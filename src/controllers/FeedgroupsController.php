<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme\controllers;

use kristiansp\refeedme\RefeedMe;
use kristiansp\refeedme\models\RefeedModel;
use kristiansp\refeedme\services\Feedgroups;

use craft\feedme\Plugin as FeedMe;
use craft\feedme\queue\jobs\FeedImport;

use Craft;
use craft\web\Controller;

/**
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 */
class FeedgroupsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'save'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        $feedGroups = RefeedMe::$plugin->feedgroups->getFeedGroups();

        $feeds = class_exists('\craft\feedme\Plugin') ? FeedMe::$plugin->feeds->getFeeds() : null;

        if ($feeds) {
            $feedsById = [];

            foreach ($feeds as $feed) {
                $feedsById[$feed->id] = $feed;
            }

            $feeds = $feedsById;
        }


        $variables['feeds'] = $feeds;
        $variables['feedgroups'] = $feedGroups;

        return $this->renderTemplate('refeed-me/feedgroups/index', $variables);
    }

    public function actionSave()
    {

        // NB! Testing without AJAX
        if (Craft::$app->getRequest()->isPost) {
            $params = Craft::$app->getRequest()->getBodyParams();
            $feeds = $params['feeds'];
        } else {
            $feeds = [8,2,4];
        }

        $model = new RefeedModel();
        $model->name = 'Test';
        $model->feeds = $feeds;

        $success = RefeedMe::getInstance()->feedgroups->saveFeedgroup($model);

        $return['success'] = $success;
        $return['msg'] = 'Saving feedgroup with ' . count($feeds) . ' feeds: ' . implode(', ', $feeds) . ' seemed to ' . (!$success ? 'NOT ' : '') . 'work';

        return json_encode($return);

//        return $this->renderTemplate('refeed-me/feedgroups/index', $variables);
    }

    // TODO: Decide whether to run via AJAX, or something similar to "direct" (and "passkey")
    public function actionRun($feedGroupId = null)
    {

        // TODO: If we do this via AJAX, maybe make it more like FeedMe
        $return = 'refeed-me';

        // If Feed Me is not installed and enabled, do nothing
        if ( !Craft::$app->plugins->isPluginEnabled('feed-me') ) {
            Craft::$app->getSession()->setNotice(Craft::t('refeed-me', 'Feed Me plugin must be installed and enabled.'));
            return $this->redirect($return);

        // If no feedGroup ID is passed in, there's not much we can do
        } elseif(!$feedGroupId) {
            Craft::$app->getSession()->setNotice(Craft::t('refeed-me', 'No feed group selected.'));
            return $this->redirect($return);

        // Ok, here we go
        } else {

            // NB! This is cinema specific, so doesn't belong here for generic version
            // (If we are keeping this plugin, I guess an event would be the thing)
            $entriesToShield = $this->_getEntriesToShield();
            $feeds = [
                8 => 'events',
                2 => 'shows',
                4 => 'movies',
            ];

            // Get our feed group from the DB
            $feedGroup = RefeedMe::$plugin->feedgroups->getFeedGroupById($feedGroupId);

            // Run all the feeds
            foreach ($feedGroup->feeds as $feedId) {

                $feed = FeedMe::$plugin->feeds->getFeedById($feedId);

                // TODO: Create an event to be able to shield entries here?
                // $processedElementIds = [];
                $type = $feeds[$feedId];
                $processedElementIds = $entriesToShield[$type];

                Craft::$app->getSession()->setNotice(Craft::t('refeed-me', 'Running group ' . $feedGroup->name . '.'));

                Craft::$app->getQueue()->delay(0)->push(new FeedImport([
                    'feed' => $feed,
                    'processedElementIds' => $processedElementIds,
                ]));
            }

            return $this->redirect('refeed-me');
        }

    }

    public function actionReorderFeeds()
    {
        $return['success'] = true;
        $return['msg'] = 'Reordered feeds successfully.';

        return json_encode($return);
    }

    private function _getEntriesToShield()
    {
        // Make sure the timezone is correct
        date_default_timezone_set('Europe/Berlin');

        // Start: Midnight last thursday
        // TODO: Consider doing the "mod" trick not to keep them one week back (but who cares)
        $startTime = date('Y-m-d H:i:s', strtotime('last thursday'));
        // End: Current time (now)
        $endTime = date('Y-m-d H:i:s');

        // Find shows we want to keep, to add them to the "feed"
        $showIds = Entry::find()
                 ->section('shows')
                 ->type('show')
                 ->where(['> ', 'content.field_showTime', $startTime])
                 ->andWhere(['< ', 'content.field_showTime', $endTime])
                 ->status(null)
                 ->limit(null)
                 ->ids();

        // Find movies we want to keep
        $movieIds = Entry::find()
                  ->section('movies')
                  ->type('movie')
                  ->relatedTo([
                      'targetElement' => $showIds,
                  ])
                  ->status(null)
                  ->limit(null)
                  ->ids();

        // Find events we want to keep
        $eventIds = Tag::find()
                  ->group('eventIDs')
                  ->relatedTo([
                      'sourceElement' => $movieIds,
                  ])
                  ->limit(null)
                  ->ids();

        $return = [
            'shows' => $showIds,
            'movies' => $movieIds,
            'events' => $eventIds,
        ];

        return $return;
    }

}

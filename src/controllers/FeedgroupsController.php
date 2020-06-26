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
        $variables['feeds'] = class_exists('\craft\feedme\Plugin') ? FeedMe::$plugin->feeds->getFeeds() : null;
        $variables['feedgroups'] = RefeedMe::$plugin->feedgroups->getFeedGroups();

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

    public function actionReorderFeeds()
    {
        $return['success'] = true;
        $return['msg'] = 'Reordered feeds successfully.';

        return json_encode($return);
    }

}

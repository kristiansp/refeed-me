<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme\console\controllers;

use kristiansp\refeedme\RefeedMe;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;

use craft\feedme\Plugin as FeedMe;

/**
 * Feeds Command
 *
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 */
class FeedgroupsController extends Controller
{
    // Private Properties
    // =========================================================================

    private $_time_start;

    // Public Properties
    // =========================================================================

    public $id;
    public $caching = false;

    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        return ['id', 'caching'];
    }

    /**
     * Handle refeed-me/feeds console commands
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'something';

        echo "Welcome to the console FeedsController actionIndex() method\n";

        return $result;
    }

    /**
     * Handle refeed-me/feeds/run console commands
     *
     * @return mixed
     */
     public function actionRun()
     {

         // If Feed Me is not installed and enabled, do nothing
         if ( !Craft::$app->plugins->isPluginEnabled('feed-me') ) {
             echo PHP_EOL;
             echo 'Feed Me plugin is not installed or not enabled. Refeed Me requires Feed Me to run.' . PHP_EOL;
             echo 'For install instructions visit https://plugins.craftcms.com/feed-me' . PHP_EOL;
             echo PHP_EOL;

         } else {

             // Start the clock
             $this->_time_start = microtime(true);

             // TESTING:
             $feedIds[1] = [8,2,15,4];

             // DEBUG:
             $request = Craft::$app->getRequest();

             // NB! Not working
             // $handle = RefeedMe::$handle;

             // This needs to go in the events, I guess
             if ( substr( $request->params[0], 0, 9 ) === "refeed-me" ) {
                 echo "We are surely running Refeed Me" . PHP_EOL;
             }

             $feedGroup = $feedIds[$this->id];
             echo PHP_EOL . 'Running Feed Me group #' . $this->id . ' with '. count($feedGroup) .' feeds:'. PHP_EOL . PHP_EOL;

             $uniqueFeeds = [];
             foreach ($feedGroup as $index => $id) {

                 // Start the clock
                 $time_start = microtime(true);
                 echo $index+1 . ') ';

                 // Get the feed
                 $feed = FeedMe::$plugin->feeds->getFeedById($id);

                 // Check if there is a feed with this ID
                 if ($feed === null) {
                     echo 'No feed with ID ' . $id . ' found!' . PHP_EOL . PHP_EOL;

                 // Let Feed Me run the feed
                 } else {

                     // TODO: Is this used for anything? Must a model be set?
                     $element = $feed->element->setModel($feed);

                     FeedMe::$plugin->getInstance()->runAction('feeds/run', ['id' => $id]);

                     // Add to array for clearing caches later
                     if(!in_array($feed->feedUrl, $uniqueFeeds)){
                         $uniqueFeeds[]=$feed->feedUrl;
                     }

                     // Log the total time taken to process the feed
                     $execution_time = number_format((microtime(true) - $time_start), 2);
                     echo '   (Done in ' . $execution_time . 's)' . PHP_EOL . PHP_EOL;
                 }

             }

             // Clear feeds from cache
             foreach ($uniqueFeeds as $feed) {

                 $cacheKey = ['refeed-me', $feed];
                 Craft::$app->getCache()->delete($cacheKey);

                 $message = 'Clearing feed from cache: ' . $feed;
                 FeedMe::$feedName = 'Refeed Me'; // Workaround so that it's not logged to the previous feed, in lack of a name

                 FeedMe::info($message);
                 echo $message . PHP_EOL;
             }

             // Log the total time taken to process all feeds
             $execution_time = number_format((microtime(true) - $this->_time_start), 2);
             echo 'Finished in ' . $execution_time . 's' . PHP_EOL . PHP_EOL;

         }
     }
}

<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme;

use kristiansp\refeedme\services\Feedgroups;
use kristiansp\refeedme\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

use craft\feedme\Plugin as FeedMe;
use craft\feedme\events\FeedDataEvent;
use craft\feedme\services\DataTypes;

use yii\base\Event;

/**
 * Class RefeedMe
 *
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 *
 * @property  FeedgroupsService $feedgroups
 */
class RefeedMe extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var RefeedMe
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '0.9.0-dev';

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'kristiansp\refeedme\console\controllers';
        }

        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_registerEventHandlers();

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'refeed-me',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Public Methods
    // =========================================================================

    /**
     * @return Feeds
     */
    public function getFeedgroups()
    {
        return $this->get('feedgroups');
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'refeed-me/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'feedgroups' => Feedgroups::class,
        ]);
    }

    private function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, [
                    'refeed-me/feedgroups' => 'refeed-me/feedgroups/index',
                    'refeed-me/feedgroups/save' => 'refeed-me/feedgroups/save',
                    'refeed-me/feedgroups/test/<feedId:\d+>' => 'refeed-me/feedgroups/save',
                    'refeed-me/feedgroups/reorder-feeds' => 'refeed-me/feedgroups/reorder-feeds',
                ]);
            }
        );
    }

    private function _registerEventHandlers()
    {
        Event::on(DataTypes::class, DataTypes::EVENT_BEFORE_FETCH_FEED, function(FeedDataEvent $event) {

            // TODO: Use plugin name as part of the key
            $cacheKey = ['refeed-me', $event->url];

            // NB! This will also cache ALL feeds – need to check that we're running Refeed Me!
            $feedFromCache = Craft::$app->getCache()->get($cacheKey);
            $feedFromCache = null; // NB! While figuring this out

            if ($feedFromCache) {
                $event->response = $feedFromCache;
                FeedMe::$feedName = 'Refeed Me'; // Workaround so that it's not logged to the previous feed, in lack of a name
                FeedMe::info('Found feed in cache: ' . $event->url);
            }
        });

        Event::on(DataTypes::class, DataTypes::EVENT_AFTER_FETCH_FEED, function(FeedDataEvent $event) {

            $cache = Craft::$app->getCache();
            $cacheKey = ['refeed-me', $event->url];

            // If not in cache, it's the first run over this feed, so we set it for the next one(s)
            // NB! This will also cache ALL feeds – need to check that we're running Refeed Me!
            if ( !$cache->exists($cacheKey) && false ) {
                $cache->set($cacheKey, $event->response);

                FeedMe::$feedName = 'Refeed Me'; // Workaround so that it's not logged to the previous feed, in lack of a name
                FeedMe::info('Adding feed to cache: ' . $event->url);
            }

        });
    }

}

<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme\assetbundles\refeedme;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 */
class RefeedMeAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@kristiansp/refeedme/assetbundles/refeedme/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/RefeedMe.js',
        ];

        $this->css = [
            'css/RefeedMe.css',
        ];

        parent::init();
    }
}

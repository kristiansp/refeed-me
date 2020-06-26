<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme\models;

use kristiansp\refeedme\RefeedMe;

use Craft;
use craft\base\Model;

/**
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 */
class RefeedModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $name;
    public $feeds = [];
    public $fieldMapping;
    public $caching = true;

    public $id;
    public $siteId;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['caching', 'boolean'],
            ['caching', 'default', 'value' => true],
            ['feeds', 'each', 'rule' => ['integer']],
            ['fieldMapping', 'string'],
        ];
    }
}

<?php
/**
 * Refeed Me plugin for Craft CMS 3.x
 *
 * Refeed Me is a Craft CMS plugin building on the first-party Feed Me plugin. It let's you manage feeds as groups that can be run in batches.
 *
 * @link      https://github.com/kristiansp
 * @copyright Copyright (c) 2020 Kristian S. P.
 */

namespace kristiansp\refeedme\migrations;

use kristiansp\refeedme\RefeedMe;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    Kristian S. P.
 * @package   RefeedMe
 * @since     0.9.0-dev
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        return $this->createTables();
    }

    public function safeDown()
    {
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%refeedme_feedgroups}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%refeedme_feedgroups}}',
                [
                    'id' => $this->primaryKey(),
                    'name' => $this->string()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'siteId' => $this->integer()->notNull(),

                    'feeds' => $this->text(),
                    'fieldMapping' => $this->text(),
                    'caching' => $this->boolean()->notNull()->defaultValue(true),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%refeedme_feedgroups}}');
    }

}

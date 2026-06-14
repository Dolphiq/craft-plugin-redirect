<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\migrations;

use Craft;
use craft\db\Migration;

class Install extends Migration
{
    public function safeUp()
    {
        $this->createTables();

        echo " done\n";
    }

    public function safeDown()
    {
        $this->dropTableIfExists('{{%dolphiq_redirects}}');
        $this->dropTableIfExists('{{%dolphiq_redirects_catch_all_urls}}');
        $this->removeRedirectsFromElementsTable();
        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        // new table!!

        $this->createTable('{{%dolphiq_redirects}}', [
            'id' => $this->primaryKey(),
            'sourceUrl' => $this->string(),
            'destinationUrl' => $this->string(),
            'statusCode' => $this->string(),
            'hitCount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'hitAt' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        if (!$this->db->tableExists('{{%dolphiq_redirects_catch_all_urls}}')) {
            $this->createTable(
                '{{%dolphiq_redirects_catch_all_urls}}',
                [
                    'id' => $this->primaryKey(),
                    'uri' => $this->string(255)->notNull()->defaultValue(''),
                    // 'firstHitAt' => $this->dateTime()->notNull(),
                    // 'lastHitAt' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'siteId' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'hitCount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                ]
            );
        }

        $this->addForeignKey(null, '{{%dolphiq_redirects}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
    }

    /**
     * Remove the redirect elements from the Craft elements table.
     *
     * @return void
     */
    protected function removeRedirectsFromElementsTable()
    {
        $this->delete('{{%elements}}', ['type' => 'dolphiq\redirect\elements\Redirect']);
    }
}

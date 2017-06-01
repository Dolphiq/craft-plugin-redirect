<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\Php;
use craft\models\Info;
use craft\models\Site;

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
        $this->createTable('{{%dolphiq_redirects}}', [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer(),
            'sourceUrl' => $this->string(),
            'destinationUrl' => $this->string(),
            'statusCode' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'creatorId' => $this->integer(),
            'uid' => $this->uid()
        ]);
    }
}

<?php

namespace dolphiq\redirect\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;
use dolphiq\redirect\elements\Redirect;

/**
 * m171003_120604_createmultisiteurls migration.
 */
class m171003_120604_createmultisiteurls extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        // rename table to...
        if ($this->db->tableExists('{{%dolphiq_redirects}}') && $this->db->columnExists('{{%dolphiq_redirects}}', 'siteId')) {
            MigrationHelper::renameTable('{{%dolphiq_redirects}}', '{{%dolphiq_redirects_old}}', $this);

            // create the new table
            $this->createTable('{{%dolphiq_redirects}}', [
                'id' => $this->primaryKey(),
                'sourceUrl' => $this->string(),
                'destinationUrl' => $this->string(),
                'statusCode' => $this->string(),
                'hitCount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'hitAt' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()
            ]);

            $this->addForeignKey(null, '{{%dolphiq_redirects}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);


            // Add all old redirects to the new table
            $oldRedirects = (new Query())
                ->select(['sourceUrl', 'destinationUrl', 'statusCode', 'hitCount', 'hitAt', 'dateCreated', 'dateUpdated', 'siteId'])
                ->from(['{{%dolphiq_redirects_old}}'])
                ->all();

            foreach ($oldRedirects as $oldRedirect) {
                // insert new redirect (element) into the database

                $redirect = new Redirect();
                $redirect->sourceUrl = $oldRedirect['sourceUrl'];
                $redirect->destinationUrl = $oldRedirect['destinationUrl'];
                $redirect->statusCode = $oldRedirect['statusCode'];

                if ($oldRedirect['siteId'] == null) {
                    $redirect->siteId = Craft::$app->getSites()->currentSite->id;
                } else {
                    $redirect->siteId = $oldRedirect['siteId'];
                }
                $redirect->hitCount = $oldRedirect['hitCount'];
                $redirect->hitAt = $oldRedirect['hitAt'];
                $redirect->dateCreated = $oldRedirect['dateCreated'];
                $redirect->dateUpdated = $oldRedirect['dateUpdated'];

                $res = Craft::$app->getElements()->saveElement($redirect, true, false);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->db->tableExists('{{%dolphiq_redirects_old}}')) {
            $this->dropForeignKey('dolphiq_redirects_id_fk', '{{%dolphiq_redirects}}');
            $this->dropTable('{{%dolphiq_redirects}}');
            MigrationHelper::renameTable('{{%dolphiq_redirects_old}}', '{{%dolphiq_redirects}}', $this);
        }
        return true;
    }
}

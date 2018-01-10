<?php

namespace dolphiq\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m180104_143118_c_redirects_catch_all_urls migration.
 */
class m180104_143118_c_redirects_catch_all_urls extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->tableExists('{{%dolphiq_redirects_catch_all_urls}}')) {

            $this->createTable(
                '{{%dolphiq_redirects_catch_all_urls%}}',
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
            return true;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%dolphiq_redirects_catch_all_urls}}');
        return true;
    }
}

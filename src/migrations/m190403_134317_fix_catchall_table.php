<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190403_134317_fix_catchall_table_and_rebrand migration.
 */
class m190403_134317_fix_catchall_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // The erroneous table name...
        if ($this->db->tableExists('{{%dolphiq_redirects_catch_all_urls%}}')) {
            $this->renameTable('{{%dolphiq_redirects_catch_all_urls%}}', '{{%dolphiq_redirects_catch_all_urls}}');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190403_134317_fix_catchall_table_and_rebrand cannot be reverted.\n";
        return false;
    }
}

<?php

namespace dolphiq\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170707_211256_count_fix migration.
 */
class m170707_211256_count_fix extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Place migration code here...
        if (!$this->db->columnExists('{{%dolphiq_redirects}}', 'hitCount')) {
            $this->addColumn('{{%dolphiq_redirects}}', 'hitCount', $this->integer()->notNull()->unsigned()->defaultValue(0));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170707_211256_count_fix cannot be reverted.\n";
        return false;
    }
}

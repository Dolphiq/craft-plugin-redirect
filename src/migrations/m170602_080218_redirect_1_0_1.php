<?php

namespace dolphiq\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170602_080218_redirect_1_0_1 migration.
 */
class m170602_080218_redirect_1_0_1 extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // create new columns
        if (!$this->db->columnExists('{{%dolphiq_redirects}}', 'hitCount')) {
            $this->addColumn('{{%dolphiq_redirects}}', 'hitCount', $this->integer()->notNull()->unsigned()->defaultValue(0));
        }
        if (!$this->db->columnExists('{{%dolphiq_redirects}}', 'hitAt')) {
            $this->addColumn('{{%dolphiq_redirects}}', 'hitAt', $this->dateTime());
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170602_080218_redirect_1_0_1 cannot be reverted.\n";
        return true;
    }
}

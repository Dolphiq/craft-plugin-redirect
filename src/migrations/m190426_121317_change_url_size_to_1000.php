<?php

namespace dolphiq\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190426_121317_change_url_size_to_1000 migration.
 */
class m190426_121317_change_url_size_to_1000 extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->tableExists('{{%dolphiq_redirects}}')
            && $this->db->columnExists('{{%dolphiq_redirects}}', 'sourceUrl')
            && $this->db->columnExists('{{%dolphiq_redirects}}', 'destinationUrl')
        ) {
            $this->alterColumn('{{%dolphiq_redirects}}','sourceUrl', $this->string(1000)->notNull()->defaultValue(''));
            $this->alterColumn('{{%dolphiq_redirects}}','destinationUrl', $this->string(1000)->notNull()->defaultValue(''));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190426_121317_change_url_size_to_1000 cannot be reverted, we keep the larger size.\n";
        return true;
    }
}

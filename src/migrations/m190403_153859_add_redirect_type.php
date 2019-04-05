<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m190403_153859_add_redirect_type migration.
 */
class m190403_153859_add_redirect_type extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%dolphiq_redirects}}', 'type', $this->string('8')->null()->defaultValue('static')->notNull()->after('id'));
        $this->createIndex($this->db->getIndexName('{{%dolphiq_redirects}}', 'type'), '{{%dolphiq_redirects}}', 'type');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex($this->db->getIndexName('{{%dolphiq_redirects}}', 'type'), '{{%dolphiq_redirects}}');
        $this->dropColumn('{{%dolphiq_redirects}}', 'type');
        return true;
    }
}

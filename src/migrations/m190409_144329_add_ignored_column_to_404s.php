<?php

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190409_144329_add_ignored_column_to_404s migration.
 */
class m190409_144329_add_ignored_column_to_404s extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%dolphiq_redirects_catch_all_urls}}', 'ignored',
            $this->boolean()->notNull()->after('hitCount')->defaultValue(false));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%dolphiq_redirects_catch_all_urls}}', 'ignored');
        return true;
    }
}

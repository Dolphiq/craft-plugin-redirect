<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190404_124125_add_soft_deletes migration.
 */
class m190404_124125_add_soft_deletes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Place migration code here...
        $this->addColumn('{{%dolphiq_redirects}}', 'dateDeleted',
            $this->dateTime()->null()->after('dateUpdated'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%dolphiq_redirects}}', 'dateDeleted');
        return true;
    }
}

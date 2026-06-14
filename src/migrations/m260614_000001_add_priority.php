<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\migrations;

use craft\db\Migration;

/**
 * Adds a `priority` column to redirects (lower number = evaluated first).
 */
class m260614_000001_add_priority extends Migration
{
    public function safeUp(): bool
    {
        $table = '{{%dolphiq_redirects}}';
        if (!$this->db->columnExists($table, 'priority')) {
            $this->addColumn(
                $table,
                'priority',
                $this->integer()->notNull()->defaultValue(0)->after('matchType')
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = '{{%dolphiq_redirects}}';
        if ($this->db->columnExists($table, 'priority')) {
            $this->dropColumn($table, 'priority');
        }

        return true;
    }
}

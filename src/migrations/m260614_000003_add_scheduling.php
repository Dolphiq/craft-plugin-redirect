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
 * Adds optional `postDate` / `expiryDate` columns to redirects so a redirect can
 * be scheduled to only resolve within a time window.
 */
class m260614_000003_add_scheduling extends Migration
{
    public function safeUp(): bool
    {
        $table = '{{%dolphiq_redirects}}';

        if (!$this->db->columnExists($table, 'postDate')) {
            $this->addColumn($table, 'postDate', $this->dateTime()->null()->after('priority'));
        }
        if (!$this->db->columnExists($table, 'expiryDate')) {
            $this->addColumn($table, 'expiryDate', $this->dateTime()->null()->after('postDate'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = '{{%dolphiq_redirects}}';

        if ($this->db->columnExists($table, 'expiryDate')) {
            $this->dropColumn($table, 'expiryDate');
        }
        if ($this->db->columnExists($table, 'postDate')) {
            $this->dropColumn($table, 'postDate');
        }

        return true;
    }
}

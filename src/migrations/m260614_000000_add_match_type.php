<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\migrations;

use craft\db\Migration;
use craft\db\Query;
use dolphiq\redirect\elements\Redirect;

/**
 * Adds the `matchType` column to redirects and infers a type for existing rows.
 */
class m260614_000000_add_match_type extends Migration
{
    public function safeUp(): bool
    {
        $table = '{{%dolphiq_redirects}}';

        if (!$this->db->columnExists($table, 'matchType')) {
            $this->addColumn(
                $table,
                'matchType',
                $this->string(20)->notNull()->defaultValue('exact')->after('statusCode')
            );
        }

        // Infer a match type for existing rows from their source syntax.
        $rows = (new Query())->select(['id', 'sourceUrl'])->from($table)->all();
        foreach ($rows as $row) {
            $this->update(
                $table,
                ['matchType' => Redirect::inferMatchType((string)$row['sourceUrl'])],
                ['id' => $row['id']],
                [],
                false
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        $table = '{{%dolphiq_redirects}}';
        if ($this->db->columnExists($table, 'matchType')) {
            $this->dropColumn($table, 'matchType');
        }

        return true;
    }
}

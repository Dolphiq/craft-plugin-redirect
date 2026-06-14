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
 * Adds the aggregate (PII-free) 404 analytics tables.
 */
class m260614_000002_add_404_analytics extends Migration
{
    public function safeUp(): bool
    {
        $catchAll = '{{%dolphiq_redirects_catch_all_urls}}';

        if (!$this->db->tableExists('{{%dolphiq_redirect_404_daily}}')) {
            $this->createTable('{{%dolphiq_redirect_404_daily}}', [
                'id' => $this->primaryKey(),
                'catchAllUrlId' => $this->integer()->notNull(),
                'date' => $this->date()->notNull(),
                'count' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
            $this->createIndex(null, '{{%dolphiq_redirect_404_daily}}', ['catchAllUrlId', 'date'], true);
            $this->addForeignKey(null, '{{%dolphiq_redirect_404_daily}}', ['catchAllUrlId'], $catchAll, ['id'], 'CASCADE', null);
        }

        if (!$this->db->tableExists('{{%dolphiq_redirect_404_referrers}}')) {
            $this->createTable('{{%dolphiq_redirect_404_referrers}}', [
                'id' => $this->primaryKey(),
                'catchAllUrlId' => $this->integer()->notNull(),
                'host' => $this->string(255)->notNull()->defaultValue(''),
                'path' => $this->string(255)->notNull()->defaultValue(''),
                'count' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
            $this->createIndex(null, '{{%dolphiq_redirect_404_referrers}}', ['catchAllUrlId', 'host', 'path'], true);
            $this->addForeignKey(null, '{{%dolphiq_redirect_404_referrers}}', ['catchAllUrlId'], $catchAll, ['id'], 'CASCADE', null);
        }

        if (!$this->db->tableExists('{{%dolphiq_redirect_404_agents}}')) {
            $this->createTable('{{%dolphiq_redirect_404_agents}}', [
                'id' => $this->primaryKey(),
                'catchAllUrlId' => $this->integer()->notNull(),
                'browserFamily' => $this->string(20)->notNull()->defaultValue('Other'),
                'count' => $this->integer()->unsigned()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
            $this->createIndex(null, '{{%dolphiq_redirect_404_agents}}', ['catchAllUrlId', 'browserFamily'], true);
            $this->addForeignKey(null, '{{%dolphiq_redirect_404_agents}}', ['catchAllUrlId'], $catchAll, ['id'], 'CASCADE', null);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%dolphiq_redirect_404_daily}}');
        $this->dropTableIfExists('{{%dolphiq_redirect_404_referrers}}');
        $this->dropTableIfExists('{{%dolphiq_redirect_404_agents}}');

        return true;
    }
}

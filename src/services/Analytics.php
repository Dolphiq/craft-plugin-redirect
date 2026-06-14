<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\services;

use craft\db\Query;
use craft\helpers\Db;
use DateTime;
use Yii;
use yii\base\Component;
use yii\db\Expression;

/**
 * Privacy-preserving analytics for 404s. Stores aggregate counts only — never raw
 * IPs or User-Agents. See the 404-analytics design doc for the privacy rationale.
 */
class Analytics extends Component
{
    /**
     * Derives a coarse browser family from a User-Agent. The raw UA is never stored.
     */
    public function browserFamily(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'Other';
        }
        if (preg_match('/bot|crawl|spider|slurp/i', $userAgent)) {
            return 'Bot';
        }
        if (str_contains($userAgent, 'Edg/')) {
            return 'Edge';
        }
        if (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
            return 'Opera';
        }
        if (str_contains($userAgent, 'Firefox/')) {
            return 'Firefox';
        }
        if (str_contains($userAgent, 'Chrome/')) {
            return 'Chrome';
        }
        if (str_contains($userAgent, 'Safari/')) {
            return 'Safari';
        }

        return 'Other';
    }

    /**
     * Reduces a referrer URL to host + path, dropping the query string (which can
     * carry tokens/PII). Returns nulls when there's no usable referrer.
     *
     * @return array{host: string|null, path: string|null}
     */
    public function safeReferrer(?string $referrer): array
    {
        $none = ['host' => null, 'path' => null];

        if (!$referrer) {
            return $none;
        }

        $parts = parse_url($referrer);
        if (!isset($parts['host'])) {
            return $none;
        }

        return [
            'host' => $parts['host'],
            'path' => $parts['path'] ?? '/',
        ];
    }

    /**
     * Records one 404 hit into the aggregate tables (daily count, referrer, browser).
     * Stores no IP and no raw User-Agent.
     */
    public function record(int $catchAllUrlId, ?string $referrer, ?string $userAgent, string $date): void
    {
        $increment = ['count' => new Expression('[[count]] + 1')];

        Db::upsert('{{%dolphiq_redirect_404_daily}}', [
            'catchAllUrlId' => $catchAllUrlId,
            'date' => $date,
            'count' => 1,
        ], $increment);

        $ref = $this->safeReferrer($referrer);
        if ($ref['host'] !== null) {
            Db::upsert('{{%dolphiq_redirect_404_referrers}}', [
                'catchAllUrlId' => $catchAllUrlId,
                'host' => $ref['host'],
                'path' => $ref['path'],
                'count' => 1,
            ], $increment);
        }

        Db::upsert('{{%dolphiq_redirect_404_agents}}', [
            'catchAllUrlId' => $catchAllUrlId,
            'browserFamily' => $this->browserFamily((string)$userAgent),
            'count' => 1,
        ], $increment);
    }

    /**
     * Deletes daily rows older than the retention window. Returns the number removed.
     */
    public function prune(int $retentionDays): int
    {
        $cutoff = (new DateTime("-{$retentionDays} days"))->format('Y-m-d');

        return Yii::$app->db->createCommand()
            ->delete('{{%dolphiq_redirect_404_daily}}', ['<', 'date', $cutoff])
            ->execute();
    }

    /**
     * @return array<int, array{date: string, count: int}>
     */
    public function dailyTrend(int $catchAllUrlId, int $days = 30): array
    {
        $since = (new DateTime("-{$days} days"))->format('Y-m-d');

        return (new Query())
            ->select(['date', 'count'])
            ->from('{{%dolphiq_redirect_404_daily}}')
            ->where(['catchAllUrlId' => $catchAllUrlId])
            ->andWhere(['>=', 'date', $since])
            ->orderBy(['date' => SORT_ASC])
            ->all();
    }

    /**
     * @return array<int, array{host: string, path: string, count: int}>
     */
    public function topReferrers(int $catchAllUrlId, int $limit = 10): array
    {
        return (new Query())
            ->select(['host', 'path', 'count'])
            ->from('{{%dolphiq_redirect_404_referrers}}')
            ->where(['catchAllUrlId' => $catchAllUrlId])
            ->orderBy(['count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * @return array<int, array{browserFamily: string, count: int}>
     */
    public function agentBreakdown(int $catchAllUrlId): array
    {
        return (new Query())
            ->select(['browserFamily', 'count'])
            ->from('{{%dolphiq_redirect_404_agents}}')
            ->where(['catchAllUrlId' => $catchAllUrlId])
            ->orderBy(['count' => SORT_DESC])
            ->all();
    }
}

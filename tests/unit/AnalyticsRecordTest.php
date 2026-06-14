<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use DateTime;
use dolphiq\redirect\records\CatchAllUrl;
use dolphiq\redirect\services\Analytics;
use dolphiq\redirect\services\CatchAll;

/**
 * Aggregate recording + retention pruning for 404 analytics.
 */
class AnalyticsRecordTest extends Unit
{
    private function catchAllId(): int
    {
        (new CatchAll())->registerHitByUri('missing/x');
        return CatchAllUrl::findOne(['uri' => 'missing/x', 'siteId' => Craft::$app->getSites()->currentSite->id])->id;
    }

    public function testRecordAggregatesDailyReferrerAndAgent(): void
    {
        $analytics = new Analytics();
        $id = $this->catchAllId();
        $today = (new DateTime())->format('Y-m-d');

        $analytics->record($id, 'https://ref.com/from?token=x', 'Mozilla/5.0 Chrome/120 Safari/537', $today);
        $analytics->record($id, 'https://ref.com/from?token=y', 'Mozilla/5.0 Chrome/120 Safari/537', $today);

        $trend = $analytics->dailyTrend($id, 30);
        $this->assertEquals(2, $trend[0]['count']);

        $refs = $analytics->topReferrers($id);
        $this->assertSame('ref.com', $refs[0]['host']);
        $this->assertSame('/from', $refs[0]['path']);
        $this->assertEquals(2, $refs[0]['count']);

        $agents = $analytics->agentBreakdown($id);
        $this->assertSame('Chrome', $agents[0]['browserFamily']);
        $this->assertEquals(2, $agents[0]['count']);
    }

    public function testRecordWithoutReferrerStoresNoReferrerRow(): void
    {
        $analytics = new Analytics();
        $id = $this->catchAllId();

        $analytics->record($id, null, '', (new DateTime())->format('Y-m-d'));

        $this->assertCount(0, $analytics->topReferrers($id));
    }

    public function testPruneRemovesDailyRowsOlderThanRetention(): void
    {
        $analytics = new Analytics();
        $id = $this->catchAllId();

        $analytics->record($id, null, '', '2000-01-01');
        $analytics->record($id, null, '', (new DateTime())->format('Y-m-d'));

        $this->assertSame(1, $analytics->prune(90));
    }
}

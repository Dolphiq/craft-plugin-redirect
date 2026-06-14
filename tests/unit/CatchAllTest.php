<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\records\CatchAllUrl;
use dolphiq\redirect\services\CatchAll;

/**
 * The catch-all service records missed URLs, keeping a single row per URI
 * and incrementing its hit count on repeat misses.
 */
class CatchAllTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    public function testFirstMissCreatesRowWithCountOne(): void
    {
        (new CatchAll())->registerHitByUri('missing/page');

        $row = CatchAllUrl::findOne(['uri' => 'missing/page', 'siteId' => $this->siteId()]);
        $this->assertNotNull($row);
        $this->assertEquals(1, $row->hitCount);
    }

    public function testRepeatMissesDedupeToOneRowAndIncrement(): void
    {
        $service = new CatchAll();
        $service->registerHitByUri('missing/page');
        $service->registerHitByUri('missing/page');
        $service->registerHitByUri('missing/page');

        $rows = CatchAllUrl::findAll(['uri' => 'missing/page', 'siteId' => $this->siteId()]);
        $this->assertCount(1, $rows);
        $this->assertEquals(3, $rows[0]->hitCount);
    }

    public function testDeleteWithZeroIdReturnsFalse(): void
    {
        $this->assertFalse((new CatchAll())->deleteUrlById(0));
    }
}

<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\records\CatchAllUrl;
use dolphiq\redirect\services\CatchAll;

/**
 * Covers the catch-all service beyond registration: listing, deletion, lookup by uid.
 */
class CatchAllServiceTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    public function testGetLastUrlsReturnsRecordedMisses(): void
    {
        $service = new CatchAll();
        $service->registerHitByUri('missing/one');
        $service->registerHitByUri('missing/two');

        $uris = array_map(static fn(CatchAllUrl $r) => $r->uri, $service->getLastUrls(100, $this->siteId()));

        $this->assertContains('missing/one', $uris);
        $this->assertContains('missing/two', $uris);
    }

    public function testGetLastUrlsRespectsLimit(): void
    {
        $service = new CatchAll();
        $service->registerHitByUri('a');
        $service->registerHitByUri('b');
        $service->registerHitByUri('c');

        $this->assertCount(2, $service->getLastUrls(2, $this->siteId()));
    }

    public function testDeleteUrlByIdRemovesTheRecord(): void
    {
        $service = new CatchAll();
        $service->registerHitByUri('to/delete');
        $record = CatchAllUrl::findOne(['uri' => 'to/delete', 'siteId' => $this->siteId()]);

        $this->assertTrue($service->deleteUrlById($record->id));
        $this->assertNull(CatchAllUrl::findOne(['uri' => 'to/delete', 'siteId' => $this->siteId()]));
    }

    public function testDeleteUrlByIdReturnsFalseForMissingRecord(): void
    {
        $this->assertFalse((new CatchAll())->deleteUrlById(999999));
    }

    public function testDeleteUrlByIdIsScopedToSite(): void
    {
        $service = new CatchAll();
        $service->registerHitByUri('scoped/url');
        $record = CatchAllUrl::findOne(['uri' => 'scoped/url', 'siteId' => $this->siteId()]);

        // Deleting with a different site id must be a no-op.
        $this->assertFalse($service->deleteUrlById($record->id, 999999));
        $this->assertNotNull(CatchAllUrl::findOne(['id' => $record->id]));

        // Deleting with the correct site id works.
        $this->assertTrue($service->deleteUrlById($record->id, $this->siteId()));
        $this->assertNull(CatchAllUrl::findOne(['id' => $record->id]));
    }

    public function testGetUrlByUidReturnsTheRecord(): void
    {
        $service = new CatchAll();
        $service->registerHitByUri('by/uid');
        $record = CatchAllUrl::findOne(['uri' => 'by/uid', 'siteId' => $this->siteId()]);

        $found = $service->getUrlByUid($record->uid);

        $this->assertSame('by/uid', $found->uri);
    }
}

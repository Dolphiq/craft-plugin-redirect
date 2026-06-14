<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\services\Analytics;

/**
 * Pure, privacy-preserving derivations for 404 analytics: a coarse browser family
 * from the User-Agent (no raw UA stored) and a query-stripped referrer.
 */
class AnalyticsHelpersTest extends Unit
{
    public function testBrowserFamily(): void
    {
        $service = new Analytics();
        $this->assertSame('Chrome', $service->browserFamily('Mozilla/5.0 (X11) AppleWebKit/537 Chrome/120 Safari/537'));
        $this->assertSame('Safari', $service->browserFamily('Mozilla/5.0 (Macintosh) AppleWebKit/605 Version/17 Safari/605'));
        $this->assertSame('Firefox', $service->browserFamily('Mozilla/5.0 (Windows) Gecko/20100101 Firefox/121'));
        $this->assertSame('Edge', $service->browserFamily('Mozilla/5.0 Chrome/120 Safari/537 Edg/120'));
        $this->assertSame('Bot', $service->browserFamily('Mozilla/5.0 (compatible; Googlebot/2.1; +http://google.com/bot.html)'));
        $this->assertSame('Other', $service->browserFamily(''));
        $this->assertSame('Other', $service->browserFamily('something weird'));
    }

    public function testSafeReferrerStripsQueryAndKeepsHostPath(): void
    {
        $service = new Analytics();

        $r = $service->safeReferrer('https://example.com/some/page?token=secret&x=1');
        $this->assertSame('example.com', $r['host']);
        $this->assertSame('/some/page', $r['path']);
    }

    public function testSafeReferrerDefaultsPathToSlash(): void
    {
        $r = (new Analytics())->safeReferrer('https://example.com');
        $this->assertSame('example.com', $r['host']);
        $this->assertSame('/', $r['path']);
    }

    public function testSafeReferrerHandlesEmptyAndInvalid(): void
    {
        $service = new Analytics();
        $this->assertNull($service->safeReferrer('')['host']);
        $this->assertNull($service->safeReferrer(null)['host']);
        $this->assertNull($service->safeReferrer('not a url')['host']);
    }
}

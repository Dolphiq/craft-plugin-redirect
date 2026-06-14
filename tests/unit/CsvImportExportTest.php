<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * Redirects can be exported to and imported from CSV.
 */
class CsvImportExportTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    private function makeRedirect(string $source, string $dest, string $code = '301'): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = $code;
        Craft::$app->getElements()->saveElement($redirect);
    }

    public function testExportProducesCsvWithHeaderAndRows(): void
    {
        $this->makeRedirect('from-a', 'to-a', '301');
        $this->makeRedirect('from-b', 'to-b', '302');

        $csv = (new Redirects())->exportCsv($this->siteId());

        $this->assertStringContainsString('sourceUrl,destinationUrl,statusCode', $csv);
        $this->assertStringContainsString('from-a,to-a,301', $csv);
        $this->assertStringContainsString('from-b,to-b,302', $csv);
    }

    public function testImportCreatesRedirects(): void
    {
        $csv = "sourceUrl,destinationUrl,statusCode\nlegacy/page,new/page,301\npromo,/sale,302\n";

        $result = (new Redirects())->importCsv($csv, $this->siteId());

        $this->assertSame(2, $result['created']);
        $match = (new Redirects())->resolveForUri('legacy/page', $this->siteId());
        $this->assertSame('new/page', $match['destinationUrl']);
        $this->assertSame('302', (new Redirects())->resolveForUri('promo', $this->siteId())['statusCode']);
    }

    public function testImportSkipsHeaderBlankAndIncompleteRows(): void
    {
        $csv = "sourceUrl,destinationUrl,statusCode\n\nonly-source\ngood,dest,301\n";

        $result = (new Redirects())->importCsv($csv, $this->siteId());

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped']);
    }

    public function testImportDefaultsMissingStatusCodeTo301(): void
    {
        $csv = "good,dest\n";

        (new Redirects())->importCsv($csv, $this->siteId());

        $this->assertSame('301', (new Redirects())->resolveForUri('good', $this->siteId())['statusCode']);
    }
}

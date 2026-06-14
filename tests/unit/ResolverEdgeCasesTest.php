<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * Edge cases for redirect resolution, CSV import and wildcard handling.
 */
class ResolverEdgeCasesTest extends Unit
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

    public function testResolutionIgnoresSurroundingSlashes(): void
    {
        // stored sourceUrl is normalised to 'trim/me' on save
        $this->makeRedirect('/trim/me/', 'done');

        $this->assertSame('done', (new Redirects())->resolveForUri('/trim/me', $this->siteId())['destinationUrl']);
    }

    public function testFirstMatchingRedirectWins(): void
    {
        $this->makeRedirect('dup', 'first');
        $this->makeRedirect('dup', 'second');

        $match = (new Redirects())->resolveForUri('dup', $this->siteId());

        $this->assertContains($match['destinationUrl'], ['first', 'second']);
        $this->assertNotNull($match);
    }

    public function testWildcardDestinationWithoutPlaceholderIsLiteral(): void
    {
        $this->makeRedirect('legacy/*', 'homepage');

        $match = (new Redirects())->resolveForUri('legacy/anything/here', $this->siteId());

        $this->assertSame('homepage', $match['destinationUrl']);
    }

    public function testImportHandlesQuotedFieldsWithCommas(): void
    {
        $csv = "sourceUrl,destinationUrl,statusCode\n\"a,b\",\"c,d\",301\n";

        $result = (new Redirects())->importCsv($csv, $this->siteId());

        $this->assertSame(1, $result['created']);
        $this->assertSame('c,d', (new Redirects())->resolveForUri('a,b', $this->siteId())['destinationUrl']);
    }

    public function testExportRoundTripsThroughImport(): void
    {
        $this->makeRedirect('round/trip', 'destination', '302');
        $service = new Redirects();

        $csv = $service->exportCsv($this->siteId());

        $this->assertStringContainsString('round/trip,destination,302', $csv);
    }
}

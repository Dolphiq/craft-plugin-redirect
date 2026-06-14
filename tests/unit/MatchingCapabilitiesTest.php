<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * Backward-compatible matching: `<name:regex>` constraints and query-string
 * parameter substitution into the destination.
 */
class MatchingCapabilitiesTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    private function makeRedirect(string $source, string $dest): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = '301';
        Craft::$app->getElements()->saveElement($redirect);
    }

    public function testRegexConstraintMatchesMultipleSegments(): void
    {
        $this->makeRedirect('wholepath/<options:.+>', 'otherpath/<options>');

        $match = (new Redirects())->resolveForUri('wholepath/this/is/long', $this->siteId());

        $this->assertNotNull($match);
        $this->assertSame('otherpath/this/is/long', $match['destinationUrl']);
    }

    public function testRegexConstraintRestrictsTheMatch(): void
    {
        $this->makeRedirect('item/<id:\d+>', 'products/<id>');

        $this->assertSame('products/42', (new Redirects())->resolveForUri('item/42', $this->siteId())['destinationUrl']);
        $this->assertNull((new Redirects())->resolveForUri('item/not-a-number', $this->siteId()));
    }

    public function testSubstituteQueryParamsFillsDestinationPlaceholders(): void
    {
        $service = new Redirects();

        $result = $service->substituteQueryParams('book-detail/<bookId>/index.html', ['bookId' => '124']);

        $this->assertSame('book-detail/124/index.html', $result);
    }

    public function testSubstituteQueryParamsLeavesUnknownPlaceholders(): void
    {
        $service = new Redirects();

        $result = $service->substituteQueryParams('x/<missing>', []);

        $this->assertSame('x/<missing>', $result);
    }
}

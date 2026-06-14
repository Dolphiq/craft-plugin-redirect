<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * resolveForUri() looks up the redirect that matches a requested URI for a site,
 * returning the destination/status/id, or null when nothing matches.
 * This is the on-demand resolution used to handle would-be-404s.
 */
class ResolveForUriTest extends Unit
{
    private function makeRedirect(string $source, string $dest, string $code = '301'): Redirect
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = $code;
        Craft::$app->getElements()->saveElement($redirect);

        return $redirect;
    }

    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    public function testResolvesExactMatch(): void
    {
        $redirect = $this->makeRedirect('old-page', 'new-page');

        $match = (new Redirects())->resolveForUri('old-page', $this->siteId());

        $this->assertNotNull($match);
        $this->assertSame('new-page', $match['destinationUrl']);
        $this->assertSame('301', (string)$match['statusCode']);
        $this->assertEquals($redirect->id, $match['redirectId']);
    }

    public function testReturnsNullWhenNothingMatches(): void
    {
        $this->makeRedirect('old-page', 'new-page');

        $match = (new Redirects())->resolveForUri('no/such/path', $this->siteId());

        $this->assertNull($match);
    }

    public function testResolvesNamedParameterPattern(): void
    {
        $this->makeRedirect('category/<catname>/overview.php', 'overview/category/<catname>');

        $match = (new Redirects())->resolveForUri('category/books/overview.php', $this->siteId());

        $this->assertNotNull($match);
        $this->assertSame('overview/category/books', $match['destinationUrl']);
    }
}

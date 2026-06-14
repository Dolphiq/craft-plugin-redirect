<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * Source URLs may use a `*` wildcard. The matched portion is substituted into
 * the corresponding `*` in the destination, in order.
 */
class WildcardMatchTest extends Unit
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

    public function testWildcardSubstitutesMatchedSegment(): void
    {
        $this->makeRedirect('docs/*', 'help/*');

        $match = (new Redirects())->resolveForUri('docs/getting-started', $this->siteId());

        $this->assertNotNull($match);
        $this->assertSame('help/getting-started', $match['destinationUrl']);
    }

    public function testWildcardMatchesAcrossMultipleSegments(): void
    {
        $this->makeRedirect('old/*', 'new/*');

        $match = (new Redirects())->resolveForUri('old/a/b/c', $this->siteId());

        $this->assertSame('new/a/b/c', $match['destinationUrl']);
    }

    public function testWildcardDoesNotMatchDifferentPrefix(): void
    {
        $this->makeRedirect('docs/*', 'help/*');

        $this->assertNull((new Redirects())->resolveForUri('blog/x', $this->siteId()));
    }
}

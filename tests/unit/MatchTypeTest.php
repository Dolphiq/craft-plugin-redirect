<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * Explicit match types: exact | prefix | wildcard | pattern, plus inference of the
 * type from source syntax for redirects created without an explicit type.
 */
class MatchTypeTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    private function makeRedirect(string $source, string $dest, ?string $matchType = null): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = '301';
        if ($matchType !== null) {
            $redirect->matchType = $matchType;
        }
        Craft::$app->getElements()->saveElement($redirect);
    }

    public function testInferMatchTypeFromSyntax(): void
    {
        $this->assertSame('wildcard', Redirect::inferMatchType('docs/*'));
        $this->assertSame('pattern', Redirect::inferMatchType('item/<id>'));
        $this->assertSame('exact', Redirect::inferMatchType('plain/path'));
    }

    public function testPrefixMatch(): void
    {
        $this->makeRedirect('blog', 'news', 'prefix');

        $this->assertSame('news', (new Redirects())->resolveForUri('blog/2024/post', $this->siteId())['destinationUrl']);
        $this->assertSame('news', (new Redirects())->resolveForUri('blog', $this->siteId())['destinationUrl']);
        // not a path-prefix
        $this->assertNull((new Redirects())->resolveForUri('blogger', $this->siteId()));
    }

    public function testExactTypeDoesNotTreatAngleBracketsAsPattern(): void
    {
        // explicit exact: the source is matched literally, not as a pattern
        $this->makeRedirect('literal/<x>', 'dest', 'exact');

        $this->assertSame('dest', (new Redirects())->resolveForUri('literal/<x>', $this->siteId())['destinationUrl']);
        $this->assertNull((new Redirects())->resolveForUri('literal/anything', $this->siteId()));
    }

    public function testInferredPatternStillMatchesWhenTypeOmitted(): void
    {
        // created without a matchType -> inferred as pattern from `<id>`
        $this->makeRedirect('item/<id>', 'products/<id>');

        $this->assertSame('products/42', (new Redirects())->resolveForUri('item/42', $this->siteId())['destinationUrl']);
    }
}

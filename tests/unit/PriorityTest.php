<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * When two redirects could match the same URL, the one with the lower priority
 * number is evaluated first and wins.
 */
class PriorityTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    private function makeRedirect(string $source, string $dest, string $matchType, int $priority): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = '301';
        $redirect->matchType = $matchType;
        $redirect->priority = $priority;
        Craft::$app->getElements()->saveElement($redirect);
    }

    public function testLowerPriorityNumberWinsOnOverlap(): void
    {
        // Both match /blog/post; the exact one has higher priority (lower number).
        $this->makeRedirect('blog/*', 'wild', 'wildcard', 10);
        $this->makeRedirect('blog/post', 'specific', 'exact', 1);

        $match = (new Redirects())->resolveForUri('blog/post', $this->siteId());

        $this->assertSame('specific', $match['destinationUrl']);
    }

    public function testPriorityDefaultsToZero(): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = 'a';
        $redirect->destinationUrl = 'b';
        $redirect->statusCode = '301';
        Craft::$app->getElements()->saveElement($redirect);

        $found = (new Redirects())->getRedirectById($redirect->id);
        $this->assertEquals(0, $found->priority);
    }
}

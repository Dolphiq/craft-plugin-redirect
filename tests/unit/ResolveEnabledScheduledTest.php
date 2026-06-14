<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use DateTime;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * End-to-end: the resolver skips disabled redirects and redirects outside their
 * scheduled postDate/expiryDate window.
 */
class ResolveEnabledScheduledTest extends Unit
{
    private function newRedirect(string $source, string $dest, array $attrs = []): Redirect
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = '301';
        foreach ($attrs as $k => $v) {
            $redirect->$k = $v;
        }
        Craft::$app->getElements()->saveElement($redirect);
        return $redirect;
    }

    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    public function testEnabledRedirectResolves(): void
    {
        $this->newRedirect('enabled-page', 'target', ['enabled' => true]);
        $match = (new Redirects())->resolveForUri('enabled-page', $this->siteId());
        $this->assertNotNull($match);
        $this->assertSame('target', $match['destinationUrl']);
    }

    public function testDisabledRedirectDoesNotResolve(): void
    {
        $this->newRedirect('disabled-page', 'target', ['enabled' => false]);
        $match = (new Redirects())->resolveForUri('disabled-page', $this->siteId());
        $this->assertNull($match);
    }

    public function testFuturePostDateDoesNotResolve(): void
    {
        $this->newRedirect('future-page', 'target', ['postDate' => new DateTime('+10 days')]);
        $match = (new Redirects())->resolveForUri('future-page', $this->siteId());
        $this->assertNull($match);
    }

    public function testExpiredRedirectDoesNotResolve(): void
    {
        $this->newRedirect('expired-page', 'target', ['expiryDate' => new DateTime('-10 days')]);
        $match = (new Redirects())->resolveForUri('expired-page', $this->siteId());
        $this->assertNull($match);
    }

    public function testWithinWindowResolves(): void
    {
        $this->newRedirect('window-page', 'target', ['postDate' => new DateTime('-1 day'), 'expiryDate' => new DateTime('+1 day')]);
        $match = (new Redirects())->resolveForUri('window-page', $this->siteId());
        $this->assertNotNull($match);
        $this->assertSame('target', $match['destinationUrl']);
    }
}

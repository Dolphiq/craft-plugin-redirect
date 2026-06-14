<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * When an element's URI changes, a 301 redirect from the old URI to the new one
 * is created automatically. Reverse redirects are removed to avoid loops, and an
 * unchanged URI creates nothing.
 */
class AutoRedirectTest extends Unit
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

    public function testCreatesRedirectWhenUriChanges(): void
    {
        $service = new Redirects();

        $created = $service->createUriChangeRedirect('old-uri', 'new-uri', $this->siteId());

        $this->assertNotNull($created);
        $match = $service->resolveForUri('old-uri', $this->siteId());
        $this->assertNotNull($match);
        $this->assertSame('new-uri', $match['destinationUrl']);
        $this->assertSame('301', $match['statusCode']);
    }

    public function testNoRedirectWhenUriUnchanged(): void
    {
        $service = new Redirects();

        $created = $service->createUriChangeRedirect('same-uri', 'same-uri', $this->siteId());

        $this->assertNull($created);
        $this->assertNull($service->resolveForUri('same-uri', $this->siteId()));
    }

    public function testRemovesReverseRedirectToAvoidLoop(): void
    {
        $service = new Redirects();
        // A previous rename left new-uri -> old-uri.
        $this->makeRedirect('new-uri', 'old-uri');

        $service->createUriChangeRedirect('old-uri', 'new-uri', $this->siteId());

        // Forward redirect exists, reverse is gone (no loop).
        $this->assertSame('new-uri', $service->resolveForUri('old-uri', $this->siteId())['destinationUrl']);
        $this->assertNull($service->resolveForUri('new-uri', $this->siteId()));
    }

    public function testDoesNotDuplicateExistingRedirect(): void
    {
        $service = new Redirects();
        $service->createUriChangeRedirect('old-uri', 'new-uri', $this->siteId());

        $second = $service->createUriChangeRedirect('old-uri', 'new-uri', $this->siteId());

        $this->assertNull($second);
        $count = Redirect::find()->andWhere(['sourceUrl' => 'old-uri'])->count();
        $this->assertEquals(1, $count);
    }
}

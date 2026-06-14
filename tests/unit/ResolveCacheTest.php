<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;
use Yii;

/**
 * resolveForUri() caches its result so repeat hits skip the database, and the
 * cache is invalidated whenever a redirect is saved or deleted.
 */
class ResolveCacheTest extends Unit
{
    private function makeRedirect(string $source, string $dest): Redirect
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = '301';
        Craft::$app->getElements()->saveElement($redirect);

        return $redirect;
    }

    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    public function testRepeatResolutionIsServedFromCache(): void
    {
        $redirect = $this->makeRedirect('promo', 'destination-a');
        $service = new Redirects();

        // First call resolves and caches.
        $service->resolveForUri('promo', $this->siteId());

        // Remove the underlying rows directly, bypassing element invalidation.
        Yii::$app->db->createCommand()->delete('{{%dolphiq_redirects}}', ['id' => $redirect->id])->execute();
        Yii::$app->db->createCommand()->delete('{{%elements}}', ['id' => $redirect->id])->execute();

        // Still resolves: served from cache.
        $cached = $service->resolveForUri('promo', $this->siteId());
        $this->assertNotNull($cached);
        $this->assertSame('destination-a', $cached['destinationUrl']);
    }

    public function testCacheIsInvalidatedWhenRedirectChanges(): void
    {
        $redirect = $this->makeRedirect('promo', 'destination-a');
        $service = new Redirects();

        $first = $service->resolveForUri('promo', $this->siteId());
        $this->assertSame('destination-a', $first['destinationUrl']);

        $redirect->destinationUrl = 'destination-b';
        Craft::$app->getElements()->saveElement($redirect);

        $second = $service->resolveForUri('promo', $this->siteId());
        $this->assertSame('destination-b', $second['destinationUrl']);
    }
}

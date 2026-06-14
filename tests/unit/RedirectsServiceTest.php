<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\services\Redirects;

/**
 * Covers the Redirects service helpers: lookup by id, per-site listing,
 * config-file redirects and cache invalidation.
 */
class RedirectsServiceTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    private function makeRedirect(string $source, string $dest): Redirect
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = $source;
        $redirect->destinationUrl = $dest;
        $redirect->statusCode = '301';
        Craft::$app->getElements()->saveElement($redirect);

        return $redirect;
    }

    public function testGetRedirectByIdReturnsElement(): void
    {
        $redirect = $this->makeRedirect('look/me/up', 'found');

        $found = (new Redirects())->getRedirectById($redirect->id, $this->siteId());

        $this->assertNotNull($found);
        $this->assertSame('look/me/up', $found->sourceUrl);
    }

    public function testGetAllRedirectsForSiteReturnsSavedRedirects(): void
    {
        $this->makeRedirect('one', 'dest-one');
        $this->makeRedirect('two', 'dest-two');

        $all = (new Redirects())->getAllRedirectsForSite($this->siteId());

        $this->assertGreaterThanOrEqual(2, count($all));
    }

    public function testGetConfigFileRedirectsIsEmptyWithoutConfigFile(): void
    {
        $this->assertSame([], (new Redirects())->getConfigFileRedirects());
    }

    public function testInvalidateCacheClearsResolution(): void
    {
        $redirect = $this->makeRedirect('cached/path', 'somewhere');
        $service = new Redirects();
        $service->resolveForUri('cached/path', $this->siteId());

        // Remove the row directly, then invalidate: resolution should miss.
        \Yii::$app->db->createCommand()->delete('{{%dolphiq_redirects}}', ['id' => $redirect->id])->execute();
        \Yii::$app->db->createCommand()->delete('{{%elements}}', ['id' => $redirect->id])->execute();
        $service->invalidateCache();

        $this->assertNull($service->resolveForUri('cached/path', $this->siteId()));
    }
}

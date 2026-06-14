<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\records\Redirect as RedirectRecord;
use dolphiq\redirect\services\Redirects;

/**
 * registerHitById() bumps the hit counter and stamps the last-hit time.
 */
class RedirectHitCounterTest extends Unit
{
    private function makeRedirect(): Redirect
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = 'old-page';
        $redirect->destinationUrl = 'new-page';
        $redirect->statusCode = '301';
        Craft::$app->getElements()->saveElement($redirect);

        return $redirect;
    }

    public function testRegisterHitIncrementsCountAndStampsTime(): void
    {
        $redirect = $this->makeRedirect();

        (new Redirects())->registerHitById($redirect->id);

        $record = RedirectRecord::findOne($redirect->id);
        $this->assertEquals(1, $record->hitCount);
        $this->assertNotNull($record->hitAt);
    }

    public function testHitCountIsVisibleViaElementAfterRegister(): void
    {
        $redirect = $this->makeRedirect();
        $service = new Redirects();

        $service->registerHitById($redirect->id);

        // Re-fetched element reflects the new count (caches invalidated on hit).
        $element = $service->getRedirectById($redirect->id);
        $this->assertEquals(1, $element->hitCount);
    }

    public function testHitsAccumulate(): void
    {
        $redirect = $this->makeRedirect();
        $service = new Redirects();

        $service->registerHitById($redirect->id);
        $service->registerHitById($redirect->id);

        $record = RedirectRecord::findOne($redirect->id);
        $this->assertEquals(2, $record->hitCount);
    }

    public function testInvalidIdReturnsFalse(): void
    {
        $this->assertFalse((new Redirects())->registerHitById(0));
    }
}

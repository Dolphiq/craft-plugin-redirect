<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use DateTime;
use dolphiq\redirect\services\Redirects;

/**
 * A scheduled redirect only resolves within its optional [postDate, expiryDate]
 * window. Either bound may be null (open-ended). isScheduleActive() is the pure
 * predicate the resolver uses.
 */
class ScheduleWindowTest extends Unit
{
    private function svc(): Redirects
    {
        return new Redirects();
    }

    private function now(): DateTime
    {
        return new DateTime('2026-06-14 12:00:00');
    }

    public function testNoBoundsIsAlwaysActive(): void
    {
        $this->assertTrue($this->svc()->isScheduleActive(null, null, $this->now()));
    }

    public function testBeforePostDateIsInactive(): void
    {
        $this->assertFalse($this->svc()->isScheduleActive('2026-06-20 00:00:00', null, $this->now()));
    }

    public function testAfterPostDateIsActive(): void
    {
        $this->assertTrue($this->svc()->isScheduleActive('2026-06-01 00:00:00', null, $this->now()));
    }

    public function testAfterExpiryIsInactive(): void
    {
        $this->assertFalse($this->svc()->isScheduleActive(null, '2026-06-10 00:00:00', $this->now()));
    }

    public function testBeforeExpiryIsActive(): void
    {
        $this->assertTrue($this->svc()->isScheduleActive(null, '2026-06-20 00:00:00', $this->now()));
    }

    public function testWithinWindowIsActive(): void
    {
        $this->assertTrue($this->svc()->isScheduleActive('2026-06-01 00:00:00', '2026-06-30 00:00:00', $this->now()));
    }

    public function testOutsideWindowIsInactive(): void
    {
        $this->assertFalse($this->svc()->isScheduleActive('2026-07-01 00:00:00', '2026-07-30 00:00:00', $this->now()));
    }
}

<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\models\Settings;

/**
 * Covers the plugin settings model defaults and validation.
 */
class SettingsTest extends Unit
{
    public function testDefaults(): void
    {
        $settings = new Settings();

        $this->assertTrue($settings->redirectsActive);
        $this->assertFalse($settings->catchAllActive);
        $this->assertSame('', $settings->catchAllTemplate);
        $this->assertTrue($settings->autoCreateRedirectOnUriChange);
    }

    public function testValidatesWithDefaults(): void
    {
        $this->assertTrue((new Settings())->validate());
    }

    public function testActiveFlagsAreRequired(): void
    {
        $settings = new Settings();
        $settings->redirectsActive = null;

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('redirectsActive', $settings->getErrors());
    }
}

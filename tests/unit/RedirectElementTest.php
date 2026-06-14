<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\elements\Redirect;

/**
 * Covers the Redirect element: string representation, validation and the
 * status-code table attribute rendering.
 */
class RedirectElementTest extends Unit
{
    public function testStringRepresentationIsSourceUrl(): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = 'string/me';

        $this->assertSame('string/me', (string)$redirect);
    }

    public function testRequiresSourceAndDestination(): void
    {
        $redirect = new Redirect();

        $this->assertFalse($redirect->validate());
        $this->assertArrayHasKey('sourceUrl', $redirect->getErrors());
        $this->assertArrayHasKey('destinationUrl', $redirect->getErrors());
    }

    public function testValidatesWithSourceDestinationAndStatus(): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = 'a';
        $redirect->destinationUrl = 'b';
        $redirect->statusCode = '301';

        $this->assertTrue($redirect->validate());
    }

    public function testStatusCodeAttributeRendersLabel(): void
    {
        $redirect = new Redirect();
        $redirect->statusCode = '301';

        $html = $redirect->getAttributeHtml('statusCode');

        $this->assertStringContainsString('301', $html);
    }
}

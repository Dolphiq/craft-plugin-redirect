<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\elements\Redirect;

/**
 * The element index renders the source/destination URLs, which are free text.
 * They must be HTML-encoded so a value like `<catname>` cannot inject markup.
 */
class RedirectTableAttributeTest extends Unit
{
    public function testSourceUrlIsHtmlEncoded(): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = 'category/<catname>/overview.php';

        $html = $redirect->getAttributeHtml('sourceUrl');

        $this->assertStringNotContainsString('<catname>', $html);
        $this->assertStringContainsString('&lt;catname&gt;', $html);
    }

    public function testDestinationUrlIsHtmlEncoded(): void
    {
        $redirect = new Redirect();
        $redirect->destinationUrl = 'overview/<catname>';

        $html = $redirect->getAttributeHtml('destinationUrl');

        $this->assertStringNotContainsString('<catname>', $html);
        $this->assertStringContainsString('&lt;catname&gt;', $html);
    }
}

<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\elements\Redirect;

/**
 * formatUrl() normalises a source/destination URL before it is stored.
 * For relative URLs it trims whitespace and strips a single leading slash.
 */
class RedirectFormatUrlTest extends Unit
{
    private function format(string $url): string
    {
        return (new Redirect())->formatUrl($url);
    }

    public function testTrimsSurroundingWhitespace(): void
    {
        $this->assertSame('about-us', $this->format('  about-us  '));
    }

    public function testStripsLeadingSlash(): void
    {
        $this->assertSame('about-us', $this->format('/about-us'));
    }

    public function testKeepsTrailingSlash(): void
    {
        $this->assertSame('blog/2019/post/', $this->format('/blog/2019/post/'));
    }

    public function testLeavesCleanRelativeUrlUntouched(): void
    {
        $this->assertSame('already/clean', $this->format('already/clean'));
    }

    public function testKeepsAbsoluteUrlScheme(): void
    {
        $this->assertSame('https://example.com/page', $this->format('https://example.com/page'));
    }
}

<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\services\Redirects;

/**
 * testMatch() powers the edit-form "test this redirect" box: given a match type,
 * source, destination and a test URL, it reports whether it matches and the
 * resulting destination — without touching the database, never throwing.
 */
class TestMatchTest extends Unit
{
    public function testExactMatch(): void
    {
        $result = (new Redirects())->testMatch('exact', 'about-us', 'about', 'about-us');

        $this->assertTrue($result['matched']);
        $this->assertSame('about', $result['destination']);
        $this->assertNull($result['error']);
    }

    public function testPatternMatchSubstitutes(): void
    {
        $result = (new Redirects())->testMatch('pattern', 'item/<id>', 'products/<id>', 'item/42');

        $this->assertTrue($result['matched']);
        $this->assertSame('products/42', $result['destination']);
    }

    public function testWildcardMatch(): void
    {
        $result = (new Redirects())->testMatch('wildcard', 'docs/*', 'help/*', 'docs/a/b');

        $this->assertTrue($result['matched']);
        $this->assertSame('help/a/b', $result['destination']);
    }

    public function testPrefixMatch(): void
    {
        $result = (new Redirects())->testMatch('prefix', 'blog', 'news', 'blog/2024/post');

        $this->assertTrue($result['matched']);
        $this->assertSame('news', $result['destination']);
    }

    public function testNoMatch(): void
    {
        $result = (new Redirects())->testMatch('exact', 'about-us', 'about', 'something-else');

        $this->assertFalse($result['matched']);
        $this->assertNull($result['destination']);
        $this->assertNull($result['error']);
    }

    public function testInvalidPatternReturnsErrorNotException(): void
    {
        $result = (new Redirects())->testMatch('pattern', 'item/<id:(>', 'x', 'item/42');

        $this->assertFalse($result['matched']);
        $this->assertNotNull($result['error']);
    }
}

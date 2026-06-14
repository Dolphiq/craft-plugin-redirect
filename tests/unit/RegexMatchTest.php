<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\services\Redirects;

/**
 * The `regex` match type treats the source as a raw PCRE pattern and substitutes
 * numeric capture groups ($1, $2, …) into the destination — distinct from the
 * `<name>` / `<name:regex>` pattern type.
 */
class RegexMatchTest extends Unit
{
    private function svc(): Redirects
    {
        return new Redirects();
    }

    public function testRegexSubstitutesBackreferences(): void
    {
        $result = $this->svc()->testMatch('regex', '^blog/(\d+)/(.+)$', 'news/$2/$1', 'blog/2024/launch');

        $this->assertTrue($result['matched']);
        $this->assertSame('news/launch/2024', $result['destination']);
        $this->assertNull($result['error']);
    }

    public function testRegexSingleGroup(): void
    {
        $result = $this->svc()->testMatch('regex', '^products/(\d+)$', 'shop/item/$1', 'products/42');

        $this->assertTrue($result['matched']);
        $this->assertSame('shop/item/42', $result['destination']);
    }

    public function testRegexNoMatch(): void
    {
        $result = $this->svc()->testMatch('regex', '^products/(\d+)$', 'shop/$1', 'products/abc');

        $this->assertFalse($result['matched']);
        $this->assertNull($result['error']);
    }

    public function testUnknownBackreferenceLeftIntact(): void
    {
        $result = $this->svc()->testMatch('regex', '^a/(\d+)$', 'b/$1/$5', 'a/7');

        $this->assertTrue($result['matched']);
        $this->assertSame('b/7/$5', $result['destination']);
    }

    public function testInvalidRegexReportsErrorNotException(): void
    {
        $result = $this->svc()->testMatch('regex', '^a/(\d+$', 'b/$1', 'a/7');

        $this->assertFalse($result['matched']);
        $this->assertNotNull($result['error']);
    }
}

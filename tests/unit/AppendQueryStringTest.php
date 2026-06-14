<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\services\Redirects;

/**
 * Regression for issue #138: a `?` must NOT be appended to a redirect destination
 * when the incoming request has no query string. When it does, the query string is
 * carried over (joined with `&` if the destination already has one).
 */
class AppendQueryStringTest extends Unit
{
    private function svc(): Redirects
    {
        return new Redirects();
    }

    public function testNoQueryStringLeavesDestinationUntouched(): void
    {
        $this->assertSame('https://example.com/about', $this->svc()->appendQueryString('https://example.com/about', ''));
    }

    public function testQueryStringIsAppendedWithQuestionMark(): void
    {
        $this->assertSame(
            'https://example.com/about?utm=news',
            $this->svc()->appendQueryString('https://example.com/about', 'utm=news')
        );
    }

    public function testQueryStringJoinsExistingDestinationQueryWithAmpersand(): void
    {
        $this->assertSame(
            'https://example.com/about?ref=1&utm=news',
            $this->svc()->appendQueryString('https://example.com/about?ref=1', 'utm=news')
        );
    }
}

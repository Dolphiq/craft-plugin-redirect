<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use dolphiq\redirect\RedirectPlugin;

/**
 * The source URL is turned into a Yii URL-rule key:
 * a `#` fragment is dropped, and a purely numeric source is wrapped in slashes
 * so it routes as a path segment (e.g. `12` -> `/12/`).
 */
class RuleKeyForSourceUrlTest extends Unit
{
    public function testPlainPathIsUnchanged(): void
    {
        $this->assertSame('about-us', RedirectPlugin::ruleKeyForSourceUrl('about-us'));
    }

    public function testNestedPathIsUnchanged(): void
    {
        $this->assertSame('blog/2019/post', RedirectPlugin::ruleKeyForSourceUrl('blog/2019/post'));
    }

    public function testFragmentIsStripped(): void
    {
        $this->assertSame('page', RedirectPlugin::ruleKeyForSourceUrl('page#section'));
    }

    public function testNumericSourceIsWrappedInSlashes(): void
    {
        $this->assertSame('/12/', RedirectPlugin::ruleKeyForSourceUrl('12'));
    }

    public function testNumericSourceWithFragmentIsStrippedThenWrapped(): void
    {
        $this->assertSame('/12/', RedirectPlugin::ruleKeyForSourceUrl('12#section'));
    }
}

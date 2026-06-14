<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\gql\RedirectType;
use dolphiq\redirect\services\Redirects;
use GraphQL\Type\Definition\ObjectType;

/**
 * Covers the GraphQL data shape and type definition for redirects.
 */
class GraphqlTest extends Unit
{
    private function siteId(): int
    {
        return Craft::$app->getSites()->currentSite->id;
    }

    public function testRedirectDataForSiteReturnsScalarRows(): void
    {
        $redirect = new Redirect();
        $redirect->sourceUrl = 'gql/source';
        $redirect->destinationUrl = 'gql/dest';
        $redirect->statusCode = '301';
        Craft::$app->getElements()->saveElement($redirect);

        $rows = (new Redirects())->getRedirectDataForSite($this->siteId());

        $this->assertNotEmpty($rows);
        $row = $rows[0];
        $this->assertSame('gql/source', $row['sourceUrl']);
        $this->assertSame('gql/dest', $row['destinationUrl']);
        $this->assertSame('301', $row['statusCode']);
        $this->assertIsInt($row['hitCount']);
    }

    public function testGqlTypeExposesExpectedFields(): void
    {
        $type = RedirectType::getType();

        $this->assertInstanceOf(ObjectType::class, $type);
        $fields = array_keys($type->getFields());
        $this->assertContains('sourceUrl', $fields);
        $this->assertContains('destinationUrl', $fields);
        $this->assertContains('statusCode', $fields);
        $this->assertContains('hitCount', $fields);
    }
}

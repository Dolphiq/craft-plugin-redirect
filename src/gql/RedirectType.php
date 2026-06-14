<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\gql;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * GraphQL object type for a redirect.
 */
class RedirectType
{
    public const TYPE_NAME = 'Redirect';

    public static function getType(): ObjectType
    {
        if ($type = GqlEntityRegistry::getEntity(self::TYPE_NAME)) {
            return $type;
        }

        return GqlEntityRegistry::createEntity(self::TYPE_NAME, new ObjectType([
            'name' => self::TYPE_NAME,
            'fields' => [
                'id' => Type::int(),
                'sourceUrl' => Type::string(),
                'destinationUrl' => Type::string(),
                'statusCode' => Type::string(),
                'hitCount' => Type::int(),
            ],
        ]));
    }
}

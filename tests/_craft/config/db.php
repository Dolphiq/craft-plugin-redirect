<?php

use craft\helpers\App;

return [
    'dsn' => App::env('CRAFT_DB_DSN'),
    'user' => App::env('CRAFT_DB_USER'),
    'password' => App::env('CRAFT_DB_PASSWORD'),
    'schema' => App::env('CRAFT_DB_SCHEMA'),
    'tablePrefix' => App::env('CRAFT_DB_TABLE_PREFIX'),
    // Craft 5 / MySQL 8 default; the test DB otherwise inherits utf8mb3 and rejects the 0900 collation.
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_0900_ai_ci',
];

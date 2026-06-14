<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

// Test database + app env. Set here (not via a .env file) so we don't depend on
// vlucas/phpdotenv 5.x — Craft 4 pins the 3.x line, which Codeception's param loader rejects.
$env = [
    'CRAFT_DB_DSN' => 'mysql:host=db;port=3306;dbname=craft_test',
    'CRAFT_DB_USER' => 'db',
    'CRAFT_DB_PASSWORD' => 'db@123',
    'CRAFT_DB_SCHEMA' => '',
    'CRAFT_DB_TABLE_PREFIX' => '',
    'SECURITY_KEY' => 'test-security-key',
];
foreach ($env as $name => $value) {
    putenv("$name=$value");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

// Use the current installation of Craft.
const CRAFT_TESTS_PATH = __DIR__;
const CRAFT_ROOT_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft';
const CRAFT_STORAGE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'storage';
const CRAFT_TEMPLATES_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'templates';
const CRAFT_CONFIG_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'config';
const CRAFT_MIGRATIONS_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'migrations';
const CRAFT_TRANSLATIONS_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'translations';
define('CRAFT_VENDOR_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor');

$devMode = true;

TestSetup::configureCraft();

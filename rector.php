<?php

declare(strict_types=1);

use craft\rector\SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSets([
        SetList::CRAFT_CMS_50,
    ]);

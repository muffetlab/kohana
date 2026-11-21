<?php

/**
 * Renames Kohana 3.5 classes to their Kohana 4 names in your application.
 *
 *     composer require --dev rector/rector
 *     vendor/bin/rector process application --config vendor/kohana/kohana/upgrade/rector.php
 *
 * Set KOHANA_RECTOR_PATHS to a comma separated list to change the processed paths.
 */

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

$paths = getenv('KOHANA_RECTOR_PATHS') ? explode(',', getenv('KOHANA_RECTOR_PATHS')) : [getcwd() . '/application'];

return RectorConfig::configure()
    ->withPaths($paths)
    ->withConfiguredRule(RenameClassRector::class, array_filter(require __DIR__ . '/class-map.php'))
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);

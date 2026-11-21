<?php

use Kohana\Foundation\Application;

// Test data files (feeds, images, message and view directories)
define('KOHANA_FIXTURES', __DIR__ . '/Fixtures');

require __DIR__ . '/../modules/unittest/bootstrap_all_modules.php';

// Database groups for the tests, from TEST_* environment variables
$database = Application::$config->load('database');
foreach (require __DIR__ . '/database.php' as $name => $group) {
    $database->set($name, $group);
}
unset($database, $name, $group);

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

// Native sessions started by tests go to the system temp directory, not storage/
Application::$config->load('session')->set('native', ['save_path' => sys_get_temp_dir() . '/kohana-test-sessions'] + Application::$config->load('session')->get('native', []));

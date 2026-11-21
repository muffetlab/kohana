<?php

use Kohana\Foundation\Application;

include_once('bootstrap.php');

// Enable all modules we can find
$modules_iterator = new DirectoryIterator(MODPATH);

$modules = [];

foreach ($modules_iterator as $module) {
    if ($module->isDir() && !$module->isDot()) {
        $modules[$module->getFilename()] = MODPATH . $module->getFilename();
    }
}

Application::modules(Application::modules() + $modules);

unset($modules_iterator, $modules, $module);

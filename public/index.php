<?php

use Kohana\Console\Cli;
use Kohana\Console\Command;
use Kohana\Http\Request;

/**
 * Set the PHP error reporting level. If you set this in php.ini, you remove this.
 * @link https://www.php.net/errorfunc.configuration#ini.error-reporting
 *
 * When developing your application, it is highly recommended to enable notices
 * and warnings. Enable them by using: E_ALL
 *
 * In a production environment, it is safe to ignore notices and warnings.
 * Disable them by using: E_ALL & ~E_NOTICE & ~E_DEPRECATED
 */
error_reporting(E_ALL);

/**
 * End of standard configuration! Changing any of the code below should only be
 * attempted by those with a working knowledge of Kohana internals.
 *
 * @link https://kohana.top/guide/using.configuration
 */
// Project paths: BASEPATH, APPPATH, MODPATH, SYSPATH, DOCROOT, VENDOR_PATH
require __DIR__ . '/../bootstrap/paths.php';

if (file_exists(__DIR__ . '/install.php')) {
    // Load the installation check
    return include __DIR__ . '/install.php';
}

// Bootstrap the application
require BASEPATH . 'bootstrap/app.php';

if (PHP_SAPI === 'cli') {
    // Try and load minion
    isset(\Kohana\Foundation\Application::modules()['minion']) or die('Please enable the Minion module for CLI support.');
    set_exception_handler([\Kohana\Console\ConsoleException::class, 'handler']);

    Command::factory(Cli::options())->execute();
} else {
    /**
     * Execute the main request. A source of the URI can be passed, e.g., $_SERVER['PATH_INFO'].
     * If no source is specified, the URI will be automatically detected.
     */
    echo Request::factory(null, [], false)
        ->execute()
        ->sendHeaders(true)
        ->body();
}

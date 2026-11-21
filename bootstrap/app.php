<?php

use Kohana\Config\FileReader;
use Kohana\Cookie\Cookie;
use Kohana\Foundation\Application;
use Kohana\Foundation\PackageManifest;
use Kohana\Http\Http;
use Kohana\Log\Writers\FileWriter;
use Kohana\Translation\Translator;

// Project paths: BASEPATH, APPPATH, MODPATH, SYSPATH, DOCROOT, VENDOR_PATH
require_once __DIR__ . '/paths.php';

// Register the Composer autoloader
require VENDOR_PATH . 'autoload.php';

// Load the project's .env file. Real environment variables take precedence over it.
Dotenv\Dotenv::createImmutable(BASEPATH)->safeLoad();

// Application settings (config/app.php), needed before the config system starts
$app = require BASEPATH . 'config/app.php';

// -- Environment setup --------------------------------------------------------

/**
 * Set the default time zone.
 *
 * @link https://kohana.top/guide/using.configuration
 * @link https://www.php.net/timezones
 */
date_default_timezone_set($app['timezone']);

/**
 * Set the default locale.
 *
 * @link https://kohana.top/guide/using.configuration
 * @link https://www.php.net/function.setlocale
 */
setlocale(LC_ALL, $app['system_locale']);

/**
 * Enable the Kohana autoloader.
 *
 * @link https://kohana.top/guide/using.autoloading
 * @link https://www.php.net/function.spl-autoload-register
 */
spl_autoload_register([Application::class, 'autoLoad']);

/**
 * Enable the Kohana autoloader for unserialization.
 *
 * @link https://www.php.net/function.spl-autoload-call
 * @link https://www.php.net/var.configuration#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

/**
 * Set the mb_substitute_character to "none"
 *
 * @link https://www.php.net/manual/en/function.mb-substitute-character.php
 */
mb_substitute_character('none');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
Translator::lang($app['locale']);

if (isset($_SERVER['SERVER_PROTOCOL'])) {
    // Replace the default protocol.
    Http::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

/**
 * Set Application::$environment from APP_ENV: production, staging, testing or
 * development ("local" means development).
 */
$environment = strtoupper($app['env']) === 'LOCAL' ? 'DEVELOPMENT' : strtoupper($app['env']);
Application::$environment = constant(Application::class . '::' . $environment);

/**
 * Initialize Kohana with the settings from config/app.php.
 */
Application::init(array_intersect_key($app, array_flip([
    'base_url', 'index_file', 'charset', 'errors', 'caching', 'cache_dir', 'cache_life', 'profile', 'expose',
])));

/**
 * Attach the file writer to logging. Multiple writers are supported.
 */
Application::$log->attach(new FileWriter(storage_path('logs')));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Application::$config->attach(new FileReader());

/**
 * Kohana modules installed with Composer are discovered from their composer.json
 * ("extra": {"kohana": {"module": "name"}}). See docs/kohana/modules.md.
 */
Application::$packages = new PackageManifest(BASEPATH, VENDOR_PATH, BASEPATH . 'bootstrap/cache/packages.php');

/**
 * Enable the modules from config/app.php, then the discovered packages.
 */
Application::modules($app['modules'] + Application::$packages->modules());

/**
 * Cookie settings (config/cookie.php).
 */
foreach (Application::$config->load('cookie')->asArray() as $setting => $value) {
    Cookie::${$setting} = $value;
}

unset($app, $environment, $setting, $value);

/**
 * Load the routes.
 */
require BASEPATH . 'routes/web.php';

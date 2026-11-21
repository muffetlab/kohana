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

// -- Environment setup --------------------------------------------------------

/**
 * Set the default time zone.
 *
 * @link https://kohana.top/guide/using.configuration
 * @link https://www.php.net/timezones
 */
date_default_timezone_set('America/Chicago');

/**
 * Set the default locale.
 *
 * @link https://kohana.top/guide/using.configuration
 * @link https://www.php.net/function.setlocale
 */
setlocale(LC_ALL, 'en_US.utf-8');

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
Translator::lang('en-us');

if (isset($_SERVER['SERVER_PROTOCOL'])) {
    // Replace the default protocol.
    Http::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

/**
 * Set Application::$environment from APP_ENV (or the older KOHANA_ENV):
 * production, staging, testing or development ("local" means development).
 */
if ($environment = env('APP_ENV', env('KOHANA_ENV'))) {
    $environment = strtoupper($environment) === 'LOCAL' ? 'DEVELOPMENT' : strtoupper($environment);
    Application::$environment = constant(Application::class . '::' . $environment);
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   null
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   storage/framework/cache
 * - integer  cache_life  lifetime, in seconds, of items cached              60
 * - boolean  errors      enable or disable error handling                   true
 * - boolean  profile     enable or disable internal profiling               true
 * - boolean  caching     enable or disable internal caching                 false
 * - boolean  expose      set the X-Powered-By header                        false
 */
Application::init([
    'base_url' => env('APP_BASE_URL', '/'),
]);

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
 * Enable modules. Modules are referenced by a relative or absolute path. Modules
 * listed here come before the discovered packages in the cascading filesystem.
 */
Application::modules([
//    'auth' => MODPATH . 'auth', // Basic authentication
//    'cache' => MODPATH . 'cache', // Caching with multiple backends
//    'database' => MODPATH . 'database', // Database access
//    'image' => MODPATH . 'image', // Image manipulation
//    'minion' => MODPATH . 'minion', // CLI Tasks
//    'orm' => MODPATH . 'orm', // Object Relationship Mapping
//    'unittest' => MODPATH . 'unittest', // Unit testing
] + Application::$packages->modules());

/**
 * Cookie salt, used to sign cookies. Generate one with: php kohana key:generate
 */
Cookie::$salt = env('COOKIE_SALT');

/**
 * Load the routes.
 */
require BASEPATH . 'routes/web.php';

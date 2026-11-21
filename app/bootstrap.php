<?php

use Kohana\Config\FileReader;
use Kohana\Cookie\Cookie;
use Kohana\Foundation\Application;
use Kohana\Http\Http;
use Kohana\Log\Writers\FileWriter;
use Kohana\Routing\Route;
use Kohana\Translation\Translator;

// Register the Composer autoloader
require DOCROOT . '../vendor/autoload.php';

// Load the project's .env file. Real environment variables take precedence over it.
Dotenv\Dotenv::createImmutable(dirname(DOCROOT))->safeLoad();

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
 * Optionally, you can enable a compatibility autoloader for use with
 * older modules that have not been updated for PSR-0.
 *
 * It is recommended to not enable this unless absolutely necessary.
 */
//spl_autoload_register([Application::class, 'auto_load_lowercase']);

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
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
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
Application::$log->attach(new FileWriter(APPPATH . 'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Application::$config->attach(new FileReader());

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Application::modules([
//    'auth' => MODPATH . 'auth', // Basic authentication
//    'cache' => MODPATH . 'cache', // Caching with multiple backends
//    'database' => MODPATH . 'database', // Database access
//    'image' => MODPATH . 'image', // Image manipulation
//    'minion' => MODPATH . 'minion', // CLI Tasks
//    'orm' => MODPATH . 'orm', // Object Relationship Mapping
//    'unittest' => MODPATH . 'unittest', // Unit testing
]);

/**
 * Cookie salt, used to sign cookies. Generate one with: php public/index.php --task=key:generate
 */
Cookie::$salt = env('COOKIE_SALT');

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
    ->defaults([
        'controller' => 'welcome',
        'action' => 'index',
    ]);

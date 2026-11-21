# Bootstrap

The bootstrap is located at `application/bootstrap.php`. It is responsible for setting up the Kohana environment and executing the main response. It is included by `index.php` (see [Request flow](flow))

[!!] The bootstrap is responsible for the flow of your application. In previous versions of Kohana the bootstrap was in `system` and was somewhat of an unseen, uneditable force. In Kohana 3 the bootstrap takes on a much more integral and versatile role. Do not be afraid to edit and change your bootstrap however you see fit.

## Environment setup

The bootstrap first sets the timezone and locale, and then adds Kohana's autoloader so the [cascading filesystem](files) works. You could add any other settings that all your application needed here.

~~~
// Sample excerpt from bootstrap.php with comments trimmed down

// Set the default time zone.
date_default_timezone_set('America/Chicago');

// Set the default locale.
setlocale(LC_ALL, 'en_US.utf-8');

// Composer autoloads the framework (Kohana\) and your application (App\).
require DOCROOT . '../vendor/autoload.php';

// Enable the Kohana autoloader for classes without a namespace.
spl_autoload_register([Application::class, 'autoLoad']);

// Enable the Kohana autoloader for unserialization.
ini_set('unserialize_callback_func', 'spl_autoload_call');
~~~

## Initialization and Configuration

Kohana is then initialized by calling `Application::init()`, and the log and [config](files/config) reader/writers are enabled.

~~~
// Sample excerpt from bootstrap.php with comments trimmed down

Application::init([
    'base_url' => '/kohana/',
]);

// Attach the file writer to logging. Multiple writers are supported.
Application::$log->attach(new FileWriter(APPPATH . 'logs'));

// Attach a file reader to config. Multiple readers are supported.
Application::$config->attach(new FileReader);
~~~

You can add conditional statements to make the bootstrap have different values based on certain settings. For example, detect whether we are live by checking `$_SERVER['HTTP_HOST']` and set caching, profiling, etc. accordingly. This is just an example, there are many different ways to accomplish the same thing.

~~~
/**
 * Set the environment status by the domain.
 */
if (strpos($_SERVER['HTTP_HOST'], 'kohana.top') !== false) {
    // We are live!
    Application::$environment = Application::PRODUCTION;

    // Turn off notices and strict errors
    error_reporting(E_ALL ^ E_NOTICE);
}

/**
 * Initialize Kohana, setting the default options.
 */
Application::init([
    'base_url' => Application::$environment === Application::PRODUCTION ? '/' : '/kohanaframework.org/',
    'caching' => Application::$environment === Application::PRODUCTION,
    'profile' => Application::$environment !== Application::PRODUCTION,
    'index_file' => false,
]);
~~~

[!!] Note: The default bootstrap will set `Application::$environment = $_ENV['KOHANA_ENV']` if set. Docs on how to supply this variable are available in your web server's documentation (e.g. [Apache](http://httpd.apache.org/docs/1.3/mod/mod_env.html#setenv), [Lighttpd](http://redmine.lighttpd.net/wiki/1/Docs:ModSetEnv#Options)). This is considered better practice than many alternative methods to set `Application::$enviroment`, as you can change the setting per server, without having to rely on config options or hostnames.

## Modules

**Read the [Modules](modules) page for a more detailed description.**

[Modules](modules) are then loaded using `Application::modules()`. Including modules is optional.

Each key in the array should be the name of the module, and the value is the path to the module, either relative or absolute.
~~~
// Example excerpt from bootstrap.php

Application::modules([
    'database' => MODPATH . 'database',
    'orm' => MODPATH . 'orm',
]);
~~~

## Routes

**Read the [Routing](routing) page for a more detailed description and more examples.**

[Routes](routing) are then defined via `Route::set()`.

~~~
// The default route that comes with Kohana 3
Route::set('default', '(<controller>(/<action>(/<id>)))')
    ->defaults([
        'controller' => 'Welcome',
        'action' => 'index',
    ]);
~~~

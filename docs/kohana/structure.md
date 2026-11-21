# Directory Structure

    app/                 Your classes, namespace App\
        Console/Commands/    console commands (php kohana make:command)
        Http/Controllers/    controllers (php kohana make:controller)
        Models/              models (php kohana make:model)
    bootstrap/
        app.php              boots Kohana: environment, modules, routes
        paths.php            BASEPATH, APPPATH, MODPATH, SYSPATH, DOCROOT, VENDOR_PATH
        cache/               generated files, such as the package manifest
    config/              configuration files
    database/
        migrations/          migrations (php kohana make:migration)
        seeders/             seeders, namespace Database\Seeders (php kohana make:seeder)
    lang/                translations (i18n)
    modules/             Kohana's modules and your own
    public/              web server document root: index.php and assets
    resources/
        views/               views
        messages/            message files, e.g. validation messages
    routes/
        web.php              routes
    storage/             written by the application; must be writable
        app/                 your own files, e.g. uploads
        framework/cache/     Kohana's internal cache (Application::$cacheDir)
        framework/sessions/  file sessions
        logs/                log files
    system/              the Kohana framework
    tests/               tests (php kohana ... / vendor/bin/pest)
    vendor/              Composer packages
    .env                 environment settings, not in version control
    kohana               the console

## Paths in code

| Helper | Path |
|---|---|
| `base_path('composer.json')` | project root |
| `app_path('Models')` | `app/` |
| `config_path()`, `database_path()`, `lang_path()` | `config/`, `database/`, `lang/` |
| `resource_path('views')` | `resources/` |
| `storage_path('logs')` | `storage/` |
| `public_path('index.php')` | `public/` |

The constants `BASEPATH`, `APPPATH`, `MODPATH`, `SYSPATH`, `DOCROOT` and `VENDOR_PATH` hold the same directories with a trailing separator.

## Writable directories

The web server needs to write to `storage/` and `bootstrap/cache/`:

    chmod -R ug+rwx storage bootstrap/cache

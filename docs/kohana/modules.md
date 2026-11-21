# Modules

Modules are simply an addition to the [Cascading Filesystem](files). A module can add any kind of file (controllers, views, classes, config files, etc.) to the filesystem available to Kohana (via `Application::findFile()`). This is useful to make any part of your application more transportable or shareable between different apps. For example, creating a new modeling system, a search engine, a css/js manager, etc.

## Where to find modules

Kolanos has created [kohana-universe](https://github.com/kolanos/kohana-universe/tree/master/modules/), a fairly comprehensive list of modules that are available on GitHub. To get your module listed there, send him a message via GitHub.

Mon Geslani created a [very nice site](http://kohana.mongeslani.com/) that allows you to sort GitHub modules by activity, watchers, forks, etc. It seems to not be as comprehensive as kohana-universe.

Andrew Hutchings has created [kohana-modules](http://www.kohana-modules.com) which is similar to the above sites.

## Enabling modules

Modules are enabled by calling `Application::modules()` and passing an array of `'name' => 'path'`. The name isn't important, but the path obviously is. A module's path does not have to be in `MODPATH`, but usually is. You can only call `Application::modules()` once.

    Application::modules([
        'auth' => MODPATH . 'auth', // Basic authentication
        'cache' => MODPATH . 'cache', // Caching with multiple backends
        'database' => MODPATH . 'database', // Connection access
        'image' => MODPATH . 'image', // Image manipulation
        'orm' => MODPATH . 'orm', // Object Relationship Mapping
        'oauth' => MODPATH . 'oauth', // OAuth authentication
        'pagination' => MODPATH . 'pagination', // Paging of results
        'unittest' => MODPATH . 'unittest', // Unit testing
    ]);

## Modules installed with Composer

A module can be a Composer package. Install it and Kohana enables it for you:

    composer require acme/kohana-blog

The package declares the module in its `composer.json`:

    "extra": {
        "kohana": {
            "module": "blog",
            "path": ".",
            "commands": {
                "blog:import": "Acme\\Blog\\Console\\ImportCommand"
            }
        }
    }

- `module`: the module name (required).
- `path`: the module directory inside the package, default the package root. Its `config/`, `views/`, `messages/`, `i18n/`, `migrations/` and `init.php` work like those of any module.
- `commands`: console commands, name => class, listed by `php kohana`.

Classes are autoloaded through the package's own `autoload` section, as with any Composer package.

After every `composer install` and `composer update`, Composer runs `php kohana package:discover` (the `post-autoload-dump` script in your `composer.json`), which writes the list to `bootstrap/cache/packages.php`. The list is also rebuilt automatically when `vendor/composer/installed.json` changes.

Modules listed in `bootstrap/app.php` come first in the cascading filesystem, then the discovered packages, so your own modules can override package files. To skip packages, list them in your `composer.json`:

    "extra": {
        "kohana": {
            "dont-discover": ["acme/kohana-blog"]
        }
    }

Use `["*"]` to turn discovery off.

## Init.php

When a module is activated, if an `init.php` file exists in that module's directory, it is included. This is the ideal place to have a module include routes or other initialization necessary for the module to function.

## How modules work

A file in an enabled module is virtually the same as having that exact file in the same place in the application folder. The main difference being that it can be overwritten by a file of the same name in a higher location (a module enabled after it, or the application folder) via the [Cascading Filesystem](files). It also provides an easy way to organize and share your code.

## Creating your own module

To create a module simply create a folder (usually in `modules/`) and place the files you want to be in the module there, and activate that module in `bootstrap/app.php`. Put its classes in a `src/` directory under your own namespace and register it in `composer.json`. To share it, publish it as a Composer package with an `extra.kohana` section. To share your module, you can upload it to [GitHub](https://github.com). You can look at examples of modules made by [Kohana](https://github.com/kohana) or [other users](#where-to-find-modules).
# Loading Classes

Kohana uses [Composer](https://getcomposer.org) and the [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading standard. You never `include` or `require` a class file: when you use a class, Composer finds the file from the class's namespace.

    use Kohana\Cookie\Cookie;

    Cookie::set('mycookie', 'any string value');

## Namespaces

| Namespace | Directory |
|---|---|
| `Kohana\` | `system/src/` and `modules/*/src/` |
| `App\` | `app/classes/` |

A class name maps to a file by replacing namespace separators with directory separators, for example:

| Class | File |
|---|---|
| `Kohana\Http\Request` | `system/src/Http/Request.php` |
| `Kohana\Database\Query\SelectBuilder` | `modules/database/src/Database/Query/SelectBuilder.php` |
| `App\Http\Controllers\WelcomeController` | `app/classes/Http/Controllers/WelcomeController.php` |
| `App\Models\User` | `app/classes/Models/User.php` |

File names must match the class name's case exactly. Your computer's filesystem may not care, but Linux servers do.

Both mappings are declared in `composer.json`:

    "autoload": {
        "psr-4": {
            "Kohana\\": ["system/src", "modules/auth/src", ...],
            "App\\": "app/classes/"
        }
    }

Run `composer dump-autoload` after you change them.

## The Kohana autoloader

`app/bootstrap.php` still registers `Application::autoLoad()`:

    spl_autoload_register([Application::class, 'autoLoad']);

It loads classes without a namespace from the `classes/` directories of the [cascading filesystem](files), converting underscores to directory separators (`Foo_Bar` → `classes/Foo/Bar.php`). New code should use namespaces and Composer instead.

## Third-party libraries

Install libraries with Composer and use them right away:

    composer require symfony/mailer

    use Symfony\Component\Mailer\Mailer;

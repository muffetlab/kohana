# Loading Classes

Kohana uses the [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading specification, provided by [Composer](https://getcomposer.org). This allows you to take advantage of PHP [autoloading](https://www.php.net/manual/en/language.oop5.autoload.php), removing the need to call [include](https://www.php.net/manual/en/function.include.php) or [require](https://www.php.net/require) before using a class. When you use a class, the autoloader will find and include the class file for you. For instance, when you want to use the [Cookie::set] method, you simply call:

    Cookie::set('mycookie', 'any string value');

Or to load an [Encrypt] instance, just call [Encrypt::instance]:

    $encrypt = Encrypt::instance();

Classes are loaded by the autoloader that Composer generates from the `psr-4` section of `composer.json`. It is required by `application/bootstrap.php`, which must load it before any class is used:

    // Register the Composer autoloader
    require DOCROOT . '../vendor/autoload.php';

The class name is converted into a file path using these rules:

1. Classes are placed in the `classes/` directory of the [filesystem](files)
2. The namespace prefix of the class is matched against the `psr-4` mappings in `composer.json` and replaced by the base directory it points to
3. The remainder of the class name, including any sub-namespaces, becomes the path of the file within that directory
4. The file name and its case must match the class name exactly

The mappings for the framework and the application look like this (abbreviated):

    "autoload": {
        "psr-4": {
            "Kohana\\": [
                "system/classes",
                "modules/auth/classes",
                "modules/cache/classes"
            ],
            "App\\": "application/classes"
        }
    }

When the `Kohana\Cookie` class is first used, the `Kohana\` prefix is replaced with `system/classes`, so the autoloader includes `system/classes/Cookie.php`. In the same way, `App\Controller\Welcome` is loaded from `application/classes/Controller/Welcome.php`.

If you add classes in a new directory or namespace, map them in `composer.json` and run `composer dump-autoload`.

[!!] Underscores in class names are no longer converted into directories. The file name simply matches the class name, e.g. `My_Class_Name` is stored in `classes/My_Class_Name.php`.

If your classes do not follow this convention, they cannot be autoloaded. You will have to manually include your files, or register your own autoloader (see below).

## Application and Framework Classes

Framework classes live in the `Kohana\` namespace and your own classes live in `App\`. When the framework must find a class that either location may provide - a controller, model or driver - it uses [Kohana::find_class], which prefers the application namespace:

    // Resolves to App\Model\User when it exists, otherwise Kohana\Model\User
    $class = Kohana::find_class('Model\\User');

## Custom Autoloaders

Classes that do not follow the PSR-4 conventions can be loaded with an additional autoloader, registered using [spl_autoload_register](https://www.php.net/manual/en/function.spl-autoload-register.php) after the Composer autoloader has been required:

    spl_autoload_register(function (string $class): bool {
        // Find and include $class, returning TRUE when it has been loaded
        ...
    });

Autoloaders are attempted in the order they were registered, so Composer always gets the first chance to load a class.

### Example: Composer Packages

Third-party libraries do not need any manual wiring: install them with [Composer](https://getcomposer.org) and their classes are registered in the same `vendor/autoload.php` file that the bootstrap already requires:

    composer require michelf/php-markdown

The library's classes are then autoloaded like any Kohana class, as soon as `application/bootstrap.php` requires the Composer autoloader.

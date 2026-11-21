# Classes

Your application's classes live in `app/` under the `App\` namespace, and Composer [autoloads](../autoloading) them. [Models](../mvc/models) and [Controllers](../mvc/controllers) are classes too, with their own conventions; read their pages to learn more.

## Helper or Library?

Kohana does not distinguish "helper" classes from "library" classes. In general a helper is used statically (see the [helpers included in Kohana](../helpers)) and a library is instantiated (like the [Database query builders](../../database/query/builder)), but both follow the same conventions.

## Creating a class

Place the file where its namespace points, following the [naming conventions](../conventions#class-names-and-file-location). For example, a `Foobar` class:

    // app/Foobar.php

    namespace App;

    class Foobar
    {
        public static function magic()
        {
            // Does something
        }
    }

Import it and call `Foobar::magic()` anywhere:

    use App\Foobar;

    Foobar::magic();

Subdirectories become sub-namespaces:

    // app/Professor/Baxter.php

    namespace App\Professor;

    class Baxter
    {
        public static function teach()
        {
            // Does something
        }
    }

## Classes in modules

A module ships its classes in its own `src/` directory under its own namespace. Register it in your `composer.json`:

    "autoload": {
        "psr-4": {
            "Acme\\Blog\\": "modules/blog/src/"
        }
    }

To change how a framework class behaves, see [Extending Classes](../extension).

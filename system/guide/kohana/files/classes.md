# Classes

TODO: Brief intro to classes.

[Models](mvc/models) and [Controllers](mvc/controllers) are classes as well, but are treated slightly differently by Kohana. Read their respective pages to learn more.

## Helper or Library?

Kohana 4 does not differentiate between "helper" classes and "library" classes like in previous versions. They are all placed in the `classes/` folder and follow the same conventions. The distinction is that in general, a "helper" class is used statically, (for examples see the [helpers included in Kohana](helpers)), and library classes are typically instantiated and used as objects (like the [Database query builders](../database/query/builder)). The distinction is not black and white, and is irrelevant anyway, since they are treated the same by Kohana.

## Creating a class

To create a new class, place a file in the `classes/` directory of the application, module or system path, with a name that matches the [class naming conventions](conventions#class-names-and-file-location). The class must be in a namespace that is mapped in the `psr-4` section of `composer.json`, and its file name and case must match the class name exactly.

For example, let's create an `App\Foobar` class. The `App\` prefix maps to `application/classes`, so the file is `application/classes/Foobar.php` and it declares:

    // application/classes/Foobar.php

    namespace App;

    class Foobar
    {
        public static function magic()
        {
            // Does something
        }
    }

We can now call `Foobar::magic()` anywhere and Kohana will [autoload](autoloading) the file for us, as long as the class is imported or referred to by its full name:

    use App\Foobar;

    Foobar::magic();

A sub-namespace adds a directory level. Sub-namespaces are mapped by the base `psr-4` entry, so no extra configuration is needed:

    // application/classes/Professor/Baxter.php

    namespace App\Professor;

    class Baxter
    {
        public static function teach()
        {
            // Does something
        }
    }

We could now call `App\Professor\Baxter::teach()` anywhere we want.

[!!] Underscores in class names are no longer converted into directories. Use a sub-namespace to create a directory level: `App\Professor\Baxter` lives in `application/classes/Professor/Baxter.php`, while the undelimited `App\Professor_Baxter` would live in `application/classes/Professor_Baxter.php`.

For examples of how to create and use classes, simply look at the 'classes' folder in `system` or any module.

## Extending classes

Because framework classes are namespaced rather than prefixed, a class is no longer extended by adding an underscore to its name. Instead, use [Kohana::find_class], which prefers the `App\` namespace, or provide the class from the application explicitly. See [Extending Classes](extension) for the full details.
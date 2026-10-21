# Conventions and Coding Style

It is encouraged that you follow Kohana's coding style. This makes code more readable and allows for easier code sharing and contributing.

## Class Names and File Location

Classes are autoloaded with [PSR-4](autoloading), so a class name maps directly onto its file path: the namespace prefix selects the base directory, and the rest of the name, including any sub-namespaces, is the path within it. The file name and its case must match the class name exactly.

The following conventions apply:

1. Framework classes live in the `Kohana\` namespace, which maps to `system/classes` and the `classes` directory of each module.
2. Your own classes live in the `App\` namespace, which maps to `application/classes`.
3. Controllers are placed in the `Controller` sub-namespace: `Kohana\Controller\Welcome` is stored in `system/classes/Controller/Welcome.php`.
4. Models are placed in the `Model` sub-namespace: `Kohana\Model\User` is stored in `modules/orm/classes/Model/User.php`.
5. A group of related classes can use a deeper sub-namespace, e.g. `Kohana\Model\Auth\User` is stored in `modules/orm/classes/Model/Auth/User.php`.

### Examples  {#class-name-examples}

Consider the following examples:

| Class Name                  | File Path                                      |
|-----------------------------|------------------------------------------------|
| `Kohana\Controller\Template`| `system/classes/Controller/Template.php`       |
| `Kohana\Model\User`         | `modules/orm/classes/Model/User.php`           |
| `Kohana\Model\Auth\User`    | `modules/orm/classes/Model/Auth/User.php`      |
| `Kohana\Database`           | `modules/database/classes/Database.php`        |
| `Kohana\Database\Query`     | `modules/database/classes/Database/Query.php`  |
| `Kohana\Form`               | `system/classes/Form.php`                      |
| `App\Controller\Welcome`    | `application/classes/Controller/Welcome.php`   |
| `App\Model\BlogPost`        | `application/classes/Model/BlogPost.php`       |

The base classes are namespaced as well. A controller extends [Controller], a model extends [Model], and both are found in the `Kohana\` namespace.

[!!] Underscores in class names are no longer converted into directories. The file name simply matches the class name, so `App\My_Class_Name` is stored in `application/classes/My_Class_Name.php`.

When the framework must find a class that either the application or the framework may provide, such as a controller or a model, it uses [Kohana::find_class], which tries the `App\` namespace before `Kohana\`:

    // Resolves to App\Model\User when it exists, otherwise Kohana\Model\User
    $class = Kohana::find_class('Model\\User');

## Coding Style

In order to produce highly consistent source code, we ask that everyone follow the [PSR-12](https://www.php-fig.org/psr/psr-12) coding standard except the naming conventions below.

### Naming Conventions

Methods and variables use under_score naming, not camelCase naming.

#### Classes

Class names are namespaced, and each segment starts with an uppercase letter:

    namespace App\Controller;

    // Controller class, extends the base Controller
    class Apple extends Controller
    {
    }

    namespace App\Model;

    // Model class, extends the base Model
    class Cheese extends Model
    {
    }

    namespace App;

    // Regular class
    class Peanut
    {
    }

#### Methods

Method names use under_score naming:

    public function my_method()
    {
    }

    public static function my_static_method()
    {
    }

## PHPDoc

Kohana follows the [PSR-5](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md) PHPDoc standard.

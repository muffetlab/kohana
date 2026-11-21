# Conventions and Coding Style

It is encouraged that you follow Kohana's coding style. This makes code more readable and allows for easier code sharing and contributing.

## Class Names and File Location

Kohana follows [PSR-4](https://www.php-fig.org/psr/psr-4/), like Laravel and most modern PHP frameworks:

1. Every class has a namespace: `Kohana\` for the framework, `App\` for your application.
2. Class names are StudlyCase, without underscores.
3. Each class lives in its own file, named after the class, in the directory that matches its namespace. File and directory names must match the class's case.

### Examples  {#class-name-examples}

| Class | File |
|---|---|
| `App\Http\Controllers\WelcomeController` | `application/classes/Http/Controllers/WelcomeController.php` |
| `App\Http\Controllers\Admin\UserController` | `application/classes/Http/Controllers/Admin/UserController.php` |
| `App\Models\BlogPost` | `application/classes/Models/BlogPost.php` |
| `App\Console\Commands\Db\MigrateCommand` | `application/classes/Console/Commands/Db/MigrateCommand.php` |
| `Kohana\Database\Query\SelectBuilder` | `modules/database/src/Database/Query/SelectBuilder.php` |
| `Kohana\Html\Form` | `system/src/Html/Form.php` |

Controllers end in `Controller`, console commands in `Command`. Models have no suffix.

## Coding Style

Kohana follows the [PSR-12](https://www.php-fig.org/psr/psr-12) coding standard.

### Naming Conventions

| What | Style | Example |
|---|---|---|
| Classes, interfaces, traits | StudlyCase | `HeaderBag`, `MySqliConnection` |
| Methods | camelCase | `findAll()`, `asArray()` |
| Properties | camelCase, no leading underscore | `protected $tableName` |
| Constants | UPPER_CASE | `Application::PRODUCTION` |
| Config keys, validation rules, message keys | snake_case | `'max_length'`, `'cache_dir'` |

#### Classes

    namespace App\Http\Controllers;

    use Kohana\Routing\Controller;

    class AppleController extends Controller
    {
        public function index()
        {
        }
    }

    namespace App\Models;

    use Kohana\Orm\Model;

    class Cheese extends Model
    {
    }

## PHPDoc

Kohana follows the [PSR-5](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md) PHPDoc standard.

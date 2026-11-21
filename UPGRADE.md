# Upgrade Guide: Kohana 3.5 → PHP 8.5

This guide lists every breaking change, step by step. Each step shows what changed and what to do in your application.

## 1. PHP 8.1+ is required

`composer.json` now requires `"php": ">=8.1"`. The framework is tested on PHP 8.5.

**Action:** Run your application on PHP 8.1 or newer.

## 2. Nullable parameters are explicit

Signatures such as `function f(array $x = null)` are now `function f(?array $x = null)`. PHP 8.4 deprecates the implicit form.

**Action:** If you extend a framework class and override one of these methods, add `?` to the same parameters. Otherwise PHP reports an incompatible-signature error. Rector does this automatically:

```php
// rector.php
->withRules([\Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class])
```

## 3. Return types on `ArrayAccess` and `Iterator` classes

| Class | Method | New return type |
|---|---|---|
| `Validation`, `Config_Group`, `HTTP_Header`, `Database_Result` | `offsetSet()`, `offsetUnset()` | `void` |
| `Validation`, `HTTP_Header`, `Database_Result`, `Database_MySQLi_Result`, `Database_Result_Cached` | `offsetGet()`, `current()` | `mixed` |

`Database_Result::next()`, `rewind()` and `seek()` keep their chainable return values and are marked `#[\ReturnTypeWillChange]`.

**Action:** If you override these methods, use the same return types.

## 4. `E_STRICT` removed

`Kohana_Exception::$php_errors` no longer has an `E_STRICT` entry. The constant is deprecated in PHP 8.4.

**Action:** Remove `E_STRICT` from `error_reporting()` calls in your `bootstrap.php`.

## 5. `Arr::path()` with objects

On PHP 8, `array_key_exists()` no longer accepts objects. `Arr::path()` now checks `ArrayAccess` objects (for example `Config_Group`) with `offsetExists()`. Behaviour is unchanged. No action needed.

## 6. `UTF8::strcasecmp()` returns -1, 0 or 1

On PHP 8.2+, string comparison functions return only -1, 0 or 1, not the byte difference.

**Action:** Compare the result's sign (`< 0`, `> 0`) instead of its exact value.

## 7. Tests: PHPUnit 9.6

- `setUp()`, `tearDown()`, `setUpBeforeClass()` and `tearDownAfterClass()` must declare `: void`.
- `assertRegExp()` → `assertMatchesRegularExpression()`.
- `assertContains()` on a string → `assertStringContainsString()`.
- PHPUnit calls test methods in strict mode, so data provider values must match the parameters' scalar types exactly.

## 8. `Date::months()` no longer uses `strftime()`

`strftime()` is deprecated in PHP 8.1. `Date::MONTHS_LONG` is now `'F'` and `Date::MONTHS_SHORT` is `'M'`, both `date()` formats. Month names no longer follow `setlocale()` and are always English.

**Action:** If you need localized month names, translate them with `__()` or use `IntlDateFormatter`. If you passed `'%B'`/`'%b'` directly instead of the constants, use the constants.

## 9. `Valid::digit()` rejects non-strings

`Valid::digit()` returns `false` for `null`, `bool`, `float` and other non-string, non-int values. Before, PHP's `ctype_digit()` handled them inconsistently.

**Action:** Cast the value to a string before validating it if it may arrive as another type.

## 10. `File::split()` accepts a float piece size

`File::split(string $filename, float $piece_size = 10)`: you can split into pieces smaller than 1 MB, for example `0.5`. No action needed.

## 11. Tests: `setAccessible()` removed

`ReflectionProperty::setAccessible()` and `ReflectionMethod::setAccessible()` are deprecated in PHP 8.5 and have had no effect since 8.1.

**Action:** Delete these calls from your own tests.

## 12. ORM no longer implements `Serializable`

`Serializable` is deprecated in PHP 8.1. `ORM::serialize()` and `ORM::unserialize()` are replaced by `__serialize(): array` and `__unserialize(array $data): void`.

**Action:**
- Models that override `serialize()`/`unserialize()` must override `__serialize()`/`__unserialize()` instead.
- ORM objects serialized before the upgrade (in sessions or cache) use the old `C:` format and cannot be restored. Clear sessions and cache that hold ORM objects when you deploy.

## 13. Removed modules and drivers

| Removed | Replacement |
|---|---|
| `codebench` module | None. Use a dedicated benchmarking tool such as PHPBench. |
| `userguide` module (`/guide` browser, `Kodoc`) | The guides are Markdown files in `docs/`. |
| `Cache_Memcache` driver (`ext-memcache`) | `Cache_Memcached` (`ext-memcached`), with the config driver `'memcached'`. |
| `Cache_Wincache` driver (`ext-wincache`) | `Cache_Apcu` or `Cache_File`. |

**Action:** Remove these modules from `Kohana::modules()` in `bootstrap.php`. Delete any `config/userguide.php` files from your own modules.

## 14. Classes use the `Kohana\` namespace (PSR-4)

Every framework class now lives under `Kohana\`, grouped like Laravel, and is autoloaded by Composer. The transparent extension layer is gone: there is no more `Kohana_Request` + `Request` pair, only `Kohana\Http\Request`. Old names have **no** aliases.

The full old → new list is in [`upgrade/class-map.php`](upgrade/class-map.php). The most used ones:

| Kohana 3.5 | Kohana 4 |
|---|---|
| `Kohana` | `Kohana\Foundation\Application` |
| `Kohana_Exception` | `Kohana\Foundation\KohanaException` |
| `Arr`, `Text`, `UTF8`, `Num`, `Date` | `Kohana\Support\Arr`, `Str`, `Utf8`, `Number`, `Date` |
| `Request`, `Response`, `HTTP_Header` | `Kohana\Http\Request`, `Response`, `HeaderBag` |
| `HTTP_Exception_404` | `Kohana\Http\Exceptions\Http404Exception` |
| `Route`, `URL`, `Controller` | `Kohana\Routing\Route`, `Url`, `Controller` |
| `View`, `HTML`, `Form` | `Kohana\View\View`, `Kohana\Html\Html`, `Form` |
| `Validation`, `Valid` | `Kohana\Validation\Validator`, `Rules` |
| `Config`, `I18n`, `Log`, `Session` | `Kohana\Config\Repository`, `Kohana\Translation\Translator`, `Kohana\Log\Logger`, `Kohana\Session\Session` |
| `Database`, `DB`, `ORM` | `Kohana\Database\Connection`, `Kohana\Database\DB`, `Kohana\Orm\Model` |
| `Cache`, `Cache_File` | `Kohana\Cache\Cache`, `Kohana\Cache\FileStore` |
| `Minion_Task` | `Kohana\Console\Command` |
| `Unittest_TestCase` | `Kohana\Testing\TestCase` |

### Rename your code automatically

`upgrade/rector.php` renames every class reference, type hint and docblock in your application:

```bash
composer require --dev rector/rector
vendor/bin/rector process --config vendor/kohana/kohana/upgrade/rector.php
```

Rector does not touch class names inside strings. Search your code for strings such as `'Kohana::$base_url'`, `'Valid'`, or `['Kohana', 'method']` callables, and replace them with `Application::class . '::$base_url'`, `Rules::class`, `[Application::class, 'method']`.

### Application classes use the `App\` namespace

`composer.json` maps `App\` to `application/classes/`.

| Kohana 3.5 | Kohana 4 | File |
|---|---|---|
| `Controller_Welcome` | `App\Http\Controllers\WelcomeController` | `classes/Http/Controllers/WelcomeController.php` |
| `Controller_Admin_User` (directory `admin`) | `App\Http\Controllers\Admin\UserController` | `classes/Http/Controllers/Admin/UserController.php` |
| `Model_User` | `App\Models\User` | `classes/Models/User.php` |
| `Model_Blog_Post` | `App\Models\BlogPost` | `classes/Models/BlogPost.php` |
| `Task_Db_Migrate` (`db:migrate`) | `App\Console\Commands\Db\MigrateCommand` | `classes/Console/Commands/Db/MigrateCommand.php` |

Routes stay the same: `'controller' => 'welcome'` resolves to `App\Http\Controllers\WelcomeController`. A route may also name a controller by its fully qualified class.

`Model::factory('blog_post')`, `ORM::factory('Blog_Post')` and the `'model'` key of ORM relationships resolve to `App\Models\BlogPost`. Pass a fully qualified class to use another namespace. ORM derives the object name from the class: `App\Models\BlogPost` → `blog_post` (table `blog_posts`), as before.

**Action:** Move each controller, model and task to its new namespace and file, and remove the `Controller_`, `Model_` and `Task_` prefixes.

### Drivers are resolved by namespace

Config `driver`/`type` values keep working and map to `{Name}{Component}` classes in the component's namespace:

| Config | Class |
|---|---|
| cache `'driver' => 'file'` | `Kohana\Cache\FileStore` |
| session `native`, `cookie`, `database` | `Kohana\Session\NativeSession`, `CookieSession`, `DatabaseSession` |
| database `'type' => 'PDO'` / `'MySQLi'` | `Kohana\Database\PdoConnection` / `MySqliConnection` |
| auth `'driver' => 'ORM'` / `'File'` | `Kohana\Auth\OrmAuth` / `FileAuth` |
| image `GD` / `Imagick` | `Kohana\Image\GdImage` / `ImagickImage` |
| encrypt `'driver' => 'openssl'` | `Kohana\Encryption\OpensslEncrypter` |

To use your own driver, set the value to a fully qualified class name, for example `'driver' => App\Cache\RedisStore::class`.

### Auth models

`Model_User`, `Model_Role` and `Model_User_Token` no longer ship with the ORM module. The ORM auth driver uses `App\Models\User` (and `Role`, `UserToken`) when your application defines them, otherwise `Kohana\Auth\Models\User` and friends. Extend them to add your own logic:

```php
namespace App\Models;

class User extends \Kohana\Auth\Models\User
{
}
```

### Bootstrap

- Remove the `require SYSPATH . 'classes/Kohana/Core.php'` and `classes/Kohana.php` lines. Composer autoloads the core.
- Change `spl_autoload_register(['Kohana', 'auto_load'])` to `spl_autoload_register([Application::class, 'auto_load'])`. It is still needed for un-namespaced classes found through the cascading filesystem.
- The `__()` translation function lives in `system/helpers.php`, loaded by Composer.

### Tests

The `unittest` module's custom suite loader (`Unittest_Tests`, `modules/unittest/tests.php`) is removed. Use a `phpunit.xml` that lists your test directories, with `modules/unittest/bootstrap.php` as the bootstrap. Test fixtures such as dummy controllers belong in a PSR-4 directory (`autoload-dev`), not at the bottom of a test file.

## 15. Methods and properties are camelCase

Every method and property name follows PSR-1 camelCase, and the leading underscore on protected members is gone:

| Kohana 3.5 | Kohana 4 |
|---|---|
| `$result->as_array()` | `$result->asArray()` |
| `$orm->find_all()`, `count_all()`, `has_any()` | `findAll()`, `countAll()`, `hasAny()` |
| `Kohana::find_file()` | `Application::findFile()` |
| `Kohana::$base_url`, `$index_file` | `Application::$baseUrl`, `$indexFile` |
| `$this->auto_render` | `$this->autoRender` |
| `protected $_data`, `$_table_name` | `protected $data`, `$tableName` |

The complete list of 255 methods and 197 properties is in [`upgrade/member-map.php`](upgrade/member-map.php).

### Template methods with new names

Some protected methods had an underscore twin of a public method. They now have their own names:

| Kohana 3.5 | Kohana 4 |
|---|---|
| `Minion_Task::_execute()` | `Command::handle()` |
| `Auth::_login()` | `Auth::attemptLogin()` |
| `Session::_read()`, `_write()`, `_regenerate()`, `_restart()`, `_destroy()` | `readSession()`, `writeSession()`, `regenerateSession()`, `restartSession()`, `destroySession()` |
| `Session::_serialize()`, `_unserialize()` | `serializeData()`, `unserializeData()` |
| `Kohana_Exception::_handler()` | `KohanaException::render()` |
| `Debug::_dump()` | `Debug::dumpVar()` |
| `ORM::_validation()` | `Model::initializeValidation()` |
| `Database::set_charset()` | `Connection::setCharset()` |

### Controller actions have no prefix

`action_index()` is now `index()`. The route's `action` parameter is converted to camelCase, so the URL `/user/edit_profile` calls `editProfile()`.

Only public, non-static methods declared in your controller (not in `Kohana\Routing\Controller` or `TemplateController`) can be called from a URL, so `before()`, `after()` and `execute()` are never routable. **Make helper methods in your controllers `protected` or `private`**, otherwise they become reachable as actions.

### What does not change

- Validation rule strings stay snake_case: `->rule('name', 'not_empty')`, `'max_length'`. They map to `Rules::notEmpty()`, `Rules::maxLength()`, and message files keep their snake_case keys. An array callback such as `[Rules::class, 'maxLength']` reports the error `max_length`.
- Request client parameters stay snake_case: `Request::factory($uri, ['follow_headers' => true])`.
- ORM column names are untouched. Inside a model's own methods, `$this->name` reads a declared property before a column, and the underscore no longer separates them. Avoid columns named after model properties (`db`, `object`, `changes`, `related`, `loaded`, `saved`, `valid`, `validation`, `sorting`, ...) or read them with `$this->get('name')`, the same caveat as Eloquent's reserved attributes.

### Rename your code automatically

Run this after `upgrade/rector.php`:

```bash
composer require --dev nikic/php-parser:^5
php vendor/kohana/kohana/upgrade/rename-members.php application modules/my-module
```

It renames declarations, calls, `[$object, 'method']` callables, `'Class::method'` strings and `action_*` methods. Public properties are only renamed on `$this`, because `$model->content_type` may be an ORM column; check other accesses by hand. Strings built at runtime (for example `'_' . $name`) are not found by any tool.

## 16. PHP 8.4+, Pest 5 and PHPUnit 13

`composer.json` now requires `"php": ">=8.4"`. The test suite runs on Pest 5 (PHPUnit 13), which needs PHP 8.4.

The framework's tests moved from `system/tests` and `modules/*/tests` to a root `tests/` directory grouped by component (`tests/System`, `tests/Cache`, `tests/Database`, `tests/Orm`, ...). Test data lives in `tests/Fixtures`, fixture classes (dummy controllers and models) in `tests/Fixtures/App` (`autoload-dev`). Run the suite with `composer test` or `vendor/bin/pest`.

**Action for your own tests:**
- Replace docblock annotations with attributes: `@dataProvider` → `#[DataProvider]`, `@test` → `#[Test]`, `@group` → `#[Group]`. Rector's PHPUnit sets do this: `->withComposerBased(phpunit: true)->withAttributesSets(phpunit: true)`.
- Data providers must be `public static`.
- `getMockForAbstractClass()`, `setMethods()`, `$this->returnValue()` and `$this->at()` are gone. `Kohana\Testing\TestCase::mockAbstract($class, $constructorArgs, $extraMethods)` replaces `getMockForAbstractClass()`. Use `onlyMethods()`, `willReturn()` and `willReturnCallback()` for the rest.
- PHPUnit cannot mock a class that has a method named `method()`, such as `Kohana\Http\Request`. Use a real `new Request($uri)`; its setters (`directory()`, `controller()`, `action()`) make it easy to configure.
- `Kohana\Testing\TestCase` extends PHPUnit's `TestCase` directly. `assertSelectEquals()` is still available through `phpunit/phpunit-dom-assertions`.

## 17. New: PostgreSQL driver

The database module has a PostgreSQL driver, `Kohana\Database\PgsqlConnection` (type `'pgsql'`, needs `ext-pdo_pgsql`). It supports `listTables()` and `listColumns()`, so ORM works on PostgreSQL. See [`docs/database/config.md`](docs/database/config.md#postgresql) for the options.

If you used the generic `PDO` type with a `pgsql:` DSN, switch to `'type' => 'pgsql'`. You can drop the `'identifier' => '"'` option. ORM did not work with the generic `PDO` type because it could not list columns; it does now.

An unknown `type` now throws `DatabaseException('Unknown database type ...')` instead of a PHP warning.

## 18. New: Redis cache store

The cache module has a Redis store, `Kohana\Cache\RedisStore` (driver `'redis'`, needs `ext-redis`; works with Valkey too). It supports increment/decrement and tags, and keeps its keys under a configurable prefix so `deleteAll()` does not touch other applications sharing the server. See [`docs/cache/config.md`](docs/cache/config.md#redis-settings).

## 19. Passwords are hashed with `password_hash()`

Kohana 3's Auth module stored passwords as `hash_hmac('sha256', $password, $hash_key)`: one key for every user, no per-user salt and a fast hash. Kohana 4 uses PHP's `password_hash()` (Argon2id, or bcrypt when PHP has no Argon2 support) and compares hashes in constant time.

**Existing users keep working.** `Auth::verify()` still accepts the old HMAC hashes as long as `hash_method` and `hash_key` stay in your auth config, and the ORM driver replaces an old hash with a new one when the user logs in.

**Action:**
1. Widen the password column; Argon2id hashes are about 100 characters:

        ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL;          -- MySQL
        ALTER TABLE users ALTER COLUMN password TYPE VARCHAR(255);         -- PostgreSQL

2. Keep `hash_key` in `config/auth.php` until your users have logged in once. Users who never log in keep their old hash; force a password reset for them before you remove the key.
3. File driver: replace the hashes in the `users` array with `password_hash()` output. The File driver cannot rewrite its config, so it does not upgrade hashes itself.
4. If you called `Auth::hash($password) === $stored` yourself, use `Auth::instance()->verify($password, $stored)`; a new hash is different every time.

`FileAuth::checkPassword()` also works now. It used to compare the plain text password with the stored hash and always returned `false`.

## 20. PHP 5 leftovers removed

`register_globals`, `magic_quotes_gpc` and `safe_mode` were removed from PHP in 5.4. Their handling is gone:

| Removed | Replacement |
|---|---|
| `Application::$magicQuotes` | None. `Application::sanitize()` no longer strips slashes; it only normalizes newlines. |
| `Application::$safeMode` | None. |
| `Application::globals()` | None. |

**Action:** Remove any reference to these from your code.

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

`composer.json` maps `App\` to `app/`.

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
php vendor/kohana/kohana/upgrade/rename-members.php app modules/my-module
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

## 21. Project layout

The `application/` directory is split into a Laravel-style project layout. Classes live directly in `app/`, everything else moves to the project root. See [`docs/kohana/structure.md`](docs/kohana/structure.md).

| Kohana 3.5 | Kohana 4 |
|---|---|
| `application/classes/` | `app/` (`App\` namespace) |
| `application/bootstrap.php` | `bootstrap/app.php` |
| routes in the bootstrap | `routes/web.php` |
| `application/config/` | `config/` |
| `application/views/` | `resources/views/` |
| `application/messages/` | `resources/messages/` |
| `application/i18n/` | `lang/` |
| `application/cache/` | `storage/framework/cache/` |
| `application/logs/` | `storage/logs/` |
| (new) | `database/migrations/`, `database/seeders/`, `storage/app/`, `bootstrap/cache/` |

Modules and the system keep their own layout (`config/`, `views/`, `messages/`, `i18n/`, `migrations/`); only the application layer of the cascading filesystem uses the new directories. `Application::findFile('views', 'welcome')` finds `resources/views/welcome.php` first, then module and system views as before. Kohana does not look in the old directories.

The paths are defined once in `bootstrap/paths.php`: `BASEPATH` (new, the project root), `APPPATH` (now `app/`), `MODPATH`, `SYSPATH`, `DOCROOT`, `VENDOR_PATH`. New helpers return paths inside them: `base_path()`, `app_path()`, `config_path()`, `database_path()`, `lang_path()`, `resource_path()`, `storage_path()`, `public_path()`.

**Action:**

```bash
mkdir -p bootstrap/cache database/migrations database/seeders resources routes storage/app storage/framework/sessions
git mv application/classes app            # then move app/* up into app/ if a classes/ directory remains
git mv application/bootstrap.php bootstrap/app.php
git mv application/config config
git mv application/views resources/views
git mv application/messages resources/messages
git mv application/i18n lang
git mv application/cache storage/framework/cache
git mv application/logs storage/logs
```

1. Copy `bootstrap/paths.php`, `public/index.php` and `kohana` from the framework, and compare your `bootstrap/app.php` with the framework's: it now starts with `require_once __DIR__ . '/paths.php';`, logs to `storage_path('logs')`, and ends with `require BASEPATH . 'routes/web.php';`. Move your `Route::set()` calls to `routes/web.php`.
2. In `composer.json`, map `"App\\": "app/"` and `"Database\\Seeders\\": "database/seeders/"`, add `"post-autoload-dump": ["@php kohana package:discover"]` to `scripts`, then run `composer dump-autoload`.
3. Replace `APPPATH . 'cache'` with `storage_path('framework/cache')` and `APPPATH . 'logs'` with `storage_path('logs')`, and any other `APPPATH . 'views'` style paths with the new directories.
4. Make `storage/` and `bootstrap/cache/` writable by the web server, and update deploy scripts, web server rules and `.gitignore` entries.

### Composer packages

Kohana modules installed with Composer are discovered from their `composer.json` (`"extra": {"kohana": {"module": "name"}}`) and enabled after the modules you list in `bootstrap/app.php`. Packages can also register console commands. See [`docs/kohana/modules.md`](docs/kohana/modules.md#modules-installed-with-composer).

## 22. Environment variables and `.env`

Kohana loads a `.env` file from the project root with [phpdotenv](https://github.com/vlucas/phpdotenv), and the `env()` helper reads its values. Real environment variables take precedence over the file. See [`docs/kohana/environment.md`](docs/kohana/environment.md).

What changed:

- `bootstrap/app.php` loads `.env`, sets the environment from `APP_ENV` (`KOHANA_ENV` still works, and `local` means development), `base_url` from `APP_BASE_URL` (default `/`), and `Cookie::$salt` from `COOKIE_SALT`.
- The database module's `default` connection now reads `DB_CONNECTION` (default `mysql`, which means the MySQLi driver), `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DSN`, `DB_PREFIX` and `DB_CHARSET`. Before, it was a generic `PDO` connection to `mysql:host=localhost;dbname=kohana`, and the module also shipped a `mysqli` sample group, which is gone.
- `config/encrypt.php` reads its `key` from `APP_KEY`. Keys starting with `base64:` are decoded.
- `config/auth.php` reads the legacy `hash_key` from `AUTH_HASH_KEY`.
- `'mysql'` is accepted as a database type and means MySQLi.
- New command: `php kohana key:generate` writes a random `APP_KEY` and `COOKIE_SALT` to `.env`.

**Action:**
1. Copy the new `bootstrap.php` lines (the `Dotenv` call after the Composer autoloader, `APP_ENV`, `APP_BASE_URL` and `COOKIE_SALT`) into your `bootstrap/app.php`.
2. `cp .env.example .env` and move your secrets (database password, encryption key, cookie salt, old auth `hash_key`) into it. Keep your **existing** encryption key and cookie salt; do not generate new ones for an application that already has data and users.
3. If your app relied on the module's default `PDO` connection or its `mysqli` group without overriding them, define them in `config/database.php` or set `DB_*` variables.
4. Add `.env` to `.gitignore`.

## 23. The `kohana` console

A `kohana` script in the project root runs console commands, like Laravel's `artisan`: `php kohana route:list`. It loads the Minion module by itself. `php public/index.php --task=...` still works.

New commands: `make:controller`, `make:model`, `make:command`, `route:list`, `cache:clear` and `serve`. `php kohana` lists every command with the first line of its docblock. See [`docs/minion/index.md`](docs/minion/index.md).

What changed for your own commands:

- Values after the command name are **arguments** (`$this->argument(0)`), not options. Before, `php public/index.php --task=report extra` passed `extra` as an option and failed validation.
- Unknown options are rejected even when given without a value (`--nope`). Before, such flags slipped through validation.
- `Cli::write()` and `Cli::writeReplace()` echo their output instead of writing to `STDOUT` directly, so output buffering (and tests) can capture it.

**Action:** copy `kohana` from the framework into your project root.

## 24. New: migrations, schema builder and seeders

The database module now has a schema builder (`Kohana\Database\Schema\Schema`), migrations (`Kohana\Database\Migrations\Migration`, run by `Migrator`) and seeders (`Kohana\Database\Seeder`), with the console commands `make:migration`, `migrate`, `migrate:rollback`, `migrate:status`, `make:seeder` and `db:seed`. Migrations live in `database/migrations` (and modules' `migrations/` directories), seeders in `Database\Seeders`. See [`docs/database/migrations.md`](docs/database/migrations.md) and [`docs/database/seeding.md`](docs/database/seeding.md).

**To adopt migrations in an existing application**, create a first migration that describes the current schema, then mark it as run on databases that already have the tables instead of running it there:

```bash
php kohana make:migration create_initial_schema    # fill in up() and down()
php kohana migrate:status                           # creates the migrations table
```

```sql
INSERT INTO migrations (migration, batch) VALUES ('2026_11_21_120000_create_initial_schema', 1);
```

Related fixes in the database module:

- **MySQLi errors throw `DatabaseException` again.** Since PHP 8.1 mysqli throws `mysqli_sql_exception` by default, which escaped Kohana's error handling; `catch (DatabaseException $e)` around a failing query did not catch anything. The original exception is kept as the previous exception.
- `MySqliConnection::listColumns()` no longer fails on column types it does not know, such as `JSON`; they are reported as strings. ORM models on such tables work.
- The generic `PDO` driver supports `listTables()` for MySQL, PostgreSQL and SQLite.
- `Application::$environment` is documented as the integer constant it always was.

## 25. Console flags, version and auth tables migration

- `Application::VERSION` is `4.0.0-dev`. `php kohana -V` (or `--version`, `version`) prints it; `-h` and `--help` list the commands. Other arguments starting with `-` before the command name are rejected instead of being taken as a command name.
- Console errors end with a newline and exit with status 1 when the exception code is not a valid exit status. Before, a PDO error such as `42P07` was printed after the message instead of being used as the exit status.
- `php kohana auth:migration` copies a migration for the ORM auth tables (`users`, `roles`, `roles_users`, `user_tokens`, plus the `login` and `admin` roles) into `database/migrations`. It is not run automatically, so enabling the ORM module never creates tables you did not ask for. Applications that already created these tables from `auth-schema-*.sql` should not run it.

## 26. All settings live in `config/`

Kohana 3 spread its settings over `system/config`, each module's `config/` directory and the application's. Kohana 4 keeps every setting in the project's `config/` directory, reading environment-specific values with `env()`. Modules and the system no longer ship copies; the framework keeps only reference data (`mimes`, `inflector`, `user_agents`, `credit_cards`) in `system/config`. See [`docs/kohana/environment.md`](docs/kohana/environment.md).

New and changed files:

- `config/app.php` (new) holds what `bootstrap/app.php` used to hard-code: environment, base URL, index file, time zone, locale, error handling, caching, profiling and the list of enabled modules. The bootstrap reads it.
- `config/cookie.php` (new) sets `Cookie::$salt`, `$expiration`, `$path`, `$domain`, `$secure` and `$httponly`. `httponly` now defaults to `true`.
- `config/session.php` merges the system's and the database module's session files and adds `driver`, the default session driver. `Session::$default` is `null` by default and falls back to it.
- `config/cache.php` lists all stores and adds `default`, the default store. `Cache::$default` is `null` by default and falls back to it.
- `config/database.php`, `encrypt.php`, `auth.php`, `image.php`, `url.php` and `curl.php` read their environment-specific values from `env()`.
- `modules/unittest/config/unittest.php` is removed; its code coverage settings no longer did anything.
- The `config('file.path', $default)` helper reads configuration values.

**Action:**
1. Copy the framework's `config/` files into your project and move your values into them (or into `.env`). Delete your copies of the old module and system files.
2. Move the settings at the top of your `bootstrap/app.php` (time zone, locale, `Application::init()` options, `Application::modules()` list, `Cookie::$salt`) into `config/app.php`, `config/cookie.php` and `.env`, and take the new `bootstrap/app.php` from the framework.
3. If you set `Session::$default` or `Cache::$default` in code, you can move the value to `SESSION_DRIVER` and `CACHE_STORE`.

## 27. Sessions

- **Redis driver.** `SESSION_DRIVER=redis` stores sessions in Redis or Valkey through a Redis store of the cache module (`config/session.php` → `redis`).
- **Native sessions are stored in `storage/framework/sessions`** (the new `save_path` setting) instead of PHP's temp directory. On the command line, where output has already started, the native driver no longer tries to change headers and warns.
- **The database driver creates random session ids** (`random_bytes()`, 40 characters) instead of `uniqid()`, which was predictable. Its table has a migration: `php kohana session:migration`.
- **`session()` helper**: `session('key')`, `session(['key' => $value])`, `session()->regenerate()`.
- Drivers send and delete their cookie through `Session::sendCookie()` and `deleteCookie()`, and check `headersSent()`; override them in custom drivers or tests.

**Action:**
1. If you use the database driver, widen `session_id`; the old schema made it `VARCHAR(24)`:

        ALTER TABLE sessions MODIFY session_id VARCHAR(64) NOT NULL;         -- MySQL
        ALTER TABLE sessions ALTER COLUMN session_id TYPE VARCHAR(64);       -- PostgreSQL

2. Make `storage/framework/sessions` writable, or set `save_path` to `null` in `config/session.php` to keep PHP's default.
3. Existing native sessions in the old location are not moved; users are logged out once.

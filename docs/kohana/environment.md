# Environment Configuration

Settings that differ between machines (database passwords, keys, the base URL) belong in a `.env` file in the project root, not in your config files. Kohana loads it with [phpdotenv](https://github.com/vlucas/phpdotenv) at the start of `bootstrap/app.php`.

    cp .env.example .env
    php kohana key:generate

Keep `.env` out of version control; it is already in `.gitignore`. Commit `.env.example` with every variable your application needs and no secrets.

[!!] Variables set by the web server or the shell take precedence over `.env`. In production you can skip the file entirely and set real environment variables.

## Reading values

Use the `env()` helper in config files:

    'password' => env('DB_PASSWORD', ''),
    'debug' => env('APP_DEBUG', false),

The second argument is the default when the variable is not set. The strings `true`, `false`, `null` and `empty` become `true`, `false`, `null` and `''`, and surrounding quotes are removed.

[!!] Only call `env()` from configuration files and the bootstrap. Everywhere else read the configuration (`Application::$config->load('database')`), so that values can also come from other config sources.

## Configuration files

Every setting lives in the project's `config/` directory, and the ones that differ between environments read `.env` through `env()`:

| File | Settings | Variables |
|---|---|---|
| `config/app.php` | environment, base URL, index file, time zone, locale, error handling, caching, profiling, enabled modules | `APP_ENV`, `APP_BASE_URL`, `APP_INDEX_FILE`, `APP_TIMEZONE`, `APP_LOCALE`, `APP_SYSTEM_LOCALE`, `APP_ERRORS`, `APP_CACHING`, `APP_PROFILE` |
| `config/cookie.php` | cookie signing and attributes | `COOKIE_SALT`, `COOKIE_EXPIRATION`, `COOKIE_PATH`, `COOKIE_DOMAIN`, `COOKIE_SECURE`, `COOKIE_HTTPONLY` |
| `config/encrypt.php` | encryption key and cipher | `APP_KEY` (`base64:` values are decoded), `APP_CIPHER` |
| `config/url.php` | trusted host names | `APP_TRUSTED_HOSTS` (comma separated patterns) |
| `config/database.php` | the `default` connection | `DB_CONNECTION` (`mysql`, `pgsql`, `PDO` or a class), `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DSN`, `DB_PREFIX`, `DB_CHARSET` |
| `config/session.php` | default driver and per-driver settings | `SESSION_DRIVER` (`native`, `cookie`, `database`, `redis`), `SESSION_NAME`, `SESSION_LIFETIME`, `SESSION_TTL`, `SESSION_STORE`, `SESSION_CONNECTION` |
| `config/cache.php` | default store and stores | `CACHE_STORE`, `CACHE_PREFIX`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_CACHE_DB`, `MEMCACHED_HOST`, `MEMCACHED_PORT` |
| `config/auth.php` | auth driver and password hashing | `AUTH_DRIVER`, `AUTH_LIFETIME`, `AUTH_HASH_KEY` (Kohana 3 hashes only) |
| `config/image.php` | default image driver | `IMAGE_DRIVER` (`GD` or `Imagick`) |
| `config/curl.php` | options of the external HTTP client | `HTTP_CLIENT_TIMEOUT` |

`KOHANA_ENV` still works when `APP_ENV` is not set. Without either, the environment is `development`.

Modules and the system no longer ship copies of these files. The framework keeps only reference data in `system/config` (`mimes`, `inflector`, `user_agents`, `credit_cards`); copy one to `config/` to change it, and Kohana merges the two.

## Reading configuration

Read settings with the `config()` helper, using the file name and a dot path:

    config('app.timezone');
    config('database.default.connection.hostname');
    config('cache.redis.port', 6379);   // with a default
    config('session');                  // the whole file

`Application::$config->load('database')` still works and returns the same group.

## Generating keys

`key:generate` (Minion module) writes a random `APP_KEY` and `COOKIE_SALT` to `.env`, creating it from `.env.example` when needed:

    php kohana key:generate          # fill empty values
    php kohana key:generate --show   # print, don't write
    php kohana key:generate --force  # replace existing values

[!!] A new `APP_KEY` makes data encrypted with the old key unreadable, and a new `COOKIE_SALT` invalidates every signed cookie (users are logged out). That is why existing values are kept unless you pass `--force`.

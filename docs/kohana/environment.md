# Environment Configuration

Settings that differ between machines (database passwords, keys, the base URL) belong in a `.env` file in the project root, not in your config files. Kohana loads it with [phpdotenv](https://github.com/vlucas/phpdotenv) at the start of `app/bootstrap.php`.

    cp .env.example .env
    php kohana key:generate

Keep `.env` out of version control; it is already in `.gitignore`. Commit `.env.example` with every variable your application needs and no secrets.

[!!] Variables set by the web server or the shell take precedence over `.env`. In production you can skip the file entirely and set real environment variables.

## Reading values

Use the `env()` helper in config files and the bootstrap:

    'password' => env('DB_PASSWORD', ''),
    'debug' => env('APP_DEBUG', false),

The second argument is the default when the variable is not set. The strings `true`, `false`, `null` and `empty` become `true`, `false`, `null` and `''`, and surrounding quotes are removed.

[!!] Only call `env()` from configuration files and the bootstrap. Everywhere else read the configuration (`Application::$config->load('database')`), so that values can also come from other config sources.

## Variables Kohana uses

| Variable | Used by | Meaning |
|---|---|---|
| `APP_ENV` | bootstrap | `production`, `staging`, `testing` or `development` (`local` = development). `KOHANA_ENV` still works. |
| `APP_BASE_URL` | bootstrap | Path, and optionally domain, the application is served from. Default `/`. |
| `APP_KEY` | `config/encrypt.php` | Encryption key. `base64:` values are decoded. |
| `COOKIE_SALT` | bootstrap | Salt for signed cookies. |
| `DB_CONNECTION` | `config/database.php` | `mysql`, `pgsql`, `PDO` or a driver class name. Default `mysql`. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `config/database.php` | Connection settings of the `default` group. |
| `DB_DSN` | `config/database.php` | Data source name, only for `DB_CONNECTION=PDO`. |
| `DB_PREFIX`, `DB_CHARSET` | `config/database.php` | Table prefix and character set. |
| `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | `config/cache.php` | Redis cache store (when you enable the `redis` group). |
| `AUTH_HASH_KEY` | `config/auth.php` | Only to verify passwords stored by Kohana 3. |

## Generating keys

`key:generate` (Minion module) writes a random `APP_KEY` and `COOKIE_SALT` to `.env`, creating it from `.env.example` when needed:

    php kohana key:generate          # fill empty values
    php kohana key:generate --show   # print, don't write
    php kohana key:generate --force  # replace existing values

[!!] A new `APP_KEY` makes data encrypted with the old key unreadable, and a new `COOKIE_SALT` invalidates every signed cookie (users are logged out). That is why existing values are kept unless you pass `--force`.

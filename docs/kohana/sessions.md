# Sessions

Kohana provides classes that make it easy to work with both cookies and sessions. At a high level both sessions and cookies provide the same functionality. They allow the developer to store temporary or persistent information about a specific client for later retrieval, usually to make something persistent between requests.

Sessions should be used for storing temporary or private data. Very sensitive data should be stored using the `Session` class with the "database" or "native" adapters. When using the "cookie" adapter, the session should always be encrypted.

[!!] For more information on best practices with session variables see [the seven deadly sins of sessions](http://lists.nyphp.org/pipermail/talk/2006-December/020358.html).

## Storing, Retrieving, and Deleting Data

`Cookie` and `Session` provide a very similar API for storing data. The main difference between them is that sessions are accessed using an object, and cookies are accessed using a static class.

Accessing the session instance is done using the `Session::instance()` method, or the `session()` helper:

    // Get the session instance
    $session = Session::instance();

    // The helper reads and writes the default session directly
    session('user_id');                  // get
    session('user_id', 0);               // get with a default
    session(['user_id' => 42]);          // set one or more values
    session()->regenerate();             // the Session object

When using sessions, you can also get all the current session data using the `Session::asArray()` method:

    // Get all the session data as an array
    $data = $session->asArray();

You can also use this to overload the `$_SESSION` global to get and set data in a way more similar to standard PHP:

    // Overload $_SESSION with the session data
    $_SESSION =& $session->asArray();

    // Set session data
    $_SESSION[$key] = $value;

### Storing Data

Storing session or cookie data is done using the `set` method:

    // Set session data
    $session->set($key, $value);
    // Or
    Session::instance()->set($key, $value);

    // Store a user id
    $session->set('user_id', 10);

### Retrieving Data

Getting session or cookie data is done using the `get` method:

    // Get session data
    $data = $session->get($key, $default_value);

    // Get the user id
    $user = $session->get('user_id');

### Deleting Data

Deleting session or cookie data is done using the `delete` method:

    // Delete session data
    $session->delete($key);


    // Delete the user id
    $session->delete('user_id');

## Session Configuration

Sessions are configured in `config/session.php`, mostly through `.env`. Always check these settings before making your application live, as many of them have a direct effect on its security.

| Variable | Setting |
|---|---|
| `SESSION_DRIVER` | default driver: `native`, `cookie`, `database` or `redis` |
| `SESSION_NAME` | cookie name |
| `SESSION_LIFETIME` | cookie lifetime in seconds; `0` lasts until the browser closes |
| `SESSION_TTL` | seconds Redis keeps an idle session |
| `SESSION_STORE` | cache store (in `config/cache.php`) of the Redis driver |
| `SESSION_CONNECTION` | database group of the database driver |

`Session::instance()` uses the default driver; `Session::instance('redis')` picks another one. Setting `Session::$default` in code overrides `SESSION_DRIVER`.

## Session Drivers

Native
: PHP's own session handling. Files are stored in `storage/framework/sessions` (the `save_path` setting).

Cookie
: Stores the data in a signed cookie. **Cookies are limited to 4KB, and the data should be encrypted** (`'encrypted' => true`).

Database
: Stores the data in a table (database module). Create it with `php kohana session:migration` and `php kohana migrate`.

Redis
: Stores the data in Redis or Valkey (cache module, `ext-redis`), reusing a Redis store from `config/cache.php`. Only the session id travels in the cookie.

The database and Redis drivers create session ids with `random_bytes()` (40 hexadecimal characters) and drop the old id when the session is regenerated.

[!!] Call `$session->regenerate()` after a user logs in, so an id known before the login (session fixation) stops working. The auth module does this for you.

### Driver Settings

    // config/session.php
    return [
        'driver' => env('SESSION_DRIVER', 'native'),

        'native' => [
            'name' => 'session',
            'lifetime' => 0,
            'save_path' => storage_path('framework/sessions'),
        ],
        'cookie' => [
            'name' => 'session',
            'encrypted' => true,
            'lifetime' => 0,
        ],
        'database' => [
            'name' => 'session',
            'lifetime' => 0,
            'group' => 'default',
            'table' => 'sessions',
            'columns' => [
                'session_id' => 'session_id',
                'last_active' => 'last_active',
                'contents' => 'contents',
            ],
            'gc' => 500,
        ],
        'redis' => [
            'name' => 'session',
            'lifetime' => 0,
            'ttl' => 7200,
            'store' => 'redis',
            'prefix' => 'session:',
        ],
    ];

Every driver accepts `name`, `lifetime` and `encrypted` (encrypt the data with `Encrypter`; `true` uses the `default` encryption group).

| Driver | Setting | Description | Default |
|---|---|---|---|
| native | `save_path` | directory of the session files | PHP's `session.save_path` |
| database | `group` | database group | `default` |
| database | `table` | table name | `sessions` |
| database | `columns` | column names, for an existing table | `session_id`, `last_active`, `contents` |
| database | `gc` | 1:x chance of deleting expired sessions on a request | `500` |
| redis | `store` | Redis store in `config/cache.php` | `redis` |
| redis | `prefix` | key prefix, after the store's own prefix | `session:` |
| redis | `ttl` | seconds an idle session is kept | `lifetime`, else `session.gc_maxlifetime` |

### Database Table

`php kohana session:migration` copies a migration that creates the table:

    Schema::create('sessions', function (Blueprint $table) {
        $table->string('session_id', 64)->primary();
        $table->unsignedInteger('last_active')->index();
        $table->longText('contents');
    });

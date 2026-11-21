# Configuration

The module's `config/database.php` reads the `default` connection from the environment: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` (see [Environment Configuration](../kohana/environment)). Most applications only need to set these in `.env`. Copy the file to `app/config/database.php` when you need more connections.

The default config file is located in `MODPATH/database/config/database.php`. You should copy this file to `APPPATH/config/database.php` and make changes there, in keeping with the [cascading filesystem](../kohana/files).

The database configuration file contains an array of configuration groups. The structure of each database configuration group, called an "instance", looks like this:

    string INSTANCE_NAME => [
        'type' => string DATABASE_TYPE,
        'connection' => array CONNECTION_ARRAY,
        'table_prefix' => string TABLE_PREFIX,
        'charset' => string CHARACTER_SET,
    ],


Understanding each of these settings is important.

INSTANCE_NAME
:  Connections can be named anything you want, but you should always have at least one connection called "default".

DATABASE_TYPE
:  One of the installed database drivers. Kohana comes with "PDO", "MySQLi" and "pgsql" (PostgreSQL) drivers. A custom driver is given by its fully qualified class name and must extend `Kohana\Database\Connection`.

CONNECTION_ARRAY
:  Specific driver options for connecting to your database. (Driver options are explained [below](#connection-settings).)

TABLE_PREFIX
:  Prefix that will be added to all table names by the [query builder](query/builder).

CHARACTER_SET
:  The character set to use for the connection with the database.

## Example

The example file below shows 2 MySQL connections, one local and one remote.

    return [
        'default' => [
            'type' => 'PDO',
            'connection' => [
                'dsn' => 'mysql:host=localhost;dbname=my_db_name',
                'username' => 'dbuser',
                'password' => 'mypassword',
                'persistent' => false,
            ],
            'table_prefix' => '',
            'charset' => 'utf8',
        ],
        'remote' => [
            'type' => 'PDO',
            'connection' => [
                'dsn' => 'mysql:host=55.55.55.55;dbname=my_remote_db_name',
                'username' => 'remote_user',
                'password' => 'mypassword',
                'persistent' => false,
                'database' => 'my_remote_db_name',
            ],
            'table_prefix' => '',
            'charset' => 'utf8',
        ],
    ];

[!!] The 'type' parameter is one of 'PDO', 'MySQLi' or 'pgsql' (case does not matter), or the fully qualified class name of your own driver.

## Connections and Instances

Each configuration group is referred to as a database instance. Each instance can be accessed by calling `Connection::instance()`. If you don't provide a parameter, the default instance is used.

    // This would connect to the database defined as 'default'
    $default = Connection::instance();

    // This would connect to the database defined as 'remote'
    $remote  = Connection::instance('remote');

To disconnect the database, simply destroy the object:

    unset($default);

    // Or

    unset(Connection::$instances['default']);

If you want to disconnect all the database instances at once:

    Connection::$instances = [];

## Connection Settings

Every database driver has different connection settings.

### PDO

A [PDO database](https://www.php.net/manual/en/book.pdo.php) can accept these options in the `connection` array:

| Type      | Option     | Description                | Default value                        |
|-----------|------------|----------------------------|--------------------------------------|
| `string`  | dsn        | PDO data source identifier | `mysql:host=localhost;dbname=kohana` |
| `string`  | username   | Database username          | empty                                |
| `string`  | password   | Database password          | empty                                |
| `array`   | options    | Driver-specific options    | none                                 |
| `boolean` | persistent | Persistent connections     | `false`                              |

The connection character set should be configured using the DSN string or `options` array.

[!!] If you are using PDO and are not sure what to use for the `dsn` option, review [PDO::__construct](https://www.php.net/pdo.construct).

### MySQLi

A [MySQLi database](https://www.php.net/manual/en/book.mysqli.php) can accept the following options in the `connection` array:

| Type      | Option     | Description              | Default value |
|-----------|------------|--------------------------|---------------|
| `string`  | hostname   | Hostname of the database | `localhost`   |
| `string`  | username   | Database username        | empty         |
| `string`  | password   | Database password        | empty         |
| `string`  | database   | Database name            | `kohana`      |
| `integer` | port       | Port number              | `3306`        |
| `string`  | socket     | Unix domain socket       | `null`        |
| `integer` | flags      | Connection options       | `null`        |
| `array`   | ssl        | SSL parameters           | `null`        |
| `array`   | variables  | Session variables        | `null`        |

The `flags` option can be used to specify connection options. See [MySQLi::real_connect](https://www.php.net/mysqli.real-connect) for more information.

SSL parameters should be specified as `key` => `value` pairs.
Available keys: client_key_path, client_cert_path, ca_cert_path, ca_dir_path, cipher
### PostgreSQL

The `pgsql` type uses [PDO PostgreSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php) (`ext-pdo_pgsql`). Give either a `dsn` or the separate options in the `connection` array:

| Type      | Option     | Description                | Default value |
|-----------|------------|----------------------------|---------------|
| `string`  | hostname   | Hostname of the database   | none (socket) |
| `integer` | port       | Port number                | `5432`        |
| `string`  | database   | Database name              | none          |
| `string`  | username   | Database username          | empty         |
| `string`  | password   | Database password          | empty         |
| `string`  | sslmode    | `disable`, `require`, ...  | none          |
| `string`  | dsn        | Full PDO DSN; overrides the options above | none |
| `boolean` | persistent | Persistent connections     | `false`       |

Two more options sit next to `connection`:

| Type     | Option  | Description                                  |
|----------|---------|----------------------------------------------|
| `string` | charset | Client encoding, for example `UTF8`          |
| `string` | schema  | Sets `search_path` after connecting          |

    'pgsql' => [
        'type' => 'pgsql',
        'connection' => [
            'hostname' => '127.0.0.1',
            'port' => 5432,
            'database' => 'kohana',
            'username' => 'postgres',
            'password' => '',
        ],
        'table_prefix' => '',
        'charset' => 'UTF8',
    ],

Unlike the generic `PDO` type, the PostgreSQL driver supports `listTables()` and `listColumns()`, so ORM models work without declaring their columns. Identifiers are quoted with double quotes. Tables without a `SERIAL` or identity column return `null` as the insert ID.

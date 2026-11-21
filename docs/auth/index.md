# Auth

User authentication and authorization is provided by the auth module.

The auth module is included with Kohana, but needs to be enabled before you can use it. To enable, open your `app/bootstrap.php` file and modify the call to `Application::modules()` by including the auth module like so:

~~~
Application::modules([
    // ...
    'auth' => MODPATH . 'auth',
    // ...
]);
~~~

Next, you will then need to [configure](config) the auth module.

The auth module provides the `File` driver for you. There is also an `ORM` driver included with the ORM module, which stores users in the database.

## Database tables for the ORM driver

The ORM driver needs `users`, `roles`, `roles_users` and `user_tokens` tables. With the database and ORM modules enabled, copy the ready-made migration into your application and run it:

    php kohana auth:migration
    php kohana migrate

The migration also creates the `login` and `admin` roles; a user needs the `login` role to log in. If you do not use migrations, `modules/orm/auth-schema-mysql.sql` and `auth-schema-postgresql.sql` contain the same tables as SQL.

Extend the auth models to add your own logic, see [Upgrading](../../UPGRADE.md) section 14:

    namespace App\Models;

    class User extends \Kohana\Auth\Models\User
    {
    }

As your application needs change you may need to find another driver or [develop](driver/develop) your own.

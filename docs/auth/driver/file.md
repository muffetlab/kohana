# File Driver

The [Auth::File] driver is included with the auth module.

Below are additional configuration options that can be set for this driver.

| Name  | Type    | Default | Description                                                              |
|-------|---------|---------|--------------------------------------------------------------------------|
| users | `array` | []      | A user => password hash array of all the users in your application |

## Forcing Login

`FileAuth::forceLogin()` allows you to force a user login without a password.

~~~
// Force the user with a username of admin to be logged into your application
Auth::instance()->forceLogin('admin');
$user = Auth::instance()->getUser();
~~~

Create a hash for the `users` array with `password_hash()`:

    php -r "echo password_hash('secret', PASSWORD_ARGON2ID), PHP_EOL;"

    'users' => [
        'admin' => '$argon2id$v=19$m=65536,t=4,p=1$...',
    ],

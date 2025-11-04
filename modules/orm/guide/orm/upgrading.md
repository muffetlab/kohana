# Upgrading from 3.4 to 3.5

## ORM::changed()

The `ORM::changed()` method now returns a strict boolean (`true` or `false`) indicating whether the model has any
changes. Use when you need a simple check:

~~~
$user = ORM::factory('User');

$user->username = 'admin';
$user->logins++;

$user->changed(); // true
$user->changed('email'); // false
$user->changed('username'); // true
$user->changed('logins'); // true
~~~

## ORM::changes()

A new `ORM::changes()` method was added to retrieve an array of changed fields and their current values.

## Mass Assignment

The second parameter of the `ORM::values()` method is now required and must explicitly list assignable fields to prevent
mass assignment vulnerabilities.

~~~
$user->values($data, ['username', 'logins']);
~~~

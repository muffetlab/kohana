# Upgrading from 3.4 to 3.5

## ORM::changed()

The `Model::changed()` method now returns a strict boolean (`true` or `false`) indicating whether the model has any
changes. Use when you need a simple check:

~~~
$user = Model::factory('User');

$user->username = 'admin';
$user->logins++;

$user->changed(); // true
$user->changed('email'); // false
$user->changed('username'); // true
$user->changed('logins'); // true
~~~

## ORM::changes()

A new `Model::changes()` method was added to retrieve an array of changed fields and their current values.

## Mass Assignment

The second parameter of the `Model::values()` method is now required and must explicitly list assignable fields to prevent
mass assignment vulnerabilities.

~~~
$user->values($data, ['username', 'logins']);
~~~

## Non-existent Property Access

In Kohana 3.5, the ORM class now allows accessing and setting non-existent properties without throwing exceptions.
Previously, attempting to access or set a property that didn't exist on the model would result in a Kohana_Exception
being thrown. This behavior has been changed to provide more flexibility when working with dynamic properties.

~~~
$user = Model::factory('User');
echo $user->nonexistent_field; // Returns null
$user->nonexistent_field = 'value'; // Sets the property without exception
~~~

This change makes the ORM class more tolerant when dealing with dynamic properties, preventing runtime exceptions while
maintaining backward compatibility for valid operations.

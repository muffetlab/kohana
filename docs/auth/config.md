# Configuration

The default configuration file is located in `MODPATH/auth/config/auth.php`. You should copy this file to `APPPATH/config/auth.php` and make changes there, in keeping with the [cascading filesystem](../kohana/files).

[Config merging](../kohana/config#config-merging) allows these default configuration settings to apply if you don't overwrite them in your application configuration file.

| Name         | Type     | Default             | Description                                             |
|--------------|----------|---------------------|---------------------------------------------------------|
| driver       | `string` | file                | The name of the auth driver to use.                     |
| hash_algorithm | `string` | null              | Algorithm for `password_hash()`. `null` uses Argon2id, or bcrypt when PHP lacks Argon2. |
| hash_options | `array`  | []                  | Options for `password_hash()`, e.g. `['memory_cost' => 65536]` or `['cost' => 12]`. |
| hash_method  | `string` | sha256              | Kohana 3 only: HMAC function of passwords stored before Kohana 4. |
| hash_key     | `string` | null                | Kohana 3 only: HMAC key of passwords stored before Kohana 4. Keep it until every user has logged in once. |
| session_type | `string` | `Session::$default` | The type of session to use when storing the auth user.  |
| session_key  | `string` | auth_user           | The name of the session variable used to save the user. |

## Password hashing

`Auth::hash()` uses PHP's `password_hash()`, which salts every password and is slow on purpose. `Auth::verify($password, $hash)` checks a password against a stored hash in constant time, and `Auth::needsRehash($hash)` tells you when a hash was made with older settings.

Passwords stored by Kohana 3 (HMAC with `hash_method` and `hash_key`) still verify while `hash_key` is set. The ORM driver replaces such a hash with a new one the next time the user logs in. Make sure the `password` column holds at least 255 characters.

# Upgrading from 3.4 to 3.5

## Requirements

Kohana 3.5 supports PHP versions 7.1, 7.2, and 7.3. Compatibility with other PHP versions has not been fully tested, and
certain features may not function as expected.

## Changes

- The `composer install` command is now required for dependency installation. Remember to run this command during
  upgrades.
- A new `VENDOR_PATH` constant has been added. This constant is used to locate the vendor directory.
- The global `EXT` constant has been removed. Explicitly specify `.php` or another file extension instead.

### Arr

- The `Arr::callback()` method now ensures that the second element of the returned array (`$params`) is always an array,
  even when no parameters are provided. This means you can safely remove any null checks for `$params` in your code.

### Cache

- The `Apc` driver has been removed. Use the `Apcu` driver or others instead.
- The `MemcacheTag` driver has been removed due to its dependency on the unmaintained `memcached-tags` PHP extension. If
  you were using this driver for tag-based caching, consider using the `Sqlite` driver.
- The `Wincache` driver was deprecated. Use the `Apcu` driver or others instead.

### Core

- The `Kohana::CODENAME` constant has been removed.
- The static property `Kohana::$magic_quotes` was deprecated.

### Database

- A new `flags` connection option was added for MySQLi driver. This option allows you to set various flags for the
  MySQLi connection. Refer to the [MySQLi documentation](https://www.php.net/manual/en/mysqli.real-connect.php) for a
  list of available flags.

### Encrypt

- The `Mcrypt` driver has been removed. Use the `OpenSSL` driver instead.

### Image

- The static property `Image::$default_driver` has been removed. To configure the default driver, refer to
  the [Image driver configuration](../../guide/image/#drivers).

### ORM

- The `changed()` method now has a strict boolean return type and only returns true or false.
- A new `changes()` method was added to retrieve the actual changed fields and their values.
- The second parameter of the `ORM::values()` method is no longer optional. This change was implemented to enhance
  security by preventing mass assignment vulnerabilities.

### Request

- The `Request::accept_encoding()` method has been removed. Use `Request::headers()->accepts_encoding_at_quality()`
  instead.
- The `Request::accept_lang()` method has been removed. Use `Request::headers()->accepts_language_at_quality()` instead.
- The `Request::accept_type()` method has been removed. Use `Request::headers()->accepts_at_quality()` instead.

### UTF8

- The `UTF8::strlen()`, `UTF8::strpos()`, `UTF8::strrpos()`, `UTF8::substr()`, `UTF8::strtolower()`,
  `UTF8::strtoupper()`, and `UTF8::stristr()` methods were deprecated. Please use their equivalent multibyte string
  functions instead.

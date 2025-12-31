# Upgrading from 3.4 to 3.5

## Requirements

Kohana 3.5 supports PHP versions 7.1, 7.2, and 7.3. Compatibility with other PHP versions has not been fully tested, and
certain features may not function as expected.

## Dependency Management

The `composer install` command is required for dependency installation since Kohana 3.4.4. Remember to run this command
during upgrades.

## Constants

The global `EXT` constant has been removed. Explicitly specify `.php` or another file extension instead.

## Arr

The `Arr::callback()` method now guarantees that the second element of the returned array (`$params`) is always an
array, even when no parameters are provided. You can safely remove null checks for `$params` in your code.

## Core

- The `Kohana::CODENAME` constant has been removed.
- The static property `Kohana::$magic_quotes` was deprecated.

## Encrypt

The `Mcrypt` driver has been removed. Use the `OpenSSL` driver instead.

## Request

The `Request::accept_encoding()`, `Request::accept_lang()`, and `Request::accept_type()` methods have been removed. Use
the header helper methods instead:

- `$request->headers()->accepts_encoding_at_quality()` — returns the quality for a specific encoding. Retrieving the
  full list of accepted encodings is not supported.
- `$request->headers()->accepts_language_at_quality()` — returns the quality for a specific language. Retrieving the
  full list of accepted languages is not supported.
- `$request->headers()->accepts_at_quality()` — returns the quality for a specific MIME type. Retrieving the full list
  of accepted content types is not supported.

## UTF8

The `UTF8::strlen()`, `UTF8::strpos()`, `UTF8::strrpos()`, `UTF8::substr()`, `UTF8::strtolower()`, `UTF8::strtoupper()`,
and `UTF8::stristr()` methods were deprecated. Please use their equivalent multibyte string functions instead.

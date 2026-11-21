# Upgrade Guide: Kohana 3.5 → PHP 8.5

This guide lists every breaking change, step by step. Each step shows what changed and what to do in your application.

## 1. PHP 8.1+ is required

`composer.json` now requires `"php": ">=8.1"`. The framework is tested on PHP 8.5.

**Action:** Run your application on PHP 8.1 or newer.

## 2. Nullable parameters are explicit

Signatures such as `function f(array $x = null)` are now `function f(?array $x = null)`. PHP 8.4 deprecates the implicit form.

**Action:** If you extend a framework class and override one of these methods, add `?` to the same parameters. Otherwise PHP reports an incompatible-signature error. Rector does this automatically:

```php
// rector.php
->withRules([\Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class])
```

## 3. Return types on `ArrayAccess` and `Iterator` classes

| Class | Method | New return type |
|---|---|---|
| `Validation`, `Config_Group`, `HTTP_Header`, `Database_Result` | `offsetSet()`, `offsetUnset()` | `void` |
| `Validation`, `HTTP_Header`, `Database_Result`, `Database_MySQLi_Result`, `Database_Result_Cached` | `offsetGet()`, `current()` | `mixed` |

`Database_Result::next()`, `rewind()` and `seek()` keep their chainable return values and are marked `#[\ReturnTypeWillChange]`.

**Action:** If you override these methods, use the same return types.

## 4. `E_STRICT` removed

`Kohana_Exception::$php_errors` no longer has an `E_STRICT` entry. The constant is deprecated in PHP 8.4.

**Action:** Remove `E_STRICT` from `error_reporting()` calls in your `bootstrap.php`.

## 5. `Arr::path()` with objects

On PHP 8, `array_key_exists()` no longer accepts objects. `Arr::path()` now checks `ArrayAccess` objects (for example `Config_Group`) with `offsetExists()`. Behaviour is unchanged. No action needed.

## 6. `UTF8::strcasecmp()` returns -1, 0 or 1

On PHP 8.2+, string comparison functions return only -1, 0 or 1, not the byte difference.

**Action:** Compare the result's sign (`< 0`, `> 0`) instead of its exact value.

## 7. Tests: PHPUnit 9.6

- `setUp()`, `tearDown()`, `setUpBeforeClass()` and `tearDownAfterClass()` must declare `: void`.
- `assertRegExp()` → `assertMatchesRegularExpression()`.
- `assertContains()` on a string → `assertStringContainsString()`.
- PHPUnit calls test methods in strict mode, so data provider values must match the parameters' scalar types exactly.

## 8. `Date::months()` no longer uses `strftime()`

`strftime()` is deprecated in PHP 8.1. `Date::MONTHS_LONG` is now `'F'` and `Date::MONTHS_SHORT` is `'M'`, both `date()` formats. Month names no longer follow `setlocale()` and are always English.

**Action:** If you need localized month names, translate them with `__()` or use `IntlDateFormatter`. If you passed `'%B'`/`'%b'` directly instead of the constants, use the constants.

## 9. `Valid::digit()` rejects non-strings

`Valid::digit()` returns `false` for `null`, `bool`, `float` and other non-string, non-int values. Before, PHP's `ctype_digit()` handled them inconsistently.

**Action:** Cast the value to a string before validating it if it may arrive as another type.

## 10. `File::split()` accepts a float piece size

`File::split(string $filename, float $piece_size = 10)`: you can split into pieces smaller than 1 MB, for example `0.5`. No action needed.

## 11. Tests: `setAccessible()` removed

`ReflectionProperty::setAccessible()` and `ReflectionMethod::setAccessible()` are deprecated in PHP 8.5 and have had no effect since 8.1.

**Action:** Delete these calls from your own tests.

## 12. ORM no longer implements `Serializable`

`Serializable` is deprecated in PHP 8.1. `ORM::serialize()` and `ORM::unserialize()` are replaced by `__serialize(): array` and `__unserialize(array $data): void`.

**Action:**
- Models that override `serialize()`/`unserialize()` must override `__serialize()`/`__unserialize()` instead.
- ORM objects serialized before the upgrade (in sessions or cache) use the old `C:` format and cannot be restored. Clear sessions and cache that hold ORM objects when you deploy.

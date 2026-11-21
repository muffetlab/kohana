# Upgrading to Kohana 4

Kohana 4 (PHP 8.4+) renames every class, method and property and removes several modules. Follow the step-by-step [upgrade guide](../../UPGRADE.md) in the repository root, which also ships Rector and php-parser scripts that rename your code automatically.

The notes below cover older upgrades.

# Upgrading from 3.4 to 3.5

## Cache Drivers

- The `Apc` driver has been removed. Use the `Apcu` driver or others instead.
- The `MemcacheTag` driver has been removed due to its dependency on the unmaintained `memcached-tags` PHP extension. If
  you were using this driver for tag-based caching, consider using the `Sqlite` driver.
- The `Wincache` and `Memcache` drivers were removed in Kohana 4. Use `ApcuStore`, `MemcachedStore` or `FileStore` instead.

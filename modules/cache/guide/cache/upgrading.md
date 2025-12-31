# Upgrading from 3.4 to 3.5

## Cache Drivers

- The `Apc` driver has been removed. Use the `Apcu` driver or others instead.
- The `MemcacheTag` driver has been removed due to its dependency on the unmaintained `memcached-tags` PHP extension. If
  you were using this driver for tag-based caching, consider using the `Sqlite` driver.
- The `Wincache` driver was deprecated. Use the `Apcu` driver or others instead.

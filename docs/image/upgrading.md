# Upgrading to Kohana 4

Kohana 4 (PHP 8.4+) renames every class, method and property and removes several modules. Follow the step-by-step [upgrade guide](../../UPGRADE.md) in the repository root, which also ships Rector and php-parser scripts that rename your code automatically.

The notes below cover older upgrades.

# Upgrading from 3.4 to 3.5

## Default Image Driver

The static property `Image::$default_driver` has been removed. To configure the default driver, refer to
  the [Image driver configuration](../../guide/image/#drivers).

# Upgrading to Kohana 4

Kohana 4 (PHP 8.4+) renames every class, method and property and removes several modules. Follow the step-by-step [upgrade guide](../../UPGRADE.md) in the repository root, which also ships Rector and php-parser scripts that rename your code automatically.

The notes below cover older upgrades.

# Upgrading from 3.4 to 3.5

## Database Connections

A new `flags` connection option was added for MySQLi driver. This option allows you to set various flags for the
  MySQLi connection. Refer to the [MySQLi documentation](https://www.php.net/manual/en/mysqli.real-connect.php) for a
  list of available flags.

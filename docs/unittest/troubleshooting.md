# Troubleshooting

## Code coverage is empty

Coverage needs a driver. Install [PCOV](https://github.com/krakjoe/pcov) (fast, coverage only) or Xdebug, then run:

    vendor/bin/pest --coverage
    vendor/bin/pest --coverage-html=build/coverage

The directories that count towards coverage are listed in the `<source>` section of `phpunit.xml`.

## Database tests are skipped

The database tests connect with the `default` (and `mysqli`) groups of `config/database.php` and skip themselves when the connection fails. Put local credentials in `config/database.php` and keep that file out of version control.

## "Class ... not found" for a test fixture

Fixture classes such as dummy controllers and models are loaded by Composer. Put them in a PSR-4 directory listed under `autoload-dev` in `composer.json`, then run `composer dump-autoload`.

## A test passes on macOS but fails on Linux

Class file names must match the class name's case. macOS filesystems ignore case by default, Linux does not.

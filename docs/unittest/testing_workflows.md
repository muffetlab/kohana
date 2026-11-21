# Testing workflows

Tests are only useful if you run them. Here are a few ways to make that part of your routine.

## In your editor

PhpStorm and VS Code (with a PHPUnit or Pest extension) read `phpunit.xml` and can run a single test, a file or the whole suite from the editor. Point them at `vendor/bin/pest` (or `vendor/bin/phpunit`) and at the `phpunit.xml` in your project root.

## On every change

Run only what you are working on:

    vendor/bin/pest tests/Feature/HomePageTest.php
    vendor/bin/pest --filter=it_says_hello

Run the failing tests first, and stop at the first failure:

    vendor/bin/pest --order-by=defects --stop-on-failure

Run in parallel on large suites:

    vendor/bin/pest --parallel

## Continuous Integration

Run the suite on every push. A GitHub Actions workflow for a Kohana application:

    name: Tests

    on: [push, pull_request]

    jobs:
      test:
        runs-on: ubuntu-latest
        steps:
          - uses: actions/checkout@v6
          - uses: shivammathur/setup-php@v2
            with:
              php-version: '8.5'
              tools: composer
          - run: composer install --prefer-dist --no-progress
          - run: vendor/bin/pest

Kohana's own workflow in `.github/workflows/test.yml` also starts MySQL and Memcached services for the database and cache tests.

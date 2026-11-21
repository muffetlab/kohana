# Testing

Kohana's tests run on [Pest](https://pestphp.com), which is built on [PHPUnit](https://phpunit.de). Pest runs both its own functional tests and ordinary PHPUnit test classes, so you can use either style.

## Setup

Install Pest as a development dependency:

    composer require --dev pestphp/pest

Create a `phpunit.xml` in your project root that boots Kohana and lists your test directory:

    <?xml version="1.0" encoding="UTF-8"?>
    <phpunit bootstrap="modules/unittest/bootstrap.php" colors="true">
        <testsuites>
            <testsuite name="Application">
                <directory>tests</directory>
            </testsuite>
        </testsuites>
    </phpunit>

`modules/unittest/bootstrap.php` defines the Kohana paths and runs `application/bootstrap.php`, so your modules, config and routes are loaded as in a real request.

Run the suite:

    vendor/bin/pest

## Writing tests

### Pest style

    // tests/Feature/HomePageTest.php

    use Kohana\Http\Request;

    uses(Kohana\Testing\TestCase::class);

    it('says hello', function () {
        $response = Request::factory('/')->execute();

        expect($response->status())->toBe(200)
            ->and($response->body())->toContain('hello');
    });

Add `uses(Kohana\Testing\TestCase::class)->in('Feature');` to `tests/Pest.php` to apply the test case to a whole directory.

### PHPUnit style

Test classes extend `Kohana\Testing\TestCase`, which adds Kohana helpers such as `setEnvironment()` (temporarily changes static properties, config values and superglobals, and restores them after the test) and `mockAbstract()`.

    use Kohana\Testing\TestCase;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\Attributes\Group;

    #[Group('strings')]
    class StrLenTest extends TestCase
    {
        public static function providerStrLen(): array
        {
            return [
                ['One set of testcase data', 24],
                ['This is a different one', 23],
            ];
        }

        #[DataProvider('providerStrLen')]
        public function testStrLen(string $string, int $length): void
        {
            $this->assertSame($length, strlen($string));
        }
    }

Data providers must be `public static`. Metadata uses attributes (`#[DataProvider]`, `#[Group]`, `#[Test]`); docblock annotations are no longer read.

### Database tests

Extend `Kohana\Testing\DatabaseTestCase` to get `getConnection()` (a PDO connection) and `getKohanaConnection()` for the connection named in `config/unittest.php`. Skip the test when the database is not available:

    public function setUp(): void
    {
        parent::setUp();

        try {
            Connection::instance()->connect();
        } catch (Exception $e) {
            $this->markTestSkipped('Database connection failed: ' . $e->getMessage());
        }
    }

## Grouping and filtering

    vendor/bin/pest --group=strings
    vendor/bin/pest --exclude-group=slow
    vendor/bin/pest --filter=StrLen
    vendor/bin/pest --list-groups

Kohana's own tests use lowercase groups with periods for levels, for example `kohana.validation.helpers`.

<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests Kohana Core
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.core')]
class Kohana_CoreTest extends TestCase
{
    protected $old_modules = [];

    /**
     * Captures the module list as it was before this test
     *
     * @return void
     * @throws KohanaException
     */
    // @codingStandardsIgnoreStart
    public function setUp(): void
    // @codingStandardsIgnoreEnd
    {
        parent::setUp();
        $this->old_modules = Application::modules();
    }

    /**
     * Restores the module list
     *
     * @return void
     * @throws KohanaException
     */
    // @codingStandardsIgnoreStart
    public function tearDown(): void
    // @codingStandardsIgnoreEnd
    {
        Application::modules($this->old_modules);
    }

    /**
     * Provides test data for test_sanitize()
     *
     * @return array
     */
    public static function provider_sanitize(): array
    {
        return [
            // $value, $result
            ['foo', 'foo'],
            ["foo\r\nbar", "foo\nbar"],
            ["foo\rbar", "foo\nbar"],
            ["Is your name O\'reilly?", "Is your name O\'reilly?"],
        ];
    }

    /**
     * Tests Kohana::sanitize()
     *
     * @param string $value Input for Kohana::sanitize
     * @param string $result Output for Kohana::sanitize
     * @throws KohanaException
     * @throws ReflectionException
     */
    #[DataProvider('provider_sanitize')]
    #[Test]
    public function test_sanitize(string $value, string $result)
    {
        $this->assertSame($result, Application::sanitize($value));
    }

    /**
     * Passing false for the file extension should prevent appending any extension.
     * See issue #3214
     */
    #[Test]
    public function test_find_file_no_extension()
    {
        // ".php" is manually appended to the _file name_, not passed as the extension
        $path = Application::findFile('src', $file = 'Foundation/Application.php', false);

        $this->assertIsString($path);

        $this->assertStringEndsWith($file, $path);
    }

    /**
     * If a file can't be found then find_file() should return false if
     * only a single file was requested, or an empty array if multiple files
     * (i.e. configuration files) were requested
     */
    #[Test]
    public function test_find_file_returns_false_or_array_on_failure()
    {
        $this->assertFalse(Application::findFile('configy', 'zebra'));

        $this->assertSame([], Application::findFile('configy', 'zebra', null, true));
    }

    /**
     * Kohana::list_files() should return an array on success and an empty array on failure
     */
    #[Test]
    public function test_list_files_returns_array_on_success_and_failure()
    {
        $files = Application::listFiles('config');

        $this->assertIsArray($files);
        $this->assertGreaterThan(3, count($files));

        $this->assertSame([], Application::listFiles('geshmuck'));
    }

    /**
     * Provides test data for testCache()
     *
     * @return array
     */
    public static function provider_cache(): array
    {
        return [
            // $value, $result
            ['foo', 'hello, world', 10],
            ['bar', null, 10],
            ['bar', null, -10],
        ];
    }

    /**
     * Tests Kohana::cache()
     *
     * @param string $key Key to cache/get for Kohana::cache
     * @param mixed $value Output from Kohana::cache
     * @param int $lifetime Lifetime for Kohana::cache
     */
    #[DataProvider('provider_cache')]
    #[Test]
    public function test_cache(string $key, $value, int $lifetime)
    {
        Application::cache($key, $value, $lifetime);
        $this->assertEquals($value, Application::cache($key));
    }

    /**
     * Provides test data for test_message()
     *
     * @return array
     */
    public static function provider_message(): array
    {
        return [
            [
                'no_message_file',
                'anything',
                'default',
                'default',
            ],
            [
                'no_message_file',
                null,
                'anything',
                [],
            ],
            [
                'kohana_core_message_tests',
                'bottom_only',
                'anything',
                'inherited bottom message',
            ],
            [
                'kohana_core_message_tests',
                'cfs_replaced',
                'anything',
                'overriding cfs_replaced message',
            ],
            [
                'kohana_core_message_tests',
                'top_only',
                'anything',
                'top only message',
            ],
            [
                'kohana_core_message_tests',
                'missing',
                'default',
                'default',
            ],
            [
                'kohana_core_message_tests',
                null,
                'anything',
                [
                    'bottom_only' => 'inherited bottom message',
                    'cfs_replaced' => 'overriding cfs_replaced message',
                    'top_only' => 'top only message',
                ],
            ],
        ];
    }

    /**
     * Tests Kohana::message()
     *
     * @param string $file to pass to Kohana::message
     * @param string|null $key to pass to Kohana::message
     * @param string $default to pass to Kohana::message
     * @param mixed $expected Output for Kohana::message
     * @throws KohanaException
     */
    #[DataProvider('provider_message')]
    #[Test]
    public function test_message(string $file, ?string $key, string $default, $expected)
    {
        $test_path = realpath(KOHANA_FIXTURES . '/message_tests');
        Application::modules([
            'top' => "$test_path/top_module",
            'bottom' => "$test_path/bottom_module",
        ]);

        $this->assertEquals($expected, Application::message($file, $key, $default));
    }

    /**
     * Provides test data for test_error_handler()
     *
     * @return array
     */
    public static function provider_error_handler(): array
    {
        return [
            [1, 'Foobar', 'foobar.php', __LINE__],
        ];
    }

    /**
     * Tests Kohana::error_handler()
     *
     * @param int $code The Exception code.
     * @param string $error The Exception message to throw.
     * @param string $file The filename where the exception is thrown.
     * @param int $line The line number where the exception is thrown.
     */
    #[DataProvider('provider_error_handler')]
    #[Test]
    public function test_error_handler(int $code, string $error, string $file, int $line)
    {
        $error_level = error_reporting();
        error_reporting(E_ALL);
        try {
            Application::errorHandler($code, $error, $file, $line);
        } catch (Exception $e) {
            $this->assertEquals($code, $e->getCode());
            $this->assertEquals($error, $e->getMessage());
        }
        error_reporting($error_level);
    }

    /**
     * Provides test data for test_modules_sets_and_returns_valid_modules()
     *
     * @return array
     */
    public static function provider_modules_detects_invalid_modules(): array
    {
        return [
            [
                [
                    'unittest' => MODPATH . 'fo0bar',
                ],
            ],
            [
                [
                    'unittest' => MODPATH . 'unittest',
                    'fo0bar' => MODPATH . 'fo0bar',
                ],
            ],
        ];
    }

    /**
     * Tests Kohana::modules()
     *
     * @param array $source Input for Kohana::modules
     * @throws KohanaException
     */
    #[DataProvider('provider_modules_detects_invalid_modules')]
    #[Test]
    public function test_modules_detects_invalid_modules(array $source)
    {
        $this->expectException(KohanaException::class);

        $modules = Application::modules();

        Application::modules($source);

        // Restore modules
        Application::modules($modules);
    }

    /**
     * Provides test data for test_modules_sets_and_returns_valid_modules()
     *
     * @return array
     */
    public static function provider_modules_sets_and_returns_valid_modules(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                ['module' => __DIR__],
                ['module' => static::dirSeparator(__DIR__ . '/')],
            ],
        ];
    }

    /**
     * Tests Kohana::modules()
     *
     * @param array $source Input for Kohana::modules
     * @param array $expected Output for Kohana::modules
     * @throws KohanaException
     */
    #[DataProvider('provider_modules_sets_and_returns_valid_modules')]
    #[Test]
    public function test_modules_sets_and_returns_valid_modules(array $source, array $expected)
    {
        $modules = Application::modules();

        try {
            $this->assertEquals($expected, Application::modules($source));
        } catch (Exception $e) {
            Application::modules($modules);

            throw $e;
        }

        Application::modules($modules);
    }

    /**
     * To make the tests as portable as possible this just tests that
     * you get an array of modules when you can Kohana::modules() and that
     * said array contains unittest
     */
    #[Test]
    public function test_modules_returns_array_of_modules()
    {
        $modules = Application::modules();

        $this->assertIsArray($modules);

        $this->assertArrayHasKey('unittest', $modules);
    }

    /**
     * Tests Kohana::include_paths()
     *
     * The include paths must contain the apppath and syspath
     */
    #[Test]
    public function test_include_paths()
    {
        $include_paths = Application::includePaths();
        $modules = Application::modules();

        $this->assertIsArray($include_paths);

        // We must have at least 2 items in include paths (APP / SYS)
        $this->assertGreaterThan(2, count($include_paths));
        // Make sure said paths are in the include paths
        // And make sure they're in the correct positions
        $this->assertSame(APPPATH, reset($include_paths));
        $this->assertSame(SYSPATH, end($include_paths));

        foreach ($modules as $module) {
            $this->assertContains($module, $include_paths);
        }
    }

}

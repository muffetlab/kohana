<?php

use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Testing\TestCase;
use Kohana\View\View;
use Kohana\View\ViewException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests the View class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.core')]
#[Group('kohana.core.view')]
class Kohana_ViewTest extends TestCase
{
    protected static $old_modules = [];

    /**
     * Setups the filesystem for test view files
     *
     * @return void
     * @throws KohanaException
     */
    // @codingStandardsIgnoreStart
    public static function setupBeforeClass(): void
    // @codingStandardsIgnoreEnd
    {
        self::$old_modules = Application::modules();

        $new_modules = self::$old_modules + [
            'test_views' => realpath(KOHANA_FIXTURES . '/'),
        ];
        Application::modules($new_modules);
    }

    /**
     * Restores the module list
     *
     * @return void
     * @throws KohanaException
     */
    // @codingStandardsIgnoreStart
    public static function teardownAfterClass(): void
    // @codingStandardsIgnoreEnd
    {
        Application::modules(self::$old_modules);
    }

    /**
     * Provider for test_instantiate
     *
     * @return array
     */
    public static function provider_instantiate(): array
    {
        return [
            ['kohana/error', false],
            ['test.css', false],
            ['doesnt_exist', true],
        ];
    }

    /**
     * Provider to test_set
     *
     * @return array
     */
    public static function provider_set(): array
    {
        return [
            ['foo', 'bar', 'foo', 'bar'],
            [['foo' => 'bar'], null, 'foo', 'bar'],
            [new ArrayIterator(['foo' => 'bar']), null, 'foo', 'bar'],
        ];
    }

    /**
     * Tests that we can instantiate a view file
     *
     * @return void
     */
    #[DataProvider('provider_instantiate')]
    #[Test]
    public function test_instantiate($path, $expects_exception)
    {
        try {
            new View($path);
            $this->assertSame(false, $expects_exception);
        } catch (ViewException $e) {
            $this->assertSame(true, $expects_exception);
        }
    }

    /**
     * Tests that we can set using string, array or Traversable object
     *
     * @return void
     * @throws ViewException
     */
    #[DataProvider('provider_set')]
    #[Test]
    public function test_set($data_key, $value, $test_key, $expected)
    {
        $view = View::factory()->set($data_key, $value);
        $this->assertSame($expected, $view->$test_key);
    }

    /**
     * Tests that we can set global using string, array or Traversable object
     *
     * @return void
     * @throws ViewException
     */
    #[DataProvider('provider_set')]
    #[Test]
    public function test_set_global($data_key, $value, $test_key, $expected)
    {
        $view = View::factory();
        $view::setGlobal($data_key, $value);
        $this->assertSame($expected, $view->$test_key);
    }

}

<?php

namespace Kohana\Testing;

use PHPUnit\Framework\DOMAssert;

/**
 * A version of the stock PHPUnit testcase that includes some extra helpers
 * and default settings
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Mocks an abstract class: its abstract methods plus $methods are doubled
     * (replaces PHPUnit's removed getMockForAbstractClass()).
     *
     * @param string $class Abstract class name
     * @param array|null $arguments Constructor arguments, null to skip the constructor
     * @param array $methods Extra concrete methods to double
     */
    protected function mockAbstract(string $class, ?array $arguments = [], array $methods = []): \PHPUnit\Framework\MockObject\MockObject
    {
        foreach ((new \ReflectionClass($class))->getMethods() as $method) {
            if ($method->isAbstract()) {
                $methods[] = $method->getName();
            }
        }

        $builder = $this->getMockBuilder($class)->onlyMethods(array_unique($methods));

        $arguments === null ? $builder->disableOriginalConstructor() : $builder->setConstructorArgs($arguments);

        return $builder->getMock();
    }
    /**
     * Asserts that a CSS selector matches $count elements with $content
     * (phpunit/phpunit-dom-assertions).
     */
    public static function assertSelectEquals(string $selector, ?string $content, array|bool|int $count, \DOMDocument|string $actual, string $message = '', bool $isHtml = true): void
    {
        DOMAssert::assertSelectEquals($selector, $content, $count, $actual, $message, $isHtml);
    }
    /**
     * Make sure PHPUnit backs up globals
     * @var bool
     */
    protected $backupGlobals = false;

    /**
     * A set of unittest helpers that are shared between normal / database
     * testcases
     * @var Kohana_Unittest_Helpers
     */
    protected $helpers = null;

    /**
     * A default set of environment to be applied before each test
     * @var array
     */
    protected $environmentDefault = [];

    /**
     * Creates a predefined environment using the default environment
     *
     * Extending classes that have their own setUp() should call
     * parent::setUp()
     */
    public function setUp(): void
    {
        $this->helpers = new Helpers;

        $this->setEnvironment($this->environmentDefault);
    }

    /**
     * Restores the original environment overridden with setEnvironment()
     *
     * Extending classes that have their own tearDown()
     * should call parent::tearDown()
     */
    public function tearDown(): void
    {
        $this->helpers->restoreEnvironment();
    }

    /**
     * Removes all kohana related cache files in the cache directory
     *
     * @return void
     */
    public function cleanCacheDir()
    {
        Helpers::cleanCacheDir();
    }

    /**
     * Helper function that replaces all occurrences of '/' with
     * the OS-specific directory separator
     *
     * @param string $path The path to act on
     * @return string
     */
    public static function dirSeparator(string $path): string
    {
        return Helpers::dirSeparator($path);
    }

    /**
     * Allows easy setting & backing up of environment config
     *
     * Option types are checked in the following order:
     *
     * * Server Var
     * * Static Variable
     * * Config option
     *
     * @param array $environment List of environment to set
     * @return false|null
     * @throws Kohana_Exception
     * @throws ReflectionException
     */
    public function setEnvironment(array $environment): ?bool
    {
        return $this->helpers->setEnvironment($environment);
    }

    /**
     * Check for internet connectivity
     *
     * @return bool Whether an internet connection is available
     */
    public function hasInternet(): bool
    {
        return Helpers::hasInternet();
    }
}

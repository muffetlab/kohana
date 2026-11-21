<?php

namespace Kohana\Testing;

use PHPUnit\Framework\DOMTestCase;

/**
 * A version of the stock PHPUnit testcase that includes some extra helpers
 * and default settings
 */
abstract class TestCase extends DOMTestCase
{
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
    protected $_helpers = null;

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
        $this->_helpers = new Helpers;

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
        $this->_helpers->restore_environment();
    }

    /**
     * Removes all kohana related cache files in the cache directory
     *
     * @return void
     */
    public function cleanCacheDir()
    {
        Helpers::clean_cache_dir();
    }

    /**
     * Helper function that replaces all occurrences of '/' with
     * the OS-specific directory separator
     *
     * @param string $path The path to act on
     * @return string
     */
    public function dirSeparator(string $path): string
    {
        return Helpers::dir_separator($path);
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
        return $this->_helpers->set_environment($environment);
    }

    /**
     * Check for internet connectivity
     *
     * @return bool Whether an internet connection is available
     */
    public function hasInternet(): bool
    {
        return Helpers::has_internet();
    }
}

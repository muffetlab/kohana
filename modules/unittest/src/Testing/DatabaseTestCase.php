<?php

namespace Kohana\Testing;

use Kohana\Database\Connection;
use Kohana\Foundation\Application;
use PHPUnit\Framework\TestCase;

/**
 * TestCase for testing a database
 *
 * @package    Kohana/UnitTest
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class DatabaseTestCase extends TestCase
{
    /**
     * Make sure PHPUnit backs up globals
     * @var bool
     */
    protected $backupGlobals = false;

    /**
     * A set of unittest helpers that are shared between normal / database
     * testcases
     * @var \Kohana\Testing\Helpers
     */
    protected $helpers = null;

    /**
     * A default set of environment to be applied before each test
     * @var array
     */
    protected $environmentDefault = [];

    /**
     * The kohana database connection that PHPUnit should use for this test
     * @var string
     */
    protected $databaseConnection = 'default';

    /**
     * Creates a predefined environment using the default environment
     *
     * Extending classes that have their own setUp() should call
     * parent::setUp()
     *
     * @return void
     * @throws \Kohana\Foundation\KohanaException
     * @throws \ReflectionException
     */
    public function setUp(): void
    {
        $this->helpers = new Helpers();

        $this->setEnvironment($this->environmentDefault);

        parent::setUp();
    }

    /**
     * Restores the original environment overridden with setEnvironment()
     *
     * Extending classes that have their own tearDown()
     * should call parent::tearDown()
     *
     * @return void
     * @throws \Kohana\Foundation\KohanaException
     * @throws \ReflectionException
     */
    public function tearDown(): void
    {
        $this->helpers->restoreEnvironment();

        parent::tearDown();
    }

    /**
     * Creates a connection to the unittesting database
     *
     * @return \PDO
     * @throws \Kohana\Foundation\KohanaException
     */
    public function getConnection(): \PDO
    {
        // Get the unittesting db connection
        $config = Application::$config->load('database.' . $this->databaseConnection);

        if (strtolower($config['type']) !== 'pdo') {
            $config['connection']['dsn'] = strtolower($config['type']) . ':' .
                'host=' . $config['connection']['hostname'] . ';' .
                'dbname=' . $config['connection']['database'];
        }

        $pdo = new \PDO(
            $config['connection']['dsn'],
            $config['connection']['username'],
            $config['connection']['password']
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * Gets a connection to the unittest database
     *
     * @return \Kohana\Database\Connection The database connection
     * @throws \Kohana\Foundation\KohanaException
     */
    public function getKohanaConnection(): Connection
    {
        return Connection::instance($this->databaseConnection);
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
     * @throws \Kohana\Foundation\KohanaException
     * @throws \ReflectionException
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

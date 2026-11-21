<?php

use PHPUnit\Framework\Attributes\Group;
use Kohana\Foundation\Application;
use Kohana\Cache\Cache;
use Kohana\Cache\CacheException;
use Kohana\Foundation\KohanaException;

include_once __DIR__ . '/CacheBasicMethods.php';

/**
 * @package    Kohana/Cache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.cache')]
class Kohana_SqliteTest extends Kohana_CacheBasicMethodsTest
{
    /**
     * This method MUST be implemented by each driver to set up the `Cache`
     * instance for each test.
     *
     * This method should do the following tasks for each driver test:
     *
     *  - Test the Cache instance driver is available, skip test otherwise
     *  - Set up the Cache instance
     *  - Call the parent setup method, `parent::setUp()`
     *
     * @return  void
     * @throws CacheException
     * @throws KohanaException
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('SQLite PDO PHP Extension is not available');
        }

        if (!Application::$config->load('cache.sqlite')) {
            Application::$config->load('cache')
                ->set('sqlite', [
                    'driver' => 'sqlite',
                    'default_expire' => 3600,
                    'database' => ':memory:',
                    'schema' => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
                    ]
            );
        }

        $this->cache(Cache::instance('sqlite'));
    }

}

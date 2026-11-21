<?php

use PHPUnit\Framework\Attributes\Group;
use Kohana\Foundation\Application;
use Kohana\Cache\Cache;
use Kohana\Cache\CacheException;
use Kohana\Foundation\KohanaException;

/**
 * @package    Kohana/Cache/Memcached
 * @category   Test
 * @author     Loong <loong2460@gmail.com>
 * @copyright  (c) 2018 Kohana Group
 * @license    https://kohana.top/license
 */
#[Group('kohana')]
#[Group('kohana.cache')]
#[Group('kohana.cache.memcached')]
class Kohana_CacheArithmeticMemcachedTest extends Kohana_CacheArithmeticMethodsTest
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

        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached PHP Extension is not available');
        }

        if (!Application::$config->load('cache.memcached')) {
            Application::$config->load('cache')->set('memcached', [
                'driver' => 'memcached',
                'default_expire' => 3600,
                'servers' => [
                    'local' => [
                        'host' => 'localhost',
                        'port' => 11211,
                        'weight' => 1,
                    ],
                ]
            ]);
            Application::$config->load('cache.memcached');
        }

        $this->cache(Cache::instance('memcached'));
    }

}

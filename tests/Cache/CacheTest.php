<?php

use Kohana\Cache\Cache;
use Kohana\Cache\CacheException;
use Kohana\Cache\FileStore;
use Kohana\Foundation\Application;
use Kohana\Foundation\KohanaException;
use Kohana\Testing\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * @package    Kohana/Cache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
#[Group('kohana.cache')]
class Kohana_CacheTest extends TestCase
{
    public const BAD_GROUP_DEFINITION = 1010;
    public const EXPECT_SELF = 1001;

    /**
     * Data provider for test_instance
     *
     * @return  array
     * @throws CacheException
     * @throws KohanaException
     */
    public static function provider_instance(): array
    {
        $base = [];

        if (Application::$config->load('cache.file')) {
            $base = [
                // Test default group
                [
                    null,
                    Cache::instance('file'),
                ],
                // Test defined group
                [
                    'file',
                    Cache::instance('file'),
                ],
            ];
        }

        return $base + [
                // Test bad group definition
                [Kohana_CacheTest::BAD_GROUP_DEFINITION, 'Failed to load Kohana Cache group: 1010'],
            ];
    }

    /**
     * Tests the [Cache::factory()] method behaves as expected
     *
     *
     * @param $group
     * @param $expected
     * @return  void
     * @throws CacheException
     * @throws KohanaException
     */
    #[DataProvider('provider_instance')]
    public function test_instance($group, $expected)
    {
        if ($group === Kohana_CacheTest::BAD_GROUP_DEFINITION) {
            $this->expectException(CacheException::class);
        }

        try {
            $cache = Cache::instance($group);
        } catch (CacheException $e) {
            $this->assertSame($expected, $e->getMessage());
            throw $e;
        }

        $this->assertInstanceOf(get_class($expected), $cache);
        $this->assertSame($expected->config(), $cache->config());
    }

    /**
     * Tests that `clone $cache` will be prevented to maintain singleton
     *
     * @return  void
     */
    public function test_cloning_fails()
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessageIsOrContains('Cloning of Kohana_Cache objects is forbidden');

        $cache = $this->mockAbstract(Cache::class, null);

        clone $cache;
    }

    /**
     * Data provider for test_config
     *
     * @return  array
     */
    public static function provider_config(): array
    {
        return [
            [
                [
                    'server' => 'otherhost',
                    'port' => 5555,
                    'persistent' => true,
                ],
                null,
                Kohana_CacheTest::EXPECT_SELF,
                [
                    'server' => 'otherhost',
                    'port' => 5555,
                    'persistent' => true,
                ],
            ],
            [
                'foo',
                'bar',
                Kohana_CacheTest::EXPECT_SELF,
                [
                    'foo' => 'bar',
                ],
            ],
            [
                'server',
                null,
                null,
                [],
            ],
            [
                null,
                null,
                [],
                [],
            ],
        ];
    }

    /**
     * Tests the config method behaviour
     *
     *
     * @param mixed $key key value to set or get
     * @param mixed $value value to set to key
     * @param mixed $expected_result expected result from [Cache::config()]
     * @param array $expected_config expected config within cache
     * @return  void
     */
    #[DataProvider('provider_config')]
    public function test_config($key, $value, $expected_result, array $expected_config)
    {
        $cache = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        if ($expected_result === Kohana_CacheTest::EXPECT_SELF) {
            $expected_result = $cache;
        }

        $this->assertSame($expected_result, $cache->config($key, $value));
        $this->assertSame($expected_config, $cache->config());
    }

    /**
     * Data provider for test_sanitize_id
     *
     * @return  array
     */
    public static function provider_sanitize_id(): array
    {
        return [
            [
                'foo',
                'foo',
            ],
            [
                'foo+-!@',
                'foo+-!@',
            ],
            [
                'foo/bar',
                'foo_bar',
            ],
            [
                'foo\\bar',
                'foo_bar',
            ],
            [
                'foo bar',
                'foo_bar',
            ],
            [
                'foo\\bar snafu/stfu',
                'foo_bar_snafu_stfu',
            ],
        ];
    }

    /**
     * Tests the [Cache::_sanitize_id()] method works as expected.
     * This uses some nasty reflection techniques to access a protected
     * method.
     *
     *
     * @param string $id id
     * @param string $expected expected
     * @return  void
     * @throws ReflectionException
     */
    #[DataProvider('provider_sanitize_id')]
    public function test_sanitize_id(string $id, string $expected)
    {
        $cache = $this->createStub(Cache::class);

        $cache_reflection = new ReflectionClass($cache);
        $sanitize_id = $cache_reflection->getMethod('sanitizeId');

        $this->assertSame($expected, $sanitize_id->invoke($cache, $id));
    }

}

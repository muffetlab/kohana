<?php

use Kohana\Cache\Cache;
use Kohana\Cache\RedisStore;
use PHPUnit\Framework\Attributes\Group;

include_once __DIR__ . '/CacheArithmeticMethods.php';

#[Group('kohana')]
#[Group('kohana.cache')]
#[Group('kohana.cache.redis')]
class Kohana_RedisTest extends Kohana_CacheArithmeticMethodsTest
{
    public function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis PHP Extension is not available');
        }

        $config = [
            'driver' => 'redis',
            'default_expire' => 3600,
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('REDIS_PORT') ?: 6379),
            'database' => 15,
            'prefix' => 'kohana-test:',
        ];

        try {
            $cache = new class ($config) extends RedisStore {
                public function __construct(array $config)
                {
                    parent::__construct($config);
                }
            };
        } catch (Kohana\Cache\CacheException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $cache->deleteAll();
        $this->cache($cache);
    }

    public function test_round_trips_values_of_every_type()
    {
        $cache = $this->cache();
        $object = new ArrayObject(['a' => 1]);

        foreach (['int' => 42, 'negative' => -7, 'zero' => 0, 'float' => 1.5, 'string' => '0042',
            'numeric string' => '42', 'empty' => '', 'true' => true, 'false' => false, 'array' => ['x' => [1, 2]]] as $id => $value) {
            $this->assertTrue($cache->set($id, $value));
            $this->assertSame($value, $cache->get($id), $id);
        }

        $cache->set('object', $object);
        $this->assertEquals($object, $cache->get('object'));
    }

    public function test_missing_key_returns_default()
    {
        $this->assertNull($this->cache()->get('missing'));
        $this->assertSame('fallback', $this->cache()->get('missing', 'fallback'));
    }

    public function test_lifetime()
    {
        $cache = $this->cache();
        $redis = $cache->redis();

        $cache->set('expiring', 'value', 60);
        $this->assertGreaterThan(0, $redis->ttl('kohana-test:expiring'));

        $cache->set('forever', 'value', 0);
        $this->assertSame(-1, $redis->ttl('kohana-test:forever'));

        $cache->set('default', 'value');
        $this->assertGreaterThan(3000, $redis->ttl('kohana-test:default'));
    }

    public function test_delete_all_keeps_other_prefixes()
    {
        $cache = $this->cache();
        $redis = $cache->redis();

        $redis->set('other-app:key', 'keep');
        $cache->set('mine', 'gone');

        $this->assertTrue($cache->deleteAll());
        $this->assertNull($cache->get('mine'));
        $this->assertSame('keep', $redis->get('other-app:key'));

        $redis->del('other-app:key');
    }

    public function test_tags()
    {
        $cache = $this->cache();

        $cache->setWithTags('post-1', ['title' => 'One'], 60, ['posts', 'home']);
        $cache->setWithTags('post-2', ['title' => 'Two'], 60, ['posts']);
        $cache->set('untagged', 'value');

        $this->assertSame(['post-1' => ['title' => 'One'], 'post-2' => ['title' => 'Two']], $this->sorted($cache->find('posts')));
        $this->assertSame(['post-1' => ['title' => 'One']], $cache->find('home'));
        $this->assertSame([], $cache->find('nothing'));

        // An expired or deleted entry disappears from find()
        $cache->delete('post-2');
        $this->assertSame(['post-1' => ['title' => 'One']], $cache->find('posts'));

        $cache->deleteTag('posts');
        $this->assertNull($cache->get('post-1'));
        $this->assertSame([], $cache->find('posts'));
        $this->assertSame('value', $cache->get('untagged'));
    }

    public function test_instance_from_config()
    {
        Kohana\Foundation\Application::$config->load('cache')->set('redis-test', [
            'driver' => 'redis',
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'database' => 15,
            'prefix' => 'kohana-test:',
        ]);

        $this->assertInstanceOf(RedisStore::class, Cache::instance('redis-test'));
    }

    private function sorted(array $values): array
    {
        ksort($values);

        return $values;
    }
}

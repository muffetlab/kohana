<?php

namespace Kohana\Cache;

use Kohana\Cache\Contracts\Arithmetic;
use Kohana\Cache\Contracts\Tagging;
use Kohana\Support\Arr;

/**
 * [Redis](https://redis.io) cache store, using the phpredis extension
 * (`ext-redis`). Works with Redis and its forks such as Valkey.
 *
 * ### Configuration example
 *
 *     'redis' => [
 *         'driver' => 'redis',
 *         'default_expire' => 3600,
 *         'host' => '127.0.0.1',     // or a unix socket path
 *         'port' => 6379,
 *         'password' => null,        // string, or [username, password] for ACL users
 *         'database' => 0,
 *         'timeout' => 1.0,
 *         'persistent' => false,
 *         'prefix' => 'kohana:',     // keeps keys apart from other applications
 *     ],
 *
 * A lifetime of 0 stores an item without expiry. `deleteAll()` only removes
 * keys with this store's prefix; with an empty prefix it flushes the database.
 *
 * @package    Kohana/Cache
 * @category   Base
 */
class RedisStore extends Cache implements Arithmetic, Tagging
{
    /**
     * Returns false when the key is missing, like Memcached, instead of creating it.
     */
    protected const INCREMENT_SCRIPT = <<<'LUA'
if redis.call('exists', KEYS[1]) == 1 then
    return redis.call('incrby', KEYS[1], ARGV[1])
end
return false
LUA;

    /**
     * @var \Redis Redis connection
     */
    protected $redis;

    /**
     * @var string Prefix added to every key
     */
    protected $prefix;

    protected function __construct(array $config)
    {
        if (!extension_loaded('redis')) {
            throw new CacheException('Redis PHP extension not loaded');
        }

        parent::__construct($config);

        $this->prefix = (string) Arr::get($this->config, 'prefix', 'kohana:');

        $host = Arr::get($this->config, 'host', '127.0.0.1');
        $port = (int) Arr::get($this->config, 'port', str_starts_with($host, '/') ? 0 : 6379);
        $timeout = (float) Arr::get($this->config, 'timeout', 1.0);

        $this->redis = new \Redis();

        try {
            if (Arr::get($this->config, 'persistent')) {
                $this->redis->pconnect($host, $port, $timeout, 'kohana:' . Arr::get($this->config, 'database', 0));
            } else {
                $this->redis->connect($host, $port, $timeout);
            }

            if (($password = Arr::get($this->config, 'password')) !== null) {
                $this->redis->auth($password);
            }

            if ($database = (int) Arr::get($this->config, 'database', 0)) {
                $this->redis->select($database);
            }
        } catch (\RedisException $e) {
            throw new CacheException('Redis could not connect to :host:port: :error', [
                ':host' => $host,
                ':port' => $port ? ':' . $port : '',
                ':error' => $e->getMessage(),
            ], 0, $e);
        }
    }

    /**
     * The underlying phpredis connection, for commands this store does not wrap.
     *
     * @return \Redis
     */
    public function redis(): \Redis
    {
        return $this->redis;
    }

    public function get(string $id, $default = null)
    {
        $value = $this->redis->get($this->key($id));

        return $value === false ? $default : $this->decode($value);
    }

    public function set(string $id, $data, ?int $lifetime = null): bool
    {
        $lifetime ??= (int) Arr::get($this->config, 'default_expire', Cache::DEFAULT_EXPIRE);

        $key = $this->key($id);
        $data = $this->encode($data);

        return $lifetime > 0 ? $this->redis->setex($key, $lifetime, $data) : $this->redis->set($key, $data);
    }

    public function delete(string $id): bool
    {
        return $this->redis->del($this->key($id)) > 0;
    }

    public function deleteAll(): bool
    {
        if ($this->prefix === '') {
            return $this->redis->flushDB();
        }

        // Remove only this store's keys, in batches
        $iterator = null;
        do {
            $keys = $this->redis->scan($iterator, $this->escapePattern($this->prefix) . '*', 1000);

            if ($keys) {
                $this->redis->unlink($keys);
            }
        } while ($iterator > 0);

        return true;
    }

    public function increment(string $id, int $step = 1)
    {
        $result = $this->redis->eval(static::INCREMENT_SCRIPT, [$this->key($id), $step], 1);

        return $result === false ? false : (int) $result;
    }

    public function decrement(string $id, int $step = 1)
    {
        return $this->increment($id, -$step);
    }

    public function setWithTags(string $id, $data, ?int $lifetime = null, ?array $tags = null): bool
    {
        if (!$this->set($id, $data, $lifetime)) {
            return false;
        }

        foreach ((array) $tags as $tag) {
            $this->redis->sAdd($this->tagKey($tag), $id);
        }

        return true;
    }

    public function deleteTag(string $tag)
    {
        $ids = $this->redis->sMembers($this->tagKey($tag)) ?: [];

        $keys = array_map([$this, 'key'], $ids);
        $keys[] = $this->tagKey($tag);

        return $this->redis->unlink($keys) > 0;
    }

    public function find(string $tag): array
    {
        $ids = $this->redis->sMembers($this->tagKey($tag)) ?: [];

        if (!$ids) {
            return [];
        }

        $result = [];
        foreach (array_combine($ids, $this->redis->mGet(array_map([$this, 'key'], $ids))) as $id => $value) {
            // Entries that expired since they were tagged are skipped
            if ($value !== false) {
                $result[$id] = $this->decode($value);
            }
        }

        return $result;
    }

    protected function key(string $id): string
    {
        return $this->prefix . $this->sanitizeId($id);
    }

    protected function tagKey(string $tag): string
    {
        return $this->prefix . 'tag:' . $tag;
    }

    /**
     * Integers are stored as is, so that INCRBY works on them; everything
     * else is serialized. Serialized strings never look like an integer.
     */
    protected function encode($data): string
    {
        return is_int($data) ? (string) $data : serialize($data);
    }

    protected function decode(string $value)
    {
        return preg_match('/^-?\d+$/D', $value) ? (int) $value : unserialize($value);
    }

    protected function escapePattern(string $value): string
    {
        return addcslashes($value, '*?[]\\');
    }
}

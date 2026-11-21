<?php

namespace Kohana\Session;

use Kohana\Cache\Cache;
use Kohana\Cache\RedisStore;
use Kohana\Cookie\Cookie;

/**
 * Stores sessions in Redis (or Valkey), through a Redis store of the cache
 * module. Needs ext-redis.
 *
 *     // config/session.php
 *     'redis' => [
 *         'name' => 'session',
 *         'lifetime' => 0,           // cookie lifetime; 0 = until the browser closes
 *         'ttl' => 7200,             // seconds an idle session is kept in Redis
 *         'store' => 'redis',        // group in config/cache.php
 *         'prefix' => 'session:',    // added after the store's own prefix
 *     ],
 *
 * The session id travels in a cookie; the data never leaves the server.
 *
 * @package    Kohana/Cache
 * @category   Session
 */
class RedisSession extends Session
{
    /**
     * @var \Redis Connection shared with the cache store
     */
    protected $redis;

    /**
     * @var string Key prefix
     */
    protected $prefix;

    /**
     * @var int Seconds Redis keeps an idle session
     */
    protected $ttl;

    /**
     * @var string|null Current session id
     */
    protected $sessionId;

    /**
     * @var string|null Id stored in Redis, replaced when the id is regenerated
     */
    protected $storedId;

    public function __construct(?array $config = null, ?string $id = null)
    {
        $config = (array) $config;
        $store = Cache::instance($config['store'] ?? 'redis');

        if (!$store instanceof RedisStore) {
            throw new SessionException('The session store ":store" must use the redis cache driver', [':store' => $config['store'] ?? 'redis']);
        }

        $this->redis = $store->redis();
        $this->prefix = (string) ($store->config('prefix') ?? 'kohana:') . ($config['prefix'] ?? 'session:');
        $this->ttl = (int) ($config['ttl'] ?? (($config['lifetime'] ?? 0) ?: ini_get('session.gc_maxlifetime') ?: 7200));

        parent::__construct($config, $id);
    }

    public function id(): ?string
    {
        return $this->sessionId;
    }

    protected function readSession(?string $id = null)
    {
        $id ??= Cookie::get($this->name);

        if (is_string($id) && preg_match('/^[a-f0-9]{40}$/', $id) && ($data = $this->redis->get($this->prefix . $id)) !== false) {
            $this->sessionId = $this->storedId = $id;

            return $data;
        }

        $this->regenerateSession();

        return null;
    }

    protected function regenerateSession(): string
    {
        return $this->sessionId = bin2hex(random_bytes(20));
    }

    protected function writeSession(): bool
    {
        $this->redis->setex($this->prefix . $this->sessionId, $this->ttl, $this->__toString());

        if ($this->storedId !== null && $this->storedId !== $this->sessionId) {
            // The id was regenerated; the old one must stop working
            $this->redis->del($this->prefix . $this->storedId);
        }

        $this->storedId = $this->sessionId;

        $this->sendCookie($this->sessionId);

        return true;
    }

    protected function restartSession(): bool
    {
        $this->regenerateSession();

        return true;
    }

    protected function destroySession(): bool
    {
        if ($this->storedId !== null) {
            $this->redis->del($this->prefix . $this->storedId);
        }

        $this->deleteCookie();

        $this->sessionId = $this->storedId = null;

        return true;
    }
}

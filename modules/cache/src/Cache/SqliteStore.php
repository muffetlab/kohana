<?php

namespace Kohana\Cache;

use Kohana\Cache\Contracts\Tagging;
use Kohana\Cache\Contracts\GarbageCollect;
use Kohana\Support\Arr;

/**
 * Kohana Cache Sqlite Driver
 *
 * Requires SQLite3 and PDO
 *
 * @package    Kohana/Cache
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
class SqliteStore extends Cache implements Tagging, GarbageCollect
{
    /**
     * Database resource
     *
     * @var  PDO
     */
    protected $db;

    /**
     * Sets up the PDO SQLite table and
     * initialises the PDO connection
     *
     * @param  array  $config  configuration
     * @throws  Cache_Exception
     */
    protected function __construct(array $config)
    {
        parent::__construct($config);

        $database = Arr::get($this->config, 'database');

        if ($database === null) {
            throw new CacheException('Database path not available in Kohana Cache configuration');
        }

        // Load new Sqlite DB
        $this->db = new \PDO('sqlite:' . $database);

        // Test for existing DB
        $result = $this->db->query("SELECT * FROM sqlite_master WHERE name = 'caches' AND type = 'table'")->fetchAll();

        // If there is no table, create a new one
        if (0 === count($result)) {
            $database_schema = Arr::get($this->config, 'schema');

            if ($database_schema === null) {
                throw new CacheException('Database schema not found in Kohana Cache configuration');
            }

            try {
                // Create the caches table
                $this->db->query(Arr::get($this->config, 'schema'));
            } catch (\PDOException $e) {
                throw new CacheException('Failed to create new SQLite caches table with the following error : :error', [':error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Retrieve a value based on an id
     *
     * @param string $id ID of cache entry.
     * @param mixed $default Default value to return if ID not found
     * @return  mixed
     * @throws  Cache_Exception
     */
    public function get(string $id, $default = null)
    {
        // Prepare statement
        $statement = $this->db->prepare('SELECT id, expiration, cache FROM caches WHERE id = :id LIMIT 0, 1');

        // Try and load the cache based on id
        try {
            $statement->execute([':id' => $this->sanitizeId($id)]);
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        if (!$result = $statement->fetch(\PDO::FETCH_OBJ)) {
            return $default;
        }

        // If the cache has expired
        if ((int) $result->expiration !== 0 && $result->expiration <= time()) {
            // Delete it and return default value
            $this->delete($id);
            return $default;
        }
        // Otherwise return cached object
        else {
            // Disable notices for unserializing
            $ER = error_reporting(~E_NOTICE);

            // Return the valid cache data
            $data = unserialize($result->cache);

            // Turn notices back on
            error_reporting($ER);

            // Return the resulting data
            return $data;
        }
    }

    /**
     * Set a value based on an id. Optionally add tags.
     *
     * @param string $id ID of cache entry
     * @param mixed $data Data to set to cache
     * @param int|null $lifetime Lifetime in seconds
     * @return bool
     * @throws Cache_Exception
     */
    public function set(string $id, $data, ?int $lifetime = null): bool
    {
        return $this->setWithTags($id, $data, $lifetime);
    }

    /**
     * Delete a cache entry based on id
     *
     * @param string $id ID of cache entry to delete.
     * @return  bool
     * @throws  Cache_Exception
     */
    public function delete(string $id): bool
    {
        // Prepare statement
        $statement = $this->db->prepare('DELETE FROM caches WHERE id = :id');

        // Remove the entry
        try {
            $statement->execute([':id' => $this->sanitizeId($id)]);
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        return (bool) $statement->rowCount();
    }

    /**
     * Delete all cache entries
     *
     * @return bool
     * @throws Cache_Exception
     */
    public function deleteAll(): bool
    {
        // Prepare statement
        $statement = $this->db->prepare('DELETE FROM caches');

        // Remove the entry
        try {
            $statement->execute();
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        return (bool) $statement->rowCount();
    }

    /**
     * Set a value based on an id. Optionally add tags.
     *
     * @param string $id ID of cache entry
     * @param mixed $data Data to set to cache
     * @param int|null $lifetime Lifetime in seconds
     * @param array|null $tags Tags to associate with the cache entry
     * @return  bool
     * @throws Cache_Exception
     */
    public function setWithTags(string $id, $data, ?int $lifetime = null, ?array $tags = null): bool
    {
        // Serialize the data
        $data = serialize($data);

        // Normalise tags
        $tags = null === $tags ? null : '<' . implode('>,<', $tags) . '>';

        // Setup lifetime
        if ($lifetime === null) {
            $lifetime = 0 === Arr::get($this->config, 'default_expire') ? 0 : Arr::get($this->config, 'default_expire', Cache::DEFAULT_EXPIRE) + time();
        } else {
            $lifetime = 0 === $lifetime ? 0 : $lifetime + time();
        }

        // Prepare statement
        // $this->exists() may throw Cache_Exception, no need to catch/rethrow
        $statement = $this->exists($id) ? $this->db->prepare('UPDATE caches SET expiration = :expiration, cache = :cache, tags = :tags WHERE id = :id') : $this->db->prepare('INSERT INTO caches (id, cache, expiration, tags) VALUES (:id, :cache, :expiration, :tags)');

        // Try to insert
        try {
            $statement->execute([':id' => $this->sanitizeId($id), ':cache' => $data, ':expiration' => $lifetime, ':tags' => $tags]);
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        return (bool) $statement->rowCount();
    }

    /**
     * Delete cache entries based on a tag
     *
     * @param string $tag Tag label identifying cache entries to be deleted.
     * @return  bool
     * @throws  Cache_Exception
     */
    public function deleteTag(string $tag): bool
    {
        // Prepare the statement
        $statement = $this->db->prepare('DELETE FROM caches WHERE tags LIKE :tag');

        // Try to delete
        try {
            $statement->execute([':tag' => "%<$tag>%"]);
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        return (bool) $statement->rowCount();
    }

    /**
     * Find cache entries based on a tag
     *
     * @param string $tag Tag label used to find associated cache entries.
     * @return  array
     * @throws  Cache_Exception
     */
    public function find(string $tag): array
    {
        // Prepare the statement
        $statement = $this->db->prepare('SELECT id, cache FROM caches WHERE tags LIKE :tag');

        // Try to find
        try {
            if (!$statement->execute([':tag' => "%<$tag>%"])) {
                return [];
            }
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        $result = [];

        while ($row = $statement->fetchObject()) {
            // Disable notices for unserializing
            $ER = error_reporting(~E_NOTICE);

            $result[$row->id] = unserialize($row->cache);

            // Turn notices back on
            error_reporting($ER);
        }

        return $result;
    }

    /**
     * Garbage collection method that cleans any expired
     * cache entries from the cache.
     *
     * @return  void
     * @throws Cache_Exception
     */
    public function garbageCollect()
    {
        // Create the sequel statement
        $statement = $this->db->prepare('DELETE FROM caches WHERE expiration < :expiration');

        try {
            $statement->execute([':expiration' => time()]);
        } catch (\PDOException $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }
    }

    /**
     * Tests whether an id exists or not
     *
     * @param string $id ID
     * @return  bool
     * @throws  Cache_Exception
     */
    protected function exists(string $id): bool
    {
        $statement = $this->db->prepare('SELECT id FROM caches WHERE id = :id');
        try {
            $statement->execute([':id' => $this->sanitizeId($id)]);
        } catch (\PDOExeption $e) {
            throw new CacheException('There was a problem querying the local SQLite3 cache. :error', [':error' => $e->getMessage()]);
        }

        return (bool) $statement->fetchAll();
    }

}

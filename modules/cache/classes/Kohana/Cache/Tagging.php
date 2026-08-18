<?php

/**
 * Kohana Cache Tagging Interface
 *
 * @package    Kohana/Cache
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
 * @license    https://kohana.top/license
 */
interface Kohana_Cache_Tagging
{
    /**
     * Set a value based on an id. Optionally add tags.
     *
     * Note : Some caching engines do not support
     * tagging
     *
     * @param string $id ID of cache entry
     * @param mixed $data Data to set to cache
     * @param int|null $lifetime Lifetime in seconds
     * @param array|null $tags Tags to associate with the cache entry
     * @return  bool
     */
    public function set_with_tags(string $id, $data, int $lifetime = null, array $tags = null): bool;
    /**
     * Delete cache entries based on a tag
     *
     * @param string $tag Tag label identifying cache entries to be deleted.
     */
    public function delete_tag(string $tag);
    /**
     * Find cache entries based on a tag
     *
     * @param string $tag Tag label used to find associated cache entries.
     * @return  array
     */
    public function find(string $tag): array;
}

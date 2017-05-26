<?php

namespace TheGallagher\WordPressPsrCache;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Simple Cache (PSR-16) implementation which proxies a PSR-6 CacheItemPoolInterface
 */
class SimpleCache implements CacheInterface
{
    /**
     * The cache item pool proxied by this object
     *
     * @var CacheItemPoolInterface
     */
    protected $cacheItemPool;

    /**
     * SimpleCache constructor.
     *
     * @param CacheItemPoolInterface|null $cacheItemPool
     */
    public function __construct(CacheItemPoolInterface $cacheItemPool = null)
    {
        $this->setCacheItemPool($cacheItemPool);
    }

    /**
     * Get the cache item pool proxied by this object
     *
     * @return CacheItemPoolInterface
     */
    public function getCacheItemPool()
    {
        return $this->cacheItemPool;
    }

    /**
     * Set the cache item pool proxied by this object
     *
     * Defaults to TheGallagher\WordPressPsrCache\CacheItemPool
     *
     * @param CacheItemPoolInterface|null $cacheItemPool
     */
    public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool = null)
    {
        if ($cacheItemPool === null) {
            $cacheItemPool = new CacheItemPool();
        }
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        $item = $this->getCacheItemPool()->getItem($key);
        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $cacheItemPool = $this->getCacheItemPool();
        $item = $cacheItemPool->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        return $cacheItemPool->save($item);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        return $this->getCacheItemPool()->deleteItem($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->getCacheItemPool()->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param array|\Traversable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return array|\Traversable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $this->validateIterator($keys);
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param array|\Traversable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->validateIterator($values);
        $success = true;
        foreach ($values as $key => $value) {
            $success = $success && $this->set($key, $value, $ttl);
        }
        return $success;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param array|\Traversable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        $this->validateIterator($keys);
        $success = true;
        foreach ($keys as $key) {
            $success = $success && $this->delete($key);
        }
        return $success;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        return $this->getCacheItemPool()->hasItem($key);
    }

    /**
     * Make sure keys can be iterated
     *
     * @param mixed $keys
     */
    protected function validateIterator($keys)
    {
        if (!is_array($keys) && !($keys instanceof \Traversable)) {
            throw new InvalidArgumentException('$keys must be an array or traversable.');
        }
    }
}
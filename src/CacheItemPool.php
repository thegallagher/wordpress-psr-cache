<?php

namespace TheGallagher\WordPressPsrCache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * CacheItemPoolInterface (PSR-6) implementation for WordPress using transients
 */
class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        $this->validateKey($key);
        return new CacheItem($key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = array())
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        $this->validateKey($key);
        return get_site_transient($key) !== false;
    }

    /**
     * Deletes all items in the pool (not implemented).
     *
     * Any implementation of this method with WordPress transients would be
     * reliant on the implementation of transients, rather than the interface.
     *
     * @return bool
     *   This method is not implemented and therefore always returns false.
     */
    public function clear()
    {
        return false;
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        $this->validateKey($key);
        return delete_site_transient($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $success && $this->deleteItem($key);
        }
        return $success;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItem|CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        if (!($item instanceof CacheItem)) {
            return false;
        }

        $expiration = $item->getExpiration();
        $now = new \DateTimeImmutable();
        if ($expiration instanceof \DateInterval) {
            $expiration = $now->add($expiration);
        }

        $ttl = 0;
        if ($expiration instanceof \DateTimeInterface) {
            $ttl = $expiration->getTimestamp() - $now->getTimestamp();
        }

        try {
            $value = serialize($item->get());
        } catch (\Exception $e) {
            return false;
        }

        return set_site_transient($item->getKey(), $value, $ttl);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * There is no advantage to deferring cache saves with WordPress transients
     * so this method proxies $this->save().
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->save($item);
    }

    /**
     * Persists any deferred cache items.
     *
     * There is no advantage to deferring cache saves with WordPress transients.
     *
     * @return bool
     *   True always returns true.
     */
    public function commit()
    {
        return true;
    }

    /**
     * Make sure key is valid
     *
     * @param mixed $key
     */
    protected function validateKey($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('$key must a string.');
        }

        if ($key === '') {
            throw new InvalidArgumentException('$key must be at least 1 character.');
        }

        if (strlen($key) > 167) {
            throw new InvalidArgumentException('$key must be less than 168 characters.');
        }

        if (preg_match('%[{}()/\\\\@:]%', $key)) {
            throw new InvalidArgumentException('$key must not contain characters "{}()/\@:".');
        }
    }
}
<?php

namespace TheGallagher\WordPressPsrCache;

use Psr\Cache\CacheItemInterface;

/**
 * CacheItemInterface (PSR-6) implementation for WordPress using transients
 */
class CacheItem implements CacheItemInterface
{
    /**
     * The key for the current cache item
     *
     * @var string
     */
    protected $key;

    /**
     * Confirms if the cache item lookup resulted in a cache hit
     *
     * @var bool
     */
    protected $isHit = null;

    /**
     * The value of the item from the cache associated with this object's key
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * The expiration TTL or time
     *
     * @var \DateTimeInterface|\DateInterval|null
     */
    protected $expiration = null;

    /**
     * CacheItem constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {
        $this->fetchTransient();
        return $this->value;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit()
    {
        $this->fetchTransient();
        return $this->isHit;
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {
        if ($this->isHit === null) {
            $this->isHit = false;
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param \DateTimeInterface|null $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration)
    {
        $this->expiration = $expiration;
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time)
    {
        if (is_int($time)) {
            $time = \DateInterval::createFromDateString($time . ' seconds');
        }
        $this->expiration = $time;
        return $this;
    }

    /**
     * Get the expiration TTL or time
     *
     * @return \DateTimeInterface|\DateInterval|null
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Load the transient value
     */
    protected function fetchTransient()
    {
        if ($this->isHit !== null) {
            return;
        }

        $serialized = get_site_transient($this->getKey());
        $this->isHit = $serialized !== false;
        if (!$this->isHit) {
            return;
        }

        $value = unserialize($serialized);
        $this->isHit = $value !== false || $serialized === serialize(false);
        if (!$this->isHit) {
            return;
        }

        $this->value = $value;
    }
}
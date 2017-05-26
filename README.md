# WordPress PSR-6 and PSR-16 Implementation Using Transients

This library should be considered alpha and could change at any time. It is recommended that you specify a commit when using composer.

## Installation

```
composer require thegallagher/wordpress-psr-cache:dev-master@dev
```

## Usage Examples

```php
<?php
// Include composer autoloader
require 'vendor/autoload.php';

// PSR-6
use TheGallagher\WordPressPsrCache\CacheItemPool;

$cacheKey = 'the_cache_item_key';
$cacheTtl = 600;
$cacheItemPool = new CacheItemPool();
$cacheItem = $cacheItemPool->getItem($cacheKey);
if (!$cacheItem->isHit()) {
    $value = SomeClass::someLongRunningMethod();
    $cacheItem->set($value);
    $cacheItem->expiresAfter($cacheTtl);
}
echo $cacheItem->get();

// PSR-16
use TheGallagher\WordPressPsrCache\SimpleCache;

$cacheKey = 'the_cache_item_key';
$cacheTtl = 600;
$cache = new SimpleCache();
$value = $cache->get($cacheKey);
if ($value === null) {
    $value = SomeClass::someLongRunningMethod();
    $cache->set($cacheKey, $value, $cacheTtl);
}
echo $value;
?>
```

## Notes
* Stored values are effectively serialized twice, because the Transient API has
  no way to tell the difference between storing false and a cache miss.
* `CacheItemPool::clear()` always fails because the Transient API doesn't allow
  all values in transients to be cleared. This also applies to
  `SimpleCache::clear()` by default, since it proxies `CacheItemPool`.

## License

The library is open-sourced software licensed under the MIT license.
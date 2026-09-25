---
name: nette-caching
description: Invoke before writing or reviewing code that uses nette/caching. Covers the Cache object and namespaces, load() with a generator callback, save(), remove(), clean(), the dependency array (Cache::Expire, Cache::Tags, Cache::Files, Cache::Items, Cache::Priority, Cache::Sliding, Cache::Callbacks), the Journal requirement for tags and priorities, bulkLoad(), output capture, storages (FileStorage, MemcachedStorage, SQLiteStorage, MemoryStorage, DevNullStorage) and the {cache} Latte tag. Also trigger for cache.storage or cache.journal in NEON, invalidation by tag, cache stampede, or disabling the cache in tests. Not for the Latte template cache or the DI container cache, which are separate mechanisms.
---

## Nette Caching

```shell
composer require nette/caching
```

### Dependency Constants – Exact Names

Since 3.1.3 the constants are **PascalCase**; the UPPER_CASE names survive as deprecated
aliases, which makes wrong guesses fail in confusing ways.

```php
Cache::Expire      // 'expire'   – NOT Cache::Expiration (that constant does not exist)
Cache::Sliding     // 'sliding'
Cache::Tags        // 'tags'
Cache::Files       // 'files'     – resolved with filemtime(), so a relative path depends on the CWD; use absolute
Cache::Items       // 'items'     – other cache keys
Cache::Constants   // 'consts'
Cache::Callbacks   // 'callbacks' – [callable, ...args], must return true to stay valid.
                   //               The callable is SERIALIZED with the entry, so a closure is
                   //               a fatal ("Serialization of 'Closure' is not allowed") –
                   //               use a function name or [Class::class, 'method'].
Cache::Priority    // 'priority'
Cache::All         // 'all'       – clean() only
```

`Cache::EXPIRATION` **does** exist (deprecated alias of `Expire`); `Cache::Expiration`
does **not** and is a fatal `Undefined constant`. There is likewise no `forget()`, no
`delete()` and no `has()` – removal is `remove($key)`, which is just `save($key, null)`.

### Reading and Writing

```php
$cache = new Nette\Caching\Cache($storage, 'my-namespace'); // Nette\Caching\Storage is autowired
$sub = $cache->derive('images');                            // nested namespace
$value = $cache->load($key, function (&$dependencies) {
	$dependencies[Cache::Expire] = '20 minutes';
	$dependencies[Cache::Tags] = ["article/$id"];
	return expensiveComputation();
});
```

- The generator gets `$dependencies` **by reference**; the same array can instead be the
  third argument of `load()` or `save()`. If it throws, the entry is removed.
- `load($key)` without a generator returns `null` on a miss, so `null` is not storable:
  `save($key, null)` removes the entry, as does an `Expire` resolving to `<= 0`.
- `Cache::Expire` accepts seconds, a UNIX timestamp, or any `DateTime::from()` string.
  Keys may be any serializable value, not just strings; `save()` returns the value.

### Tags and Priorities Need a Journal

```php
$cache->clean([Cache::Tags => ["article/$id"]]);
$cache->clean([Cache::Priority => 100]);   // removes priority <= 100; Cache::All => true wipes everything
```

`FileStorage` and `MemcachedStorage` throw
`Nette\InvalidStateException: CacheJournal has not been provided.` on the **write** as
soon as `Cache::Tags` or `Cache::Priority` is present and no journal was injected. The
default journal is `SQLiteJournal`, and the DI extension registers it **only if
`pdo_sqlite` is loaded** – without that extension every tagged `save()` blows up at
runtime. `SQLiteStorage` implements tags in its own table and needs no journal, but it stores
no metadata: only `Expire`, `Sliding` and `Tags` survive, while `Priority`, `Files`, `Items`,
`Constants` and `Callbacks` are silently ignored, so entries relying on those never invalidate.
`MemoryStorage` honours only `Cache::All` and silently drops every
dependency; `MemcachedStorage` also throws `NotSupportedException` for `Cache::Items`.

```php
$values = $cache->bulkLoad($keys, fn($key, &$dependencies) => compute($key)); // scalar keys only
$cache->bulkSave([$k1 => $v1, $k2 => $v2], [Cache::Expire => '20 minutes']);

if ($capture = $cache->capture($key)) {   // start() is the deprecated old name
	echo $renderedHtml;
	$capture->end();
}
```

### `{cache}` in Latte

```latte
{cache $id, expire: '20 minutes', tags: [tag1, tag2], if: !$form->isSubmitted()}
```

All arguments are optional, but **the tag defaults to a 7-day expiration**, unlike the
PHP API which caches indefinitely. The block is invalidated automatically when the
template file changes, and invalidating a nested block invalidates its parents.

### Configuration

There is **no `caching:` NEON section**; the extension is named `cache` and takes no
options at all. Everything is plain services:

```neon
services:
	cache.storage: Nette\Caching\Storages\DevNullStorage       # disables caching, e.g. in tests
	cache.journal: MyJournal
	- ClassTwo( Nette\Caching\Cache(namespace: 'my-namespace') )   # to inject Cache directly
```

`FileStorage` is the default and is the only source of cache-stampede protection:
concurrent requests for a cold key block until the first has generated it. Swapping in
`DevNullStorage` does not disable the Latte or DI container caches – they do not use
`nette/caching`.

### Online Documentation

For details, see the official documentation:

- [Caching](https://doc.nette.org/en/caching) – dependencies, journal, storages, `{cache}`
- [Caching Upgrading](https://doc.nette.org/en/caching/upgrading) – renamed constants and interfaces

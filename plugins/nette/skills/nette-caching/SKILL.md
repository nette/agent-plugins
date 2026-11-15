---
name: nette-caching
description: Invoke before writing or reviewing code that uses nette/caching. Covers the Cache object and namespaces, load() with a generator callback, save(), remove(), clean() with named conditions, the Dependencies object and the Cache::* dependency array (Expire, Tags, Files, Items, Priority, Sliding, Callbacks), the Journal requirement for tags and priorities, bulkLoad(), output capture, storages (FileStorage, RedisStorage, MemcachedStorage, SQLiteStorage, MemoryStorage, Psr16Storage, DevNullStorage), PSR-16 adapters and the {cache} Latte tag. Also trigger for cache.storage or cache.journal in NEON, invalidation by tag, cache stampede, or disabling the cache in tests. Not for the Latte template cache or the DI container cache, which are separate mechanisms.
---

## Nette Caching

```shell
composer require nette/caching
```

Version 4.0 requires PHP 8.4. What differs from 3.x is in [Upgrading](../../docs/caching/upgrading.md);
check the installed version before relying on `Dependencies` or named arguments of `clean()`.

### Dependencies

Since 4.0 dependencies are the object `Nette\Caching\Dependencies` with the properties `expire`,
`sliding`, `tags`, `priority`, `files`, `items`, `constants` and `callbacks`. A generator whose
parameter is **typed** `Dependencies` gets the object; an untyped one gets the array by reference.

```php
use Nette\Caching\Cache;
use Nette\Caching\Dependencies;

$cache = new Cache($storage, 'articles'); // Nette\Caching\Storage is autowired
$sub = $cache->derive('previews');        // nested namespace
$html = $cache->load("article/$id", function (Dependencies $dp) use ($id) {
	$dp->expire = '20 minutes';
	$dp->tags = ["article/$id"];
	return renderArticle($id);
});
$cache->save($key, $value, new Dependencies(expire: '1 hour', tags: ['articles']));
```

The array form with `Cache::*` keys still works. Since 4.0 an unknown key or a wrong type throws,
typically a priority written as the string `'50'`. The constants are **PascalCase**:

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
Cache::Priority    // 'priority'  – int
```

`Cache::EXPIRATION` and the other UPPER_CASE names are deprecated aliases (`E_USER_DEPRECATED`
since 4.0); `Cache::Expiration` does **not** exist and is a fatal `Undefined constant`. There
is likewise no `forget()`, no `delete()` and no `has()` – removal is `remove($key)`.

### Reading and Writing

- Prefer `load()` with a generator to `save()`: only with a generator do `FileStorage` and
  `RedisStorage` protect against cache stampede. If the generator throws, nothing is stored.
  A generator must not `load()` its own key: `FileStorage` throws `InvalidStateException`.
- `load($key)` without a generator returns `null` on a miss, so `null` is not storable:
  `save($key, null)` removes the entry, as does an `expire` resolving to `<= 0`.
- `expire` accepts seconds, a UNIX timestamp, a `DateTimeInterface` or any `DateTime::from()`
  string. Keys may be any serializable value, not just strings; `save()` returns the value.
- `save()` with a closure as the data is deprecated; use `load()`.

### Cleaning

```php
$cache->clean(tags: ["article/$id"]);  // in every namespace of the storage
$cache->clean(priority: 50);           // priority <= 50; items without priority stay
$cache->clean(namespaces: ['articles']);
$cache->clean(all: true);              // only this namespace and those derived from it
```

Since 4.0 `clean()` takes named arguments (3.x: `clean([Cache::Tags => [...]])`, which still
works). `clean()` with no condition throws; expired items are removed by `$storage->clean([])`.
`all` on a cache **with a namespace** no longer wipes the storage, only a cache without one does.

### Storages and the Journal

A storage throws `Nette\NotSupportedException` for a dependency or condition it cannot honor
(3.x silently ignored them):

| Storage | Tags, priority | Files, constants, callbacks | Items | Sliding | Clean namespaces | Stampede |
| --- | --- | --- | --- | --- | --- | --- |
| `FileStorage` (default) | journal | yes | yes | yes | yes | yes |
| `RedisStorage` | yes | yes | no | yes | yes | yes |
| `MemcachedStorage` | journal | yes | no | yes | no | no |
| `SQLiteStorage` | tags only | no | no | yes | yes | no |
| `MemoryStorage` | yes | **ignored** | **ignored** | yes | yes | no |
| `Psr16Storage` | journal | yes | no | no | no | no |

"journal": without one, a write with tags or priority throws
`Nette\InvalidStateException: CacheJournal has not been provided.` The default journal is
`SQLiteJournal`, and the DI extension registers it **only if `pdo_sqlite` is loaded** – without
that extension every tagged `save()` blows up at runtime. `MemoryStorage` silently ignores what it
cannot do, so in tests an item that would expire in production stays.

`Nette\Bridges\Psr\PsrCacheAdapter` exposes a storage as PSR-16 and since 4.0 rejects keys
containing `{}()/\@:` such as `user:1`; `Psr16Storage` is the opposite direction.

```php
$values = $cache->bulkLoad($keys, fn($key, Dependencies $dp) => compute($key)); // scalar keys only
$cache->bulkSave([$k1 => $v1, $k2 => $v2], new Dependencies(expire: '20 minutes'));

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
nette/caching 4.0 requires Latte 3.1 for the tag.

### Configuration

There is **no `caching:` NEON section**; the extension is named `cache` and takes no
options at all. Everything is plain services:

```neon
services:
	cache.storage: Nette\Caching\Storages\DevNullStorage       # disables caching, e.g. in tests
	cache.journal: MyJournal
	- ClassTwo( Nette\Caching\Cache(namespace: 'my-namespace') )   # to inject Cache directly
```

The aliases `cacheStorage` and `nette.cacheJournal` are gone in 4.0. Swapping in
`DevNullStorage` does not disable the Latte or DI container caches – they do not use
`nette/caching`.

### Documentation

The full Nette documentation for this topic is bundled with the plugin. Read a page when you need details beyond this skill:

- [Caching](../../docs/caching/index.md) – overview, namespaces, memoization, `{cache}`
- [Expiration and Invalidation](../../docs/caching/expiration.md) – dependencies, tags, priority, bulk operations
- [Storages](../../docs/caching/storages.md) – choosing and configuring a storage, the journal, PSR-16
- [Caching Upgrading](../../docs/caching/upgrading.md) – changes in 4.0, renamed constants and interfaces

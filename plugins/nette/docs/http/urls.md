# Working with URLs

Contents:

- [Url](#url)
  - [URL Components](#url-components)
  - [`getDomain(int $level = 2): string`](#getdomainint-level--2-string)
  - [`isEqual(string|Url $url): bool`](#isequalstringurl-url-bool)
  - [`canonicalize()`](#canonicalize)
  - [`Url::isAbsolute(string $url): bool` (since 3.3.2)](#urlisabsolutestring-url-bool-since-332)
  - [`Url::removeDotSegments(string $path): string` (since 3.3.2)](#urlremovedotsegmentsstring-path-string-since-332)
- [UrlImmutable](#urlimmutable)
  - [URL Components](#url-components-1)
  - [`getDomain(int $level = 2): string`](#getdomainint-level--2-string-1)
  - [`resolve(string $reference): UrlImmutable` (since 3.3.2)](#resolvestring-reference-urlimmutable-since-332)
  - [`isEqual(string|Url $url): bool`](#isequalstringurl-url-bool-1)
- [UrlScript](#urlscript)


The [Url](#url), [UrlImmutable](#urlimmutable), and [UrlScript](#urlscript) classes make it easy to generate, parse, and manipulate URLs.

→ [Installation and requirements](https://doc.nette.org/en/http#toc-installation)

## Url

The [Nette\Http\Url](https://api.nette.org/http/master/Nette/Http/Url.html) class allows easy manipulation of URLs and their individual components, as depicted in this diagram:

```
scheme  user  password  host   port    path        query  fragment
  |      |      |        |      |       |            |       |
/--\   /--\ /------\ /-------\ /--\/----------\ /--------\ /----\
http://john:xyz%2A12@nette.org:8080/en/download?name=param#footer
\______\__________________________/
    |               |
 hostUrl        authority
```

Generating URLs is intuitive:

```php
use Nette\Http\Url;

$url = new Url;
$url->setScheme('https')
	->setHost('localhost')
	->setPath('/edit')
	->setQueryParameter('foo', 'bar');

echo $url; // 'https://localhost/edit?foo=bar'
```

You can also parse a URL and then manipulate it:

```php
$url = new Url(
	'http://john:xyz%2A12@nette.org:8080/en/download?name=param#footer',
);
```

The `Url` class implements the `JsonSerializable` interface and has a `__toString()` method, so the object can be printed or used in data passed to `json_encode()`.

```php
echo $url;
echo json_encode([$url]);
```

### URL Components

The following methods are available to retrieve or modify individual URL components:

| Setter | Getter | Returned value |
| --- | --- | --- |
| `setScheme(string $scheme)` | `getScheme(): string` | `'http'` |
| `setUser(string $user)` | `getUser(): string` | `'john'` |
| `setPassword(string $password)` | `getPassword(): string` | `'xyz*12'` |
| `setHost(string $host)` | `getHost(): string` | `'nette.org'` |
| `setPort(int $port)` | `getPort(): ?int` | `8080` |
|   | `getDefaultPort(): ?int` | `80` |
| `setPath(string $path)` | `getPath(): string` | `'/en/download'` |
| `setQuery(string\|array $query)` | `getQuery(): string` | `'name=param'` |
| `setFragment(string $fragment)` | `getFragment(): string` | `'footer'` |
|   | `getAuthority(): string` | `'john:xyz%2A12@nette.org:8080'` |
|   | `getHostUrl(): string` | `'http://john:xyz%2A12@nette.org:8080'` |
|   | `getAbsoluteUrl(): string` | entire URL |

The `getUser()`, `getPassword()`, `setUser()`, and `setPassword()` methods are deprecated, because embedding credentials directly in a URL is discouraged.

Warning: When working with a URL obtained from an [HTTP request](request.md), keep in mind that it will not contain the fragment, as the browser does not send it to the server.

We can also work with individual query parameters using:

| Setter | Getter |
| --- | --- |
| `setQuery(string\|array $query)` | `getQueryParameters(): array` |
| `setQueryParameter(string $name, $val)` | `getQueryParameter(string $name)` |
| \`appendQuery(string | array $query)\` |

### `getDomain(int $level = 2): string`

Returns the right or left part of the host. Here's how it works if the host is `www.nette.org`:

|  |  |
| --- | --- |
| `getDomain(1)` | `'org'` |
| `getDomain(2)` | `'nette.org'` |
| `getDomain(3)` | `'www.nette.org'` |
| `getDomain(0)` | `'www.nette.org'` |
| `getDomain(-1)` | `'www.nette'` |
| `getDomain(-2)` | `'www'` |
| `getDomain(-3)` | `''` |

### `isEqual(string|Url $url): bool`

Checks if two URLs are identical.

```php
$url->isEqual('https://nette.org');
```

### `canonicalize()`

Converts the URL to canonical form. This converts the hostname to lowercase and normalizes the path (percent-encoding and removing redundant characters). The query string is left unchanged.

### `Url::isAbsolute(string $url): bool` (since 3.3.2)

Checks if a URL is absolute. A URL is considered absolute if it begins with a scheme (e.g., http, https, ftp) followed by a colon.

```php
Url::isAbsolute('https://nette.org');    // true
Url::isAbsolute('//nette.org');          // false
```

### `Url::removeDotSegments(string $path): string` (since 3.3.2)

Normalizes a URL path by removing special segments `.` and `..`. This method removes redundant path elements in the same way web browsers do.

```php
Url::removeDotSegments('/path/../subtree/./file.txt');  // '/subtree/file.txt'
Url::removeDotSegments('/../foo/./bar');                // '/foo/bar'
Url::removeDotSegments('./today/../file.txt');          // 'file.txt'
```

## UrlImmutable

The [Nette\Http\UrlImmutable](https://api.nette.org/http/master/Nette/Http/UrlImmutable.html) class is an immutable alternative to the [Url](#url) class (similar to how `DateTimeImmutable` is an immutable alternative to `DateTime` in PHP). Instead of setters, it has “withers,” which do not change the object but return new instances with the modified value:

```php
use Nette\Http\UrlImmutable;

$url = new UrlImmutable(
	'https://nette.org:8080/en/download?name=param#footer',
);

$newUrl = $url
	->withHost('example.com')
	->withPath('/en/')
	->withQueryParameter('name', 'value');

echo $newUrl; // 'https://example.com:8080/en/?name=value#footer'
```

The `UrlImmutable` class implements the `JsonSerializable` interface and has a `__toString()` method, so the object can be printed or used in data passed to `json_encode()`.

```php
echo $url;
echo json_encode([$url]);
```

### URL Components

The following methods are available to retrieve or change individual URL components:

| Wither | Getter | Returned value |
| --- | --- | --- |
| `withScheme(string $scheme)` | `getScheme(): string` | `'http'` |
| `withUser(string $user)` | `getUser(): string` | `'john'` |
| `withPassword(string $password)` | `getPassword(): string` | `'xyz*12'` |
| `withHost(string $host)` | `getHost(): string` | `'nette.org'` |
| `withPort(int $port)` | `getPort(): ?int` | `8080` |
|   | `getDefaultPort(): ?int` | `80` |
| `withPath(string $path)` | `getPath(): string` | `'/en/download'` |
| `withQuery(string\|array $query)` | `getQuery(): string` | `'name=param'` |
| `withFragment(string $fragment)` | `getFragment(): string` | `'footer'` |
|   | `getAuthority(): string` | `'john:xyz%2A12@nette.org:8080'` |
|   | `getHostUrl(): string` | `'http://john:xyz%2A12@nette.org:8080'` |
|   | `getAbsoluteUrl(): string` | entire URL |

The `getUser()`, `getPassword()`, `withUser()`, `withPassword()`, and `withoutUserInfo()` methods are deprecated, because embedding credentials directly in a URL is discouraged.

We can also work with individual query parameters using:

| Wither | Getter |
| --- | --- |
| `withQuery(string\|array $query)` | `getQueryParameters(): array` |
| `withQueryParameter(string $name, $val)` | `getQueryParameter(string $name)` |

### `getDomain(int $level = 2): string`

Returns the right or left part of the host. Here's how it works if the host is `www.nette.org`:

|  |  |
| --- | --- |
| `getDomain(1)` | `'org'` |
| `getDomain(2)` | `'nette.org'` |
| `getDomain(3)` | `'www.nette.org'` |
| `getDomain(0)` | `'www.nette.org'` |
| `getDomain(-1)` | `'www.nette'` |
| `getDomain(-2)` | `'www'` |
| `getDomain(-3)` | `''` |

### `resolve(string $reference): UrlImmutable` (since 3.3.2)

Resolves an absolute URL in the same way a browser processes links on an HTML page:

- if the link is an absolute URL (contains a scheme), it is used unchanged
- if the link begins with `//`, only the scheme from the current URL is adopted
- if the link begins with `/`, an absolute path from the domain root is created
- in other cases, the URL is constructed relative to the current path

```php
$url = new UrlImmutable('https://example.com/path/page');
echo $url->resolve('../foo');           // 'https://example.com/foo'
echo $url->resolve('/bar');             // 'https://example.com/bar'
echo $url->resolve('sub/page.html');    // 'https://example.com/path/sub/page.html'
```

### `isEqual(string|Url $url): bool`

Checks if two URLs are identical.

```php
$url->isEqual('https://nette.org');
```

## UrlScript

The [Nette\Http\UrlScript](https://api.nette.org/http/master/Nette/Http/UrlScript.html) class is a descendant of [UrlImmutable](#urlimmutable) and extends it with additional virtual URL components, such as the project's root directory, etc. Like its parent class, it is an immutable object.

The following diagram shows the components that UrlScript recognizes:

```
     baseUrl    basePath  relativePath  relativeUrl
        |          |        |               |
/---------------/-----\/--------\---------------------------\
http://nette.org/admin/script.php/pathinfo/?name=param#footer
                \_______________/\________/
                       |              |
                  scriptPath       pathInfo
```

- `baseUrl` is the base URL of the application, including the domain and the path part to the application's root directory
- `basePath` is the path part to the application's root directory
- `scriptPath` is the path to the current script
- `relativePath` is the script name (and possibly additional path segments) relative to `basePath`
- `relativeUrl` is the entire part of the URL after `baseUrl`, including the query string and fragment
- `pathInfo` is a now rarely used part of the URL after the script name

The following methods are available to retrieve these parts of the URL:

| Getter | Returned value |
| --- | --- |
| `getScriptPath(): string` | `'/admin/script.php'` |
| `getBasePath(): string` | `'/admin/'` |
| `getBaseUrl(): string` | `'http://nette.org/admin/'` |
| `getRelativePath(): string` | `'script.php/pathinfo/'` |
| `getRelativeUrl(): string` | `'script.php/pathinfo/?name=param#footer'` |
| `getPathInfo(): string` | `'/pathinfo/'` |

We usually do not create `UrlScript` objects directly; instead, the [Nette\Http\Request::getUrl()](request.md) method returns it with the components already correctly set for the current HTTP request.

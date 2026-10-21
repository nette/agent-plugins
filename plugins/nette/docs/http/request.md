# HTTP Request

Contents:

- [Nette\Http\Request](#nettehttprequest)
  - [`withUrl(Nette\Http\UrlScript $url): Nette\Http\Request`](#withurlnettehttpurlscript-url-nettehttprequest)
  - [`getUrl(): Nette\Http\UrlScript`](#geturl-nettehttpurlscript)
  - [`getQuery(?string $key=null): string|array|null`](#getquerystring-keynull-stringarraynull)
  - [`getPost(?string $key=null): string|array|null`](#getpoststring-keynull-stringarraynull)
  - [`getFile(string|string[] $key): ?Nette\Http\FileUpload`](#getfilestringstring-key-nettehttpfileupload)
  - [`getFiles(): array`](#getfiles-array)
  - [`getCookie(string $key): ?string`](#getcookiestring-key-string)
  - [`getCookies(): array`](#getcookies-array)
  - [`getMethod(): string`](#getmethod-string)
  - [`isMethod(string $method): bool`](#ismethodstring-method-bool)
  - [`getHeader(string $header): ?string`](#getheaderstring-header-string)
  - [`getHeaders(): array<string, string>`](#getheaders-arraystring-string)
  - [`isSecured(): bool`](#issecured-bool)
  - [`isSameSite(): bool` (deprecated)](#issamesite-bool-deprecated)
  - [`isFrom(FetchSite|array $site, FetchDest|array|null $dest=null, ?bool $user=null): bool` (since 3.4.0)](#isfromfetchsitearray-site-fetchdestarraynull-destnull-bool-usernull-bool-since-340)
  - [`isAjax(): bool`](#isajax-bool)
  - [`getRemoteAddress(): ?string`](#getremoteaddress-string)
  - [`getRemoteHost(): ?string` (deprecated)](#getremotehost-string-deprecated)
  - [`getBasicCredentials(): ?array`](#getbasiccredentials-array)
  - [`getRawBody(): ?string`](#getrawbody-string)
  - [`getOrigin(): ?UrlImmutable`](#getorigin-urlimmutable)
  - [`detectLanguage(array $langs): ?string`](#detectlanguagearray-langs-string)
- [RequestFactory](#requestfactory)
- [Uploaded Files](#uploaded-files)
  - [Overview of `FileUpload` Methods](#overview-of-fileupload-methods)
  - [`hasFile(): bool`](#hasfile-bool)
  - [`isOk(): bool`](#isok-bool)
  - [`getError(): int`](#geterror-int)
  - [`move(string $dest)`](#movestring-dest)
  - [`getContents(): ?string`](#getcontents-string)
  - [`getContentType(): ?string`](#getcontenttype-string)
  - [`getUntrustedName(): string`](#getuntrustedname-string)
  - [`getSanitizedName(): string`](#getsanitizedname-string)
  - [`getSuggestedExtension(): ?string` (since 3.2.4)](#getsuggestedextension-string-since-324)
  - [`getUntrustedFullPath(): string`](#getuntrustedfullpath-string)
  - [`getSize(): int`](#getsize-int)
  - [`getTemporaryFile(): string`](#gettemporaryfile-string)
  - [`__toString(): string`](#__tostring-string)
  - [`isImage(): bool`](#isimage-bool)
  - [`getImageSize(): ?array`](#getimagesize-array)
  - [`toImage(): Nette\Utils\Image`](#toimage-netteutilsimage)


Nette encapsulates the HTTP request into objects with a clear API while providing a sanitization filter.

The HTTP request is represented by the [Nette\Http\Request](https://api.nette.org/http/master/Nette/Http/Request.html) object. If you are working with Nette, this object is automatically created by the framework, and you can have it passed to you using [dependency injection](../dependency-injection/passing-dependencies.md). In presenters, simply call the `$this->getHttpRequest()` method. If you are working outside the Nette Framework, you can create the object using [RequestFactory](#requestfactory).

A major advantage of Nette is that when creating the object, it automatically sanitizes all input parameters (GET, POST, COOKIE) as well as the URL, removing control characters and invalid UTF-8 sequences. You can then safely work with this data. The sanitized data is subsequently used in presenters and forms.

→ [Installation and requirements](https://doc.nette.org/en/http#toc-installation)

## Nette\Http\Request

This object is immutable. It has no setters; it has only one so-called wither, `withUrl()`, which does not change the object but returns a new instance with the modified value.

### `withUrl(Nette\Http\UrlScript $url): Nette\Http\Request`

Returns a clone with a different URL.

### `getUrl(): Nette\Http\UrlScript`

Returns the URL of the request as a [UrlScript](urls.md#urlscript) object.

```php
$url = $httpRequest->getUrl();
echo $url; // https://nette.org/en/documentation?action=edit
echo $url->getHost(); // nette.org
```

Warning: Browsers do not send the fragment to the server, so `$url->getFragment()` will return an empty string.

### `getQuery(?string $key=null): string|array|null`

Returns GET request parameters.

```php
$all = $httpRequest->getQuery();    // array of all URL parameters
$id = $httpRequest->getQuery('id'); // returns GET parameter 'id' (or null)
```

### `getPost(?string $key=null): string|array|null`

Returns POST request parameters.

```php
$all = $httpRequest->getPost();     // array of all POST parameters
$id = $httpRequest->getPost('id');  // returns POST parameter 'id' (or null)
```

### `getFile(string|string[] $key): ?Nette\Http\FileUpload`

Returns an [upload](#uploaded-files) as a [Nette\Http\FileUpload](https://api.nette.org/http/master/Nette/Http/FileUpload.html) object:

```php
$file = $httpRequest->getFile('avatar');
if ($file?->hasFile()) { // was any file uploaded?
	$file->getUntrustedName(); // filename sent by the user
	$file->getSanitizedName(); // name without dangerous characters
}
```

To access a nested structure, provide an array of keys.

```php
// <input type="file" name="my-form[details][avatar]">
$file = $request->getFile(['my-form', 'details', 'avatar']);
```

Since you cannot trust external data and therefore cannot rely on the structure of the files, this approach is safer than, for example, `$request->getFiles()['my-form']['details']['avatar']`, which might fail.

### `getFiles(): array`

Returns a tree of [all uploads](#uploaded-files) in a normalized structure, where the leaves are [Nette\Http\FileUpload](https://api.nette.org/http/master/Nette/Http/FileUpload.html) objects:

```php
$files = $httpRequest->getFiles();
```

### `getCookie(string $key): ?string`

Returns a cookie or `null` if it doesn't exist.

```php
$sessId = $httpRequest->getCookie('sess_id');
```

### `getCookies(): array`

Returns all cookies.

```php
$cookies = $httpRequest->getCookies();
```

### `getMethod(): string`

Returns the HTTP method used for the request.

```php
$httpRequest->getMethod(); // GET, POST, HEAD, PUT
```

### `isMethod(string $method): bool`

Tests the HTTP method used for the request. The parameter is case-insensitive.

```php
if ($httpRequest->isMethod('GET')) // ...
```

### `getHeader(string $header): ?string`

Returns an HTTP header or `null` if it doesn't exist. The parameter is case-insensitive.

```php
$userAgent = $httpRequest->getHeader('User-Agent');
```

### `getHeaders(): array<string, string>`

Returns all HTTP headers as an associative array. The keys are normalized to lowercase.

```php
$headers = $httpRequest->getHeaders();
echo $headers['content-type'];
```

### `isSecured(): bool`

Is the connection encrypted (HTTPS)? Proper functionality might require [setting up a proxy](configuration.md#http-proxy).

### `isSameSite(): bool` (deprecated)

Did the request come from the same site? Since version 3.4 it is replaced by the more capable [isFrom()](#isfromfetchsitearray-site-fetchdestarraynull-destnull-bool-usernull-bool-since-340).

### `isFrom(FetchSite|array $site, FetchDest|array|null $dest=null, ?bool $user=null): bool` (since 3.4.0)

Tells you where the request came from and how the browser made it, based on the `Sec-Fetch-*` headers (so-called [Fetch Metadata](https://developer.mozilla.org/en-US/docs/Glossary/Fetch_metadata_request_header)) that the browser sets itself and a page running in the victim's browser can neither forge nor remove. Nette uses it internally to automatically protect forms and signals against [Cross-Site Request Forgery](../nette/glossary.md#cross-site-request-forgery-csrf) (CSRF). It is useful when you want to guard your own sensitive actions, such as API endpoints or destructive links.

The method returns `true` only when the request matches **all** the conditions you provide. The first parameter `$site` describes the relationship between the page that initiated the request and your site (the `Sec-Fetch-Site` header). It accepts a single value or a list of these `FetchSite` cases:

- `FetchSite::SameOrigin` – from the exact same origin (scheme, host, and port)
- `FetchSite::SameSite` – from the same site, possibly a different subdomain
- `FetchSite::CrossSite` – from a foreign site
- `FetchSite::None` – the user initiated it directly, e.g. by typing the URL or opening a bookmark

```php
// did the request originate from our own pages?
if (!$httpRequest->isFrom([FetchSite::SameOrigin, FetchSite::SameSite])) {
	// block the action
}
```

The optional `$dest` parameter (the `Sec-Fetch-Dest` header) says what kind of resource the browser is fetching, e.g. `FetchDest::Document` for a top-level navigation or `FetchDest::Empty` for a request made from JavaScript. The optional `$user` parameter (the `Sec-Fetch-User` header) indicates whether the navigation was triggered by a genuine user action such as clicking a link or submitting a form; pass `true` to require it.

A check that an action is reachable only from your own pages and only through a real user action then looks like this:

```php
if (!$httpRequest->isFrom(FetchSite::SameOrigin, FetchDest::Document, user: true)) {
	$this->error();
}
```

Older browsers (Safari before 16.4) do not send the `Sec-Fetch-*` headers. For them Nette falls back to a `SameSite=Strict` cookie that only proves the request is not cross-site. A check that additionally requires `$dest` or `$user` cannot be verified this way and returns `false` in those browsers – if that is too strict, test only `$site`.

### `isAjax(): bool`

Is it an AJAX request?

### `getRemoteAddress(): ?string`

Returns the user's IP address. Proper functionality might require [setting up a proxy](configuration.md#http-proxy).

### `getRemoteHost(): ?string` (deprecated)

Deprecated, it always returns `null`. Reverse DNS lookups were slow and unreliable; if you need the hostname, resolve it yourself from [getRemoteAddress()](#getremoteaddress-string).

### `getBasicCredentials(): ?array`

Returns authentication credentials for [Basic HTTP authentication](https://developer.mozilla.org/en-US/docs/Web/HTTP/Authentication).

```php
[$user, $password] = $httpRequest->getBasicCredentials();
```

### `getRawBody(): ?string`

Returns the body of the HTTP request.

```php
$body = $httpRequest->getRawBody();
```

### `getOrigin(): ?UrlImmutable`

Returns the origin from which the request came. An origin consists of the scheme (protocol), hostname, and port – for example, `https://example.com:8080`. Returns `null` if the origin header is not present or is set to `'null'`.

```php
$origin = $httpRequest->getOrigin();
echo $origin; // https://example.com:8080
echo $origin?->getHost(); // example.com
```

The browser sends the `Origin` header in the following cases:

- Cross-origin requests (AJAX calls to a different domain)
- POST, PUT, DELETE, and other modifying requests
- Requests made using the Fetch API

The browser does NOT send the `Origin` header for:

- Regular GET requests to the same domain (same-origin navigation)
- Direct navigation by typing a URL into the address bar
- Requests from non-browser clients

Unlike the `Referer` header, `Origin` contains only the scheme, host, and port – not the full URL path. This makes it more suitable for security checks while preserving user privacy. The `Origin` header is primarily used for [CORS](../nette/glossary.md#cross-origin-resource-sharing-cors) (Cross-Origin Resource Sharing) validation.

### `detectLanguage(array $langs): ?string`

Detects the language. Pass an array of languages supported by the application as the `$langs` parameter, and it will return the one preferred by the visitor's browser. It's not magic; it just uses the `Accept-Language` header. If no match is found, it returns `null`.

```php
// Browser sends e.g., Accept-Language: cs,en-us;q=0.8,en;q=0.5,sl;q=0.3

$langs = ['hu', 'pl', 'en']; // languages supported by the application
echo $httpRequest->detectLanguage($langs); // en
```

## RequestFactory

The [Nette\Http\RequestFactory](https://api.nette.org/http/master/Nette/Http/RequestFactory.html) class is used to create an instance of `Nette\Http\Request`, which represents the current HTTP request. (If you are working with Nette, the HTTP request object is automatically created by the framework.)

```php
$factory = new Nette\Http\RequestFactory;
$httpRequest = $factory->fromGlobals();
```

The `fromGlobals()` method creates the request object based on the current PHP global variables (`$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`, and `$_SERVER`). When creating the object, it automatically cleanses all input parameters (GET, POST, COOKIE) as well as the URL of control characters and invalid UTF-8 sequences, ensuring security when working with this data later.

RequestFactory can be configured before calling `fromGlobals()`:

- using the `$factory->setBinary()` method disables the automatic removal of control characters and invalid UTF-8 sequences from input parameters.
- using the `$factory->setProxy(...)` method specifies the IP address of the [proxy server](configuration.md#http-proxy), which is necessary for correct detection of the user's IP address. Its optional parameters `$forwarded` and `$xForwarded` choose which headers from the proxy to trust.
- using the `$factory->setForceHttps()` .{data-version:3.3.4} method forces the request scheme to HTTPS regardless of the server environment.
- using the `$factory->setBaseUrl()` .{data-version:3.4.1} method sets the [base URL of the application](configuration.md#application-base-url-since-341), which is used when the address cannot be detected from the environment, typically on the command line.

RequestFactory allows defining filters that automatically transform parts of the URL request. These filters remove unwanted characters from URLs that might have been inserted, for example, by incorrect implementations of comment systems on various websites:

```php
// remove spaces from the path
$requestFactory->urlFilters['path']['%20'] = '';

// remove dot, comma, or right parenthesis from the end of the URI
$requestFactory->urlFilters['url']['[.,)]$'] = '';

// clean the path from double slashes (default filter)
$requestFactory->urlFilters['path']['/{2,}'] = '/';
```

The first key, `'path'` or `'url'`, determines which part of the URL the filter will be applied to. The second key is the regular expression to search for, and the value is the replacement to be used instead of the found text.

## Uploaded Files

The `Nette\Http\Request::getFiles()` method returns an array of all uploads in a normalized structure, where the leaves are [Nette\Http\FileUpload](https://api.nette.org/http/master/Nette/Http/FileUpload.html) objects. These encapsulate the data submitted by the `<input type=file>` form element.

The structure reflects the naming of elements in HTML. In the simplest case, it might be a single named form element submitted as:

```latte
<input type="file" name="avatar">
```

In this case, `$request->getFiles()` returns an array:

```php
[
	'avatar' => /* FileUpload instance */
]
```

The `FileUpload` object is created even if the user did not upload any file or the upload failed. The `hasFile()` method returns true if a file was sent:

```php
$request->getFile('avatar')?->hasFile();
```

In the case of an element name using array notation:

```latte
<input type="file" name="my-form[details][avatar]">
```

the returned tree looks like this:

```php
[
	'my-form' => [
		'details' => [
			'avatar' => /* FileUpload instance */
		],
	],
]
```

You can also create arrays of files:

```latte
<input type="file" name="my-form[details][avatars][]" multiple>
```

In such a case, the structure looks like this:

```php
[
	'my-form' => [
		'details' => [
			'avatars' => [
				0 => /* FileUpload instance */,
				1 => /* FileUpload instance */,
				2 => /* FileUpload instance */,
			],
		],
	],
]
```

The best way to access index 1 of the nested array is as follows:

```php
$file = $request->getFile(['my-form', 'details', 'avatars', 1]);
if ($file instanceof Nette\Http\FileUpload) {
	// ...
}
```

Since you cannot trust external data and therefore cannot rely on the structure of the files, this approach is safer than, for example, `$request->getFiles()['my-form']['details']['avatars'][1]`, which might fail.

### Overview of `FileUpload` Methods

### `hasFile(): bool`

Returns `true` if the user uploaded a file.

### `isOk(): bool`

Returns `true` if the file was uploaded successfully.

### `getError(): int`

Returns the error code associated with the uploaded file. It is one of the [UPLOAD_ERR_XXX](https://php.net/manual/en/features.file-upload.errors.php) constants. If the file was uploaded successfully, it returns `UPLOAD_ERR_OK`.

### `move(string $dest)`

Moves an uploaded file to a new location. If the destination file already exists, it will be overwritten.

```php
$file->move('/path/to/files/name.ext');
```

### `getContents(): ?string`

Returns the contents of the uploaded file. If the upload was not successful, it returns `null`.

### `getContentType(): ?string`

Detects the MIME content type of the uploaded file based on its signature. If the upload was not successful or the detection failed, it returns `null`.

> [!CAUTION]
> Requires the PHP extension `fileinfo`.

### `getUntrustedName(): string`

Returns the original file name as submitted by the browser.

> [!CAUTION]
> Do not trust the value returned by this method. A client could send a malicious filename with the intention to corrupt or hack your application.

### `getSanitizedName(): string`

Returns the sanitized file name. It contains only ASCII characters `[a-zA-Z0-9.-]`. If the name does not contain such characters, it returns `'unknown'`. If the file is a JPEG, PNG, GIF, WebP, or AVIF image, it also returns the correct file extension.

> [!CAUTION]
> Requires the PHP extension `fileinfo`.

### `getSuggestedExtension(): ?string` (since 3.2.4)

Returns the appropriate file extension (without the dot) corresponding to the detected MIME type.

> [!CAUTION]
> Requires the PHP extension `fileinfo`.

### `getUntrustedFullPath(): string`

Returns the original file path as submitted by the browser during directory upload. The full path is only available in PHP 8.1 and later. In previous versions, this method returns the original file name.

> [!CAUTION]
> Do not trust the value returned by this method. A client could send a malicious filename with the intention to corrupt or hack your application.

### `getSize(): int`

Returns the size of the uploaded file. If the upload was not successful, it returns `0`.

### `getTemporaryFile(): string`

Returns the path to the temporary location of the uploaded file. If the upload was not successful, it returns `''`.

### `__toString(): string`

Returns the path to the temporary location of the uploaded file. This allows the `FileUpload` object to be used directly as a string.

### `isImage(): bool`

Returns `true` if the uploaded file is a JPEG, PNG, GIF, WebP, or AVIF image. Detection is based on its signature and does not verify the integrity of the entire file. Whether an image is corrupted can be determined, for example, by trying to [load it](#toimage-netteutilsimage).

> [!CAUTION]
> Requires the PHP extension `fileinfo`.

### `getImageSize(): ?array`

Returns a pair `[width, height]` with the dimensions of the uploaded image. If the upload was not successful or it is not a valid image, it returns `null`.

### `toImage(): Nette\Utils\Image`

Loads the image as an [Image](../utils/images.md) object. If the upload was not successful or it is not a valid image, it throws a `Nette\Utils\ImageException`.

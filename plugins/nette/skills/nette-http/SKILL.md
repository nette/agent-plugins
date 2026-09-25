---
name: nette-http
description: Invoke before working with the HTTP layer in Nette – the request, the response, sessions or cookies. Covers Nette\Http\Request (getUrl/UrlScript, getPost, getQuery, getCookie, getFile/getFiles, getHeader, getOrigin, isFrom, isSecured, isAjax, getRemoteAddress and the proxy trap), Nette\Http\Response (setCookie and SameSite, setHeader vs addHeader, redirect, setExpiration, headers-before-output), sessions (Session, SessionSection, set/get/remove, per-key expiration, the `session:` NEON section, autoStart), the Sec-Fetch based CSRF / same-origin protection and how to allow a legitimate cross-origin POST, and the SSRF guards UrlValidator and IPAddress. Also trigger for file uploads (FileUpload), Url vs UrlImmutable vs UrlScript, getBasePath/getRelativeUrl, cookie flags, and the `http:` config section. Not for authentication or the `security:` section (see nette-security) nor for building forms (see nette-forms).
---

## Nette Http

Installed with `composer require nette/http`. The framework registers `http.request` (`Nette\Http\Request`), `http.response` (`Nette\Http\Response`), `http.requestFactory` and `session.session` (`Nette\Http\Session`). All autowired – inject `Nette\Http\IRequest` / `Nette\Http\IResponse` / `Nette\Http\Session` by type; in presenters use `$this->getHttpRequest()`, `$this->getHttpResponse()`, `$this->getSession()` / `$this->getSession('sectionName')`.

### Request – read-only, already sanitized

`Nette\Http\Request` is immutable; `RequestFactory::fromGlobals()` strips control characters and invalid UTF-8 from GET/POST/COOKIE and the URL.

```php
$url = $httpRequest->getUrl();          // UrlScript, not a string
$httpRequest->getQuery();               // all GET params; getQuery('id') for one (null if missing)
$httpRequest->getPost('name');          // POST param or null
$httpRequest->getCookie('lang');        // ?string; getCookies() for all
$httpRequest->getHeader('User-Agent');  // ?string, case-insensitive; getHeaders() keys are lowercase
$httpRequest->isSecured();              // HTTPS?
$httpRequest->isAjax();                 // X-Requested-With: XMLHttpRequest
$httpRequest->getRemoteAddress();       // ?string
$httpRequest->getBasicCredentials();    // ?array – since 3.2 NOT in $url->getUser()/getPassword()
```

**`getRemoteAddress()` and `isSecured()` are wrong behind a reverse proxy** until you list the proxy, otherwise you get the load balancer's IP and `http` for a TLS-terminated request:

```neon
http:
	proxy: 127.0.0.1        # IP, CIDR range, or a list of them
	forceHttps: true        # 3.3.4+; proxy does not send X-Forwarded-Proto at all
```

`getReferer()` and `getRemoteHost()` are deprecated (`getRemoteHost()` always returns `null`). Use `getOrigin(): ?UrlImmutable` – scheme+host+port from the `Origin` header, absent for plain navigation and same-origin GET.

**File uploads** – always index with `getFile()`, never walk the `getFiles()` tree, because the client controls its shape:

```php
$file = $httpRequest->getFile(['my-form', 'details', 'avatar']); // ?FileUpload
if ($file?->hasFile()) {
	$file->getUntrustedName();   // as sent by the user – never use as a path
	$file->getSanitizedName();   // safe filename
	$file->move($dest);
}
```

`getFile()` returns **null** when the field was submitted empty (`UPLOAD_ERR_NO_FILE`), so always
use `?->`. It is the **form** control that hands you a dummy object instead: `UploadControl`
returns a `FileUpload` carrying that error unless `setNullable()` is on — that is where
`hasFile()` (did the user pick a file?) and `isOk()` (did it arrive intact?) differ.

### Response – mutable, but only before output

Every setter throws `Nette\InvalidStateException` once headers are out; `isSent()` tells you. `setHeader()` replaces (and `null` deletes), `addHeader()` appends another line of the same name.

```php
$httpResponse->setCode(IResponse::S404_NotFound);
$httpResponse->setExpiration('1 hour');   // Cache-Control + Expires; null = no cache at all
$httpResponse->sendAsFile('invoice.pdf');
$httpResponse->redirect($url);            // does NOT terminate the script
```

In a presenter use `$this->redirect()` / `$this->getHttpResponse()->redirect(); $this->terminate();` – `Response::redirect()` only sets `Location` and echoes a fallback link.

```php
$httpResponse->setCookie('lang', 'en', '100 days');  // expiration: interval string, date, or null = session cookie
$httpResponse->setCookie('t', 'dark', '1 year', sameSite: SameSite::None, partitioned: true);
$httpResponse->deleteCookie('lang');
```

`SameSite` is now the enum `Nette\Http\SameSite` (`Lax`/`Strict`/`None`); the `IResponse::SameSiteLax` constants are deprecated. Defaults: `sameSite` = `Lax`, `httpOnly` = `true`, `secure` from `http: cookieSecure` (`auto`). `SameSite=None` and `partitioned` force `Secure` on regardless of what you pass. Passing an absolute UNIX timestamp as `$expire` is deprecated – a number means *relative seconds*; a session cookie is `null`, not `0`.

### Sessions

Never call `session_start()`, `$_SESSION` or `session_regenerate_id()` yourself – Nette configures the hardening ini directives (`use_only_cookies`, `use_strict_mode`, `cookie_httponly`, no trans-sid), namespaces data under `$_SESSION['__NF']`, and expires per-section/per-variable metadata at start. A hand-started session bypasses all of it.

The session starts lazily on the first read or write (`autoStart: smart`). Start it explicitly with `$session->start()` if you know you need it and output may already be flowing.

```php
$section = $session->getSection('cart');   // SessionSection; $this->getSession('cart') in a presenter
$section->set('items', $items);
$section->set('flash', $msg, '30 seconds');  // 3rd arg = per-variable expiration
$section->get('items');                      // null when missing
$section->remove('items');                   // no argument = drop the whole section
$section->setExpiration('20 minutes');       // whole section; ($expire, $variables) for selected keys
$section->removeExpiration('flash');
```

- `set($name, null)` is a **remove**, not a write of `null`.
- `get()` takes **exactly one argument** – `get('key', $default)` throws `ArgumentCountError`. Use `$section->get('key') ?? $default`.
- Magic property access (`$section->items`), `isset()`, `unset()` and `ArrayAccess` are all deprecated. The magic read in particular goes through `&__get()`, so it starts the session **for writing** and materialises the key even on a pure read; `ArrayAccess` reads and `isset()` do not. That is why `set()`/`get()` exist.
- A section's expiration must not exceed the session's own (`session: expiration:`), otherwise you get a warning and the data dies with the session anyway.

```neon
session:
	expiration: 14 days      # inactivity timeout, default '3 hours'
	autoStart: smart         # smart (default) | always | never
	cookieSamesite: Lax      # Lax (default) | Strict | None
	name: MYID               # any PHP session directive in camelCase, e.g. savePath
	handler: @myHandler
	debugger: true           # session panel in Tracy Bar
```

Login/logout session-id regeneration is handled by nette/security – see **nette-security**.

### CSRF: same-origin via Sec-Fetch, not tokens

**Since nette/http 3.4 the protection is based on the `Sec-Fetch-*` request headers** (Fetch Metadata), which the browser sets itself and page JavaScript can neither forge nor strip. It replaced the older `SameSite=Strict` cookie check (`Request::isSameSite()`, now deprecated).

```php
Request::isFrom(FetchSite|array $site, FetchDest|array|null $dest = null, ?bool $user = null): bool
```

`FetchSite` cases: `SameOrigin`, `SameSite`, `CrossSite`, `None` (direct navigation – typed URL, bookmark, link in an e-mail). `FetchDest` describes the resource kind (`Document`, `Empty`, `Image`, …); `$user = true` requires a genuine user gesture. All given conditions must match.

```php
if (!$httpRequest->isFrom(FetchSite::SameOrigin, FetchDest::Document, user: true)) {
	$this->error();
}
```

What this means in practice:

- **POST forms and presenter signals are protected automatically** – `Nette\Forms\Form::receiveHttpData()` and `UI\Form::signalReceived()` require `FetchSite::SameOrigin`, and every `handle*()` method implicitly gets `#[Requires(sameOrigin: true)]`.
- **`$form->addProtection()` is deprecated** (nette/forms 3.3) – the built-in protection is sufficient, and unlike a token it needs no session. Do not add it to new forms.
- To allow a legitimate cross-origin POST: `$form->allowCrossOrigin()` (this replaced `disableSameSiteProtection()`).
- **Behavior change in 3.4:** direct navigation is no longer treated as same-site. A signal reached from a link in an e-mail (`Sec-Fetch-Site: none`) now fails. Mark it explicitly:

  ```php
  #[Requires(sameOrigin: false)]
  public function handleUnsubscribe(string $token): void
  ```

- Safari < 16.4 sends no `Sec-Fetch-*`; Nette falls back to the `SameSite=Strict` cookie `_nss`, sent **only** to such browsers since 3.4. It can only prove "not cross-site", so a check that also demands `$dest` or `$user` returns `false` there. `http: disableNetteCookie: true` switches the cookie off — and with it the only fallback, which breaks every POST from those browsers.
- **Any non-browser client fails the check**, not just old Safari: curl, a mobile app, a webhook or server-to-server traffic sends no `Sec-Fetch-*` and no `_nss`, so `isFrom(SameOrigin)` is false. The failure is quiet — the form reports "not submitted" rather than an error. API endpoints therefore need `#[Requires(sameOrigin: false)]` (or `allowCrossOrigin()` on a form) plus authentication of their own, typically a token.

Form-side details live in **nette-forms**; authentication in **nette-security**.

### SSRF: UrlValidator and IPAddress

New in nette/http 3.4. Validate **every** user-supplied URL before the server fetches it – otherwise an attacker reaches your loopback, private network or cloud metadata at `169.254.169.254`.

```php
use Nette\Http\UrlValidator;

if (!(new UrlValidator)->allows($userUrl)) {
	return; // unsafe
}
```

The default policy is deliberately strict: `https` only, port 443 only, and every DNS-resolved A/AAAA address must be publicly routable. Loosen it through constructor named arguments – `schemes`, `ports` (`null` = any), `allowPrivateIps`, `allowLoopback`, `allowLinkLocal`, `allowReserved`, `allowUserinfo`, `hostAllowlist`, `hostBlocklist`. Multicast is rejected unconditionally. Host patterns are exact or `*.example.com` (any subdomain depth, **not** the apex – list both).

- `allowsWithoutDns()` – same checks minus DNS resolution and IP ranges; a cheap pre-filter only.
- `getResolvedIPs()` – returns the validated IPs (`[]` on failure). Pin the fetch to them (`CURLOPT_RESOLVE`) to close the DNS-rebinding race between validation and download.

`Nette\Http\IPAddress` is the value object behind it: `new IPAddress($s)` (throws), `IPAddress::tryFrom()`, `isValid()`, predicates `isPublic()`, `isPrivate()`, `isLoopback()`, `isLinkLocal()`, `isMulticast()`, `isReserved()`, plus `isInRange('10.0.0.0/8')`. IPv4-mapped IPv6 (`::ffff:127.0.0.1`) is normalized, so `isLoopback()` sees through that classic filter bypass.

### Url, UrlImmutable, UrlScript

`Url` is mutable (setters), `UrlImmutable` returns new instances from `with*()` withers, and `UrlScript extends UrlImmutable` adding the notion of where the application root sits. `Request::getUrl()` returns a `UrlScript` with those parts already filled in – don't construct one by hand.

For `http://nette.org/admin/script.php/pathinfo/?name=param#footer` with script path `/admin/script.php`:

| Method | Value |
|---|---|
| `getScriptPath()` | `/admin/script.php` |
| `getBasePath()` | `/admin/` |
| `getBaseUrl()` | `http://nette.org/admin/` |
| `getRelativeUrl()` | `script.php/pathinfo/?name=param#footer` |
| `getPathInfo()` | `/pathinfo/` |

When generating links or asset paths by hand, prefix with `getBasePath()` – a bare `/css/style.css` breaks as soon as the app lives in a subdirectory. `getBasePath()`, `getBaseUrl()` and `getRelativeUrl()` on plain `Url` are deprecated (3.1); they belong to `UrlScript`. `$url->getFragment()` on a request URL is always empty – browsers never send the fragment.

### Online Documentation

For details, see the official documentation:

- [HTTP Request](https://doc.nette.org/en/http/request) – Request, RequestFactory, isFrom, uploads, FileUpload
- [HTTP Response](https://doc.nette.org/en/http/response) – Response, headers, cookies
- [Sessions](https://doc.nette.org/en/http/sessions) – Session, SessionSection, expiration
- [SSRF Protection](https://doc.nette.org/en/http/ssrf) – UrlValidator, IPAddress
- [URL Parsing](https://doc.nette.org/en/http/urls) – Url, UrlImmutable, UrlScript
- [HTTP Configuration](https://doc.nette.org/en/http/configuration) – the `http:` and `session:` sections

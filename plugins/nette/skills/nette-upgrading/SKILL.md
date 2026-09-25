---
name: nette-upgrading
description: Invoke whenever Nette code uses a name you are not sure still exists, and before writing any class from Nette 2.x/3.0-era memory. A lookup table mapping every retired name to its current one - `I`-prefixed interfaces (IAuthenticator, IRouter, IStorage, ITranslator, IMailer, ITemplate), UPPER_CASE constants renamed to PascalCase (Cache::EXPIRE, Form::FILLED, Debugger::PRODUCTION, Image::SHRINK_ONLY, Presenter::INVALID_LINK_*), Nette\Database\Context vs Explorer, Nette\Security\Identity vs SimpleIdentity, Nette\Configurator vs Nette\Bootstrap\Configurator, FileUpload::getName() vs getUntrustedName(), static Passwords vs the service, and flags replaced by named arguments. Also trigger for "upgrade", "migration", "BC break", "deprecated", "Nette 2 to 3", "which version renamed this", `E_USER_DEPRECATED` notices, and "why does this method/class not exist".
---

## Nette: Retired Names and Silent Traps

Most of these old names **still work** – as a `class_alias`, a deprecated stub or a magic
property – and at **runtime** most of them are completely silent. A real `E_USER_DEPRECATED`
is the exception (`Strings::TRIM_CHARACTERS`, the `@persistent` / `@crossOrigin` annotations,
NEON `...`, `getParameter($name, $default)`, `@property-deprecated`), so never rely on a
notice appearing.

They are not invisible to *tooling*, though. The aliases live in each package's
`src/compatibility.php` / `compatibility-intf.php` in this shape:

```php
if (false) {
	/** @deprecated use Nette\Database\Explorer */
	class Context extends Explorer
	{
	}
} elseif (!class_exists(Context::class)) {
	class_alias(Explorer::class, Context::class);
}
```

The `if (false)` branch never executes, but the IDE, PHPStan and Composer's classmap all read
it – which is exactly why the editor strikes the old name through while PHP says nothing.
So static analysis will flag these; a passing test run will not. Prefer the current name always.
Area-specific tables live in **nette-security** (Authenticator, Authorizator, User constants)
and **nette-components** (component tree, `redrawControl`); this skill does not repeat them.

### Interfaces: the `I` prefix was dropped

| Retired | Current | Note |
|---|---|---|
| `Nette\Application\IResponse` | `Nette\Application\Response` | alias, silent |
| `UI\ITemplate`, `ITemplateFactory`, `IRenderable`, `ISignalReceiver`, `IStatePersistent` | `UI\Template`, `TemplateFactory`, `Renderable`, `SignalReceiver`, `StatePersistent` | alias, silent |
| `Nette\Bridges\ApplicationLatte\ILatteFactory` | `…\LatteFactory` | alias, silent |
| `Nette\Application\IRouter` | `Nette\Routing\Router` | alias, silent up to application 3.2; **removed in 3.3** – hard error |
| `Nette\Caching\IStorage`, `Nette\Mail\IMailer` | `Caching\Storage`, `Mail\Mailer` | alias, silent |
| `Nette\Localization\ITranslator`, `Nette\Utils\IHtmlString` | `Localization\Translator`, `Nette\HtmlStringable` | alias, silent |
| `Nette\Security\IUserStorage` | `Nette\Security\UserStorage` | **removed in security 3.2** – hard error, and a different contract |
| `Nette\Database\IRow`, `IRowContainer` | – | deprecated as unnecessary |

`Nette\Application\IPresenter` and `IPresenterFactory` **keep the `I`** – do not "modernize" them.

### Renamed classes

| Retired | Current | Since |
|---|---|---|
| `Nette\Configurator` | `Nette\Bootstrap\Configurator` | bootstrap 3.1 – alias, **silent** |
| `Nette\Database\Context` | `Nette\Database\Explorer` | database 3.1 – alias, **silent** (`src/compatibility.php`) |
| `Nette\Security\Identity` | `Nette\Security\SimpleIdentity` | security 3.1 – deprecated stub, **silent**. Note `SimpleIdentity extends Identity`, so an `Identity` is *not* an instance of `SimpleIdentity` and a `SimpleIdentity` type hint rejects it |
| `Nette\Http\UserStorage` | (use `Nette\Security\UserStorage`) | removed in http 3.4 |
| `Nette\PhpGenerator\PhpLiteral` | `Nette\PhpGenerator\Literal` | deprecated stub |
| `Nette\DI\Statement` | `Nette\DI\Definitions\Statement` | DI 3.0 – alias, **silent** |
| `Nette\Object` | `Nette\SmartObject` trait | removed in 3.0 (`Nette\LegacyObject` in nette/deprecated) |
| `Nette\Utils\Finder` from `nette/finder` | ships in `nette/utils` 4.0 | `composer remove nette/finder` |

### Constants: UPPER_CASE became PascalCase

Every one below is a **deprecated alias that still evaluates**, so the old code runs unchanged.

| Retired | Current |
|---|---|
| `Cache::EXPIRE`, `EXPIRATION` | `Cache::Expire` (also `Priority`, `Sliding`, `Tags`, `Files`, `Items`, `Callbacks`, `Namespaces`, `All`; `CONSTS` -> `Constants`) |
| `Form::FILLED`, `EMAIL`, `MIN_LENGTH`, `IS_IN`, … | `Form::Filled`, `Email`, `MinLength`, `IsIn`, … |
| `Form::PATTERN_ICASE` | `Form::PatternInsensitive` |
| `Presenter::INVALID_LINK_WARNING`, `PRESENTER_KEY`, `SIGNAL_KEY`, `ACTION_KEY`, `FLASH_KEY`, `DEFAULT_ACTION` | `InvalidLinkWarning`, `PresenterKey`, `SignalKey`, `ActionKey`, `FlashKey`, `DefaultAction` |
| `IRequest::GET`, `POST` | `IRequest::Get`, `Post` |
| `IResponse::S404_NOT_FOUND`, `REASON_PHRASES` | `IResponse::S404_NotFound`, `ReasonPhrases` |
| `IResponse::SAME_SITE_LAX` / `SameSiteLax` | `Nette\Http\SameSite::Lax` (enum, http 3.4) |
| `Debugger::DEVELOPMENT`, `PRODUCTION`, `DETECT`, `COOKIE_SECRET` | `Debugger::Development`, `Production`, `Detect`, `CookieSecret` |
| `Image::SHRINK_ONLY`, `STRETCH`, `EMPTY_GIF` | `Image::ShrinkOnly`, `Stretch`, `EmptyGIF` |
| `Image::FIT`, `FILL`, `EXACT` | `Image::OrSmaller`, `OrBigger`, `Cover` – **renamed, not just recased** |
| `IComponent::NAME_SEPARATOR` | `IComponent::NameSeparator` (3.0.3; removed in 4.0) |
| `Strings::TRIM_CHARACTERS` | `Strings::TrimCharacters` |

### Renamed methods

| Retired | Current |
|---|---|
| `Cache::start()` | `Cache::capture()` (caching 3.1) |
| `FileUpload::getName()` | `getUntrustedName()` (http 3.0.5) – also `getSanitizedName()`, `getUntrustedFullPath()` |
| `Request::isSameSite()` | `isFrom(FetchSite::SameOrigin)` (http 3.4) |
| `Container::getUnsafeValues()`, `addImage()` | `getUntrustedValues()`, `addImageButton()` |
| `Checkbox::getSeparatorPrototype()` | `getContainerPrototype()` |
| `BaseControl::setAttribute()`, `TextInput::setType()` | `setHtmlAttribute()`, `setHtmlType()` |
| `SelectBox/MultiSelectBox::addOptionAttributes()` | `setOptionAttribute()` |
| `Arrays::searchKey()` | `Arrays::getKeyOffset()` |
| `Strings::startsWith/endsWith/contains()` | native `str_starts_with/str_ends_with/str_contains()` |
| `Strings::normalizeNewLines()`, `checkEncoding()` | `Strings::unixNewLines()`, `Validators::isUnicode()` |
| `Validators::isList()` | `Arrays::isList()` |
| `Callback::closure()` | `Closure::fromCallable(…)` / first-class `$obj->m(...)` – **removed** |
| `Reflection::getParameterType/getPropertyType/getReturnType()` | `Nette\Utils\Type` – **removed in utils 4.0** |
| `PhpGenerator::setDocuments/addDocument()` | `setComment()` / `addComment()` |
| `Latte\Engine::setTempDirectory()` | `setCacheDirectory()`; `setStrictTypes()`/`setStrictParsing()` -> `setFeature(Feature::…)` |

### Signature changes – do not pass the old arguments

- **`getComponents()` takes no arguments.** `$deep`/`$filterType` left the signature in
  component-model **3.1** and throw `Nette\DeprecatedException` in 4.0. Use `getComponentTree()`
  (also since 3.1), optionally `array_filter()`-ed. See **nette-components**.
- **`Passwords::hash()/verify()/needsRehash()` are instance methods** since security 3.0 – inject
  `Nette\Security\Passwords`, never call them statically.
- **Flags replaced by named arguments** (utils 4.0; the constants still work but are deprecated):
  `Json::PRETTY` -> `pretty:`, `Json::ESCAPE_UNICODE` -> `asciiSafe:`, `Json::FORCE_ARRAY` -> `forceArrays:`,
  `PREG_GREP_INVERT` in `Arrays::grep()` -> `invert:`, `PREG_*` in
  `Strings::split/match/matchAll()` -> named arguments. `Image::ShrinkOnly` is **not** among
  them – it stays a bit flag in the `$mode` argument of `resize()`.
- **`Nette\ComponentModel\Component` has no constructor** since 3.0 – never call
  `parent::__construct()` in a presenter or control.
- `Response::setCookie()`: an absolute UNIX timestamp is deprecated; a session cookie is `null`, not `0`.
- `$component->getParameter($name, $default)`: the `$default` argument is deprecated, use `?? `.
- `Form::getValues()` returns **only validated controls** since forms 3.1 (same for the `$values`
  handed to `onSuccess`); `getUntrustedValues()` returns everything.

### Silent traps worth checking explicitly

- **`IAuthenticator` still exists but is an empty deprecated interface** – its
  `authenticate(array $credentials)` method is commented out. Implementing it compiles and is never
  called as you expect. Implement `Nette\Security\Authenticator` with
  `authenticate(string $username, string $password)` – the first parameter is `$username`,
  which matters if you call it with named arguments.
- **NEON service keys:** the current key is `create:`. `factory:` and `class:` are silently remapped
  (`class:` -> `type:` or `create:`), so old configs never complain. `dynamic:` became `imported:`
  and an omitted argument is `_`, not `...`.
- **Annotations still parse but emit `E_USER_DEPRECATED`:** `@persistent` -> `#[Persistent]`,
  `@crossOrigin` -> `#[Requires(sameOrigin: false)]`, `@deprecated` -> `#[Attributes\Deprecated]`.
- **Session sections:** `$section->foo = 1` / `$section->foo` are deprecated magic properties – use
  `set()`, `get()`, `remove()`, which correctly distinguish read from write and do not start the
  session needlessly.
- `$cache[$key]` is a **fatal error** – `Cache` dropped `ArrayAccess` back in caching 2.5.
  Use `load()` / `save()`.
- `SmartObject` emulated properties need a `@property` annotation and are **silent** (only
  `@property-deprecated` emits a notice); there are no extension methods and no
  `getReflection()`. The `$obj->onFoo()` event sugar does still work.

### Still-reproduced Nette 2.x behaviour

- `Nette\Routing\Router::match()` returns an **array** of parameters and `constructUrl()` accepts one (3.0), not a
  `Nette\Application\Request`.
- `fetch()` / `fetchField()` return `null`, not `false`, when there is no row (database 3.0).
- Form controls are **optional by default** – drop `setRequired(false)`; a control with `addRule()`
  must be explicitly `setRequired()`.
- Presenter and route names are **case-sensitive**; so is RobotLoader.
- Overriding a framework method requires the **same type hints** as the parent (3.0), or PHP raises
  "Declaration must be compatible".
- Signals and form submissions are same-origin-checked; allow others with
  `#[Requires(sameOrigin: false)]` or `$form->allowCrossOrigin()`.

### How to check which rules apply

Each package versions independently, so read `composer.json` / `composer show nette/*` for the actual
constraint before deciding a name is current – `nette/utils` 4.x and `nette/security` 3.x coexist
normally. Every package has its own upgrade page, and you must upgrade one minor at a time
(3.0 -> 3.1 -> 3.2, never 3.0 -> 3.2). Temporarily silencing `E_USER_DEPRECATED` hides exactly the
warnings this skill is about.

### Online Documentation

For details, see the official documentation:

- [Upgrade Guide](https://doc.nette.org/en/migrations) – index of all package upgrade pages
- [Application](https://doc.nette.org/en/application/upgrading) – routers, signals, type hints
- [Forms](https://doc.nette.org/en/forms/upgrading) – getValues, CSRF, renamed controls
- [HTTP](https://doc.nette.org/en/http/upgrading) – FileUpload, cookies, sessions, Sec-Fetch
- [Utils](https://doc.nette.org/en/utils/upgrading) – named arguments, Finder, Reflection
- [Security](https://doc.nette.org/en/security/upgrading) – Authenticator, SimpleIdentity, UserStorage
- [Database](https://doc.nette.org/en/database/upgrading) – Context to Explorer
- [Dependency Injection](https://doc.nette.org/en/dependency-injection/upgrading) – config keys
- [Component Model](https://doc.nette.org/en/component-model/upgrading) – getComponents, monitor
- Also [Caching](https://doc.nette.org/en/caching/upgrading), [Bootstrap](https://doc.nette.org/en/bootstrap/upgrading), [NEON](https://doc.nette.org/en/neon/upgrading), [Tracy](https://doc.nette.org/en/tracy/upgrading), [Mail](https://doc.nette.org/en/mail/upgrading), [PhpGenerator](https://doc.nette.org/en/php-generator/upgrading), [RobotLoader](https://doc.nette.org/en/robot-loader/upgrading), [SafeStream](https://doc.nette.org/en/safe-stream/upgrading)

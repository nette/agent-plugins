---
name: nette-security
description: Invoke before implementing or modifying authentication, login/logout or permission checks in Nette. Covers the Authenticator interface, SimpleIdentity, Nette\Security\User, the Passwords hashing service, Authorizator, Permission ACL (roles, resources, privileges, assertions), IdentityHandler, user storage (session/cookie), login expiration, and the `security:` NEON section (users, roles, resources, rules). Also trigger for sign-in presenters and forms, isAllowed()/isInRole() checks, password hashing or rehashing, AuthenticationException error codes, and stale roles in the session. Not for Nette Forms validation rules nor for HTTP session configuration itself.
---

## Nette Security

Installed with `composer require nette/security`. The framework registers `security.user` (`Nette\Security\User`), `security.passwords`, `security.userStorage`, plus `security.authenticator` / `security.authorizator` when the `security:` config section defines them. All are autowired – inject `Nette\Security\User` or `Nette\Security\Passwords` by type; in presenters use `$this->getUser()`.

### Current names vs. Nette 2.x names

Using an old name still "works" (aliases/deprecated stubs exist), which makes the mistake silent. Always write the current one:

| Current (all in `Nette\Security\`) | Deprecated 2.x name |
|---|---|
| `SimpleIdentity` | `Identity` |
| `Authenticator` | `IAuthenticator` – a **different, array-based** `authenticate(array $credentials)` contract; never implement it |
| `Authorizator`, `Role`, `Resource` | `IAuthorizator`, `IRole`, `IResource` |
| `Authenticator::IdentityNotFound`, `InvalidCredential` | `IDENTITY_NOT_FOUND`, `INVALID_CREDENTIAL` |
| `Authorizator::All`, `Allow`, `Deny` | `ALL`, `ALLOW`, `DENY` |
| `User::LogoutManual`, `LogoutInactivity` | `LOGOUT_MANUAL`/`MANUAL`, `LOGOUT_INACTIVITY`/`INACTIVITY` |

### Authenticator

```php
// interface Nette\Security\Authenticator – error codes: IdentityNotFound, InvalidCredential, Failure, NotApproved
function authenticate(string $username, string $password): IIdentity;
```

`InvalidCredential` is **singular**, no trailing `s`. The error code is the exception's second argument:

```php
public function authenticate(string $username, string $password): SimpleIdentity
{
	$row = $this->database->table('users')->where('username', $username)->fetch();
	if (!$row) {
		throw new AuthenticationException('User not found.', self::IdentityNotFound);
	}
	if (!$this->passwords->verify($password, $row->password)) {
		throw new AuthenticationException('Invalid password.', self::InvalidCredential);
	}
	if ($this->passwords->needsRehash($row->password)) { // transparent hash upgrade
		$this->database->table('users')->wherePrimary($row->id)
			->update(['password' => $this->passwords->hash($password)]);
	}

	return new SimpleIdentity($row->id, $row->role, ['name' => $row->username]);
}
```

Register it as an ordinary service (`services: - MyAuthenticator`); `User` picks it up by autowiring. With several authenticators, mark each `autowired: self` and call `$user->setAuthenticator($this->authenticator)` before `login()`. The return type may be narrowed to `SimpleIdentity` (covariance). `SimpleIdentity(string|int $id, string|array|null $roles = null, ?iterable $data = null)` – roles accept a single string. Data is read via magic properties (`$identity->name`) and `getData()`; `IIdentity` declares only `getId()` and `getRoles()`.

### Passwords is a service with instance methods

`hash()`, `verify()` and `needsRehash()` are **instance** methods on the injected service – not static, and not a wrapper to skip in favour of `password_hash()` / `password_verify()`:

```php
$hash = $passwords->hash($password);       // throws on empty password
$passwords->verify($password, $hash);      // bool
$passwords->needsRehash($hash);            // bool – rehash on successful login
```

Configure the algorithm by overriding the service, or with the static factories (3.2.6+):

```neon
services:
	security.passwords: Nette\Security\Passwords(::PASSWORD_BCRYPT, [cost: 12])
	# or Nette\Security\Passwords::bcrypt(12)
	# or Nette\Security\Passwords::argon2id(memoryCost: 131072, timeCost: 4) – throws NotSupportedException without Argon2
```

Store hashes in a `VARCHAR(255)` column – `PASSWORD_DEFAULT` may change length between PHP versions.

### User

```php
$user->login($username, $password);   // or login($identity) directly; throws AuthenticationException
$user->logout();                      // keeps the identity!
$user->logout(true);                  // discards it
$user->getIdentity();                 // ?IIdentity – nullable
$user->getId();                       // string|int|null
$user->getLogoutReason();             // User::LogoutManual | User::LogoutInactivity | null
$user->onLoggedIn[] = fn() => ...;    // event array; also $onLoggedOut
```

**`logout()` does not clear the identity by default.** `getIdentity()` and `getId()` keep returning it afterwards, so "identity exists" is *not* "logged in" – gate on `isLoggedIn()`. Use `logout(true)`, or set `$user->persistIdentity = false` / `security: authentication: persistIdentity: false` to disable retention globally. Cookie storage cannot retain it either way.

`isInRole()` / `getRoles()` / `isAllowed()` work with **effective** roles: the identity's roles when logged in, otherwise `$user->guestRole` (`'guest'`), never the retained identity's roles – so they need no `isLoggedIn()` guard. Session-id regeneration on login and logout is done by `SessionStorage`; do not add your own.

### Expiration

`$user->setExpiration('30 minutes')` must be called **before** `login()`; `null` cancels it. It is inactivity-based and **sliding** – the window restarts on every request that touches the storage. On expiry the user becomes logged out and `getLogoutReason()` returns `User::LogoutInactivity`; the identity survives unless `setExpiration($time, clearIdentity: true)`. It cannot outlive the session itself (`session: expiration:`, default 3 hours).

### Authorization

`Authorizator` has one method, and its parameters are **string-typed**: `isAllowed(?string $role, ?string $resource, ?string $privilege): bool`. `User::isAllowed(mixed $resource = Authorizator::All, mixed $privilege = Authorizator::All)` omits the role – it loops over the user's effective roles and returns true if any is allowed. `Authorizator::All` is `null` and means "anything".

`Permission` is the built-in ACL:

```php
$acl = new Nette\Security\Permission;
$acl->addRole('registered', 'guest');       // roles inherit
$acl->addResource('perex', 'article');      // resources inherit too
$acl->allow('guest', ['article', 'perex'], 'view');
$acl->allow('admin', $acl::All, ['view', 'edit']);
$acl->deny('admin', 'poll', 'edit');
$acl->allow('registered', 'article', 'edit', fn(Permission $acl, ?string $role, ?string $res, ?string $priv)
	=> $acl->getQueriedRole()->id === $acl->getQueriedResource()->authorId);
```

`Permission::isAllowed(string|Role|null, string|Resource|null, ?string)` also takes objects implementing `Role::getRoleId()` / `Resource::getResourceId()`; assertions then read them back with `getQueriedRole()` / `getQueriedResource()`.

**Multiple-parent role weight: the LAST parent listed wins.** `addRole('john', ['admin', 'guest'])` gives guest's `deny` precedence; `['guest', 'admin']` gives admin's `allow` precedence. This is easy to get backwards. Register the finished ACL through a factory: `services: - App\Model\AuthorizatorFactory::create`.

### NEON configuration

```neon
security:
	users:                  # creates SimpleAuthenticator – testing only
		johndoe: 'secret123'
		janedoe:
			password: 'secret123'
			roles: [admin]
			data: {name: Jane}   # ends up in the identity

	roles: {registered: [guest]}      # role: parent(s)
	resources: {comment: [article]}   # resource: parent

	rules:                  # 3.2.6+; [role(s), resource(s), privilege(s)], omitted = all
		allow:
			- [guest, article, view]
			- [registered, comment, [add, edit]]
			- [admin]       # everything
		deny:
			- [banned, comment, add]

	authentication:
		expiration: 30 minutes
		storage: session    # or cookie
		persistIdentity: true
		cookieName: userId  # cookie storage only; plus cookieDomain, cookieSamesite
```

`SimpleAuthenticator` compares usernames **case-insensitively**, and accepts either a plaintext password or a crypt-format hash (auto-detected, 3.2.6+); always quote passwords and hashes – they contain characters NEON and DI treat specially. Defining `roles`/`resources`/`rules` registers a `Permission` service – if you also register your own authorizator, define everything there instead, or you end up with two competing services.

### IdentityHandler – fixing stale roles

The identity is serialized into the storage at login, so **role and data changes do not reach a logged-in user until re-login**. Let the authenticator also implement `Nette\Security\IdentityHandler` to refresh on every request:

```php
public function sleepIdentity(IIdentity $identity): IIdentity { return $identity; } // before writing

public function wakeupIdentity(IIdentity $identity): ?IIdentity  // after reading; null forces a logout
{
	$identity->setRoles($this->facade->getUserRoles($identity->getId()));
	return $identity;
}

// used whenever nobody is logged in; null = no guest identity
public function getGuestIdentity(): ?IIdentity { return new SimpleIdentity('guest', ['guest']); }
```

`sleepIdentity()` is also how cookie storage works: return a proxy identity whose id is a random `authtoken`, and reload the real row from it in `wakeupIdentity()`. The guest identity is never persisted.

### Traps

- **Never put the password hash into the identity.** `new SimpleIdentity($row->id, $row->role, (array) $row)` copies it into the session/cookie – unset it first.
- `getIdentity()` is nullable; `$user->getIdentity()->name` on a guest is a fatal error. Prefer `$user->getId()`, or check `isLoggedIn()`.
- Reading an unknown identity data key raises an undefined-key warning – it is a plain array lookup, not a null-safe getter.
- Multiple independent logins in one session need `$user->getStorage()->setNamespace('backend')` in every place of that section (e.g. `BasePresenter::checkRequirements()`), plus `$user->refreshStorage()` if switched mid-request.

### Online Documentation

For details, see the official documentation:

- [Authentication](https://doc.nette.org/en/security/authentication) – login, identity, storage, IdentityHandler
- [Authorization](https://doc.nette.org/en/security/authorization) – roles, resources, Permission ACL
- [Password Hashing](https://doc.nette.org/en/security/passwords) – Passwords API
- [Security Configuration](https://doc.nette.org/en/security/configuration) – the `security:` section

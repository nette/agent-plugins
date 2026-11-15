---
name: nette-schema
description: Provides Nette Schema for data validation and normalization. Use when validating configuration, API inputs, or any data structures with Expect class. Covers Expect::structure(), Expect::from(), anyOf, enum, tuple, arrayOf, listOf, assert, transform, castTo, otherItems, merging layers (processMultiple, mergeMode), exporting a schema to JSON Schema, and Processor. This is about nette/schema – not Nette Forms validation (addRule), not Nette\Utils\Validators, and not validating against a JSON Schema.
---

## Nette Schema

A library for validating and normalizing data structures against a defined schema. Declare what correct data looks like, and Schema verifies and transforms input in one step.

```shell
composer require nette/schema
```

### When to Use Schema

- **Configuration validation** – validate config arrays loaded from .neon files
- **API input validation** – validate request data before processing
- **Data import/transform** – normalize external data into typed structures
- **DI extension config** – validate extension configuration in `loadConfiguration()`

Schema is complementary to Nette Forms (which handles user input with UI) and database constraints (which enforce storage-level rules). Use Schema for programmatic data validation where there's no form UI.

### Basic Usage

```php
use Nette\Schema\Expect;
use Nette\Schema\Processor;

$schema = Expect::structure([
	'name' => Expect::string()->required(),
	'email' => Expect::email(),
	'age' => Expect::int()->min(0)->max(120),
]);

$processor = new Processor;

try {
	$normalized = $processor->process($schema, $data);
	// $normalized is stdClass with validated data
} catch (Nette\Schema\ValidationException $e) {
	echo 'Invalid: ' . $e->getMessage();
	// $e->getMessages() returns array of all errors
}
```

### Data Types

```php
Expect::string()              // string, default null
Expect::string('default')     // string with default value
Expect::int()                 // integer
Expect::float()               // float
Expect::bool()                // boolean
Expect::null()                // null only
Expect::array()               // array, default []
Expect::scalar()              // scalar value
Expect::type('ClassName')     // instance of class
Expect::type('bool|string')   // union types
```

### Arrays

```php
// Array of strings
Expect::arrayOf('string')
Expect::arrayOf(Expect::string())

// Array with string keys
Expect::arrayOf('string', 'string')

// List (indexed array)
Expect::listOf('string')
Expect::listOf('email', wrap: true)  // since 1.4: a single value becomes a one-item list

// Tuple (fixed positions, since 1.4); Expect::array([...]) is a structure-like array with keys
Expect::tuple([
	Expect::int(),
	Expect::string(),
	Expect::bool(),
])
```

On the 1.x line `'?int[]'` means an array of nullable ints; 2.0 reads it as phpDoc does (a
nullable array). Write `Expect::arrayOf(Expect::int()->nullable())`, which means the same in both.

### Structures

```php
$schema = Expect::structure([
	'database' => Expect::structure([
		'host' => Expect::string()->required(),
		'port' => Expect::int(3306),
		'user' => Expect::string()->required(),
		'password' => Expect::string()->nullable(),
	]),
	'debug' => Expect::bool(false),
]);
```

Properties are optional by default (null). Use `required()` for mandatory fields.

### Enumeration

```php
// One of specific values
Expect::anyOf('small', 'medium', 'large')

// One of values or schemas
Expect::anyOf(
	Expect::string(),
	Expect::int(),
	null
)

// First is default
Expect::anyOf('small', 'medium', 'large')->firstIsDefault()

// Backed enum (since 1.4): accepts the value or the case, always gives the case
Expect::enum(Mode::class)->default(Mode::Production)
```

`Expect::type(Mode::class)` is different: like any class name, it accepts only the case itself.

### Constraints

```php
// Required field
Expect::string()->required()

// Nullable (accepts null)
Expect::string()->nullable()

// Default value
Expect::string()->default('hello')
Expect::string('hello')  // shorthand

// Length/count limits
Expect::string()->min(3)->max(100)
Expect::array()->min(1)->max(10)

// Numeric range
Expect::int()->min(0)->max(100)

// Pattern
Expect::string()->pattern('\d{5}')  // regex for entire value

// Description, also exported to JSON Schema (since 1.4)
Expect::int(3306)->description('Port the server listens on')
```

Since 1.4 `pattern()` on a number or an array, and `min()`/`max()` on a union such as
`Expect::type('int|string')`, raise `E_USER_DEPRECATED`; put the limits on each `anyOf()` variant.

### Assertions

```php
// Custom validation
Expect::string()->assert(fn($s) => strlen($s) % 2 === 0, 'Must be even length')

// Built-in validators
Expect::string()->assert('is_file')
Expect::string()->assert('ctype_alpha')
```

### Transformations

```php
// Transform value after validation
Expect::string()->transform(fn($s) => strtoupper($s))

// Chain transformations
Expect::string()
	->assert('ctype_lower', 'Must be lowercase')
	->transform(fn($s) => strtoupper($s))

// Transform with validation
Expect::string()->transform(function ($s, $context) {
	if (!ctype_alpha($s)) {
		// $code is REQUIRED – a single-argument call is an ArgumentCountError
		$context->addError('Must be letters only', Nette\Schema\Message::FailedAssertion);
		return null;
	}
	return strtoupper($s);
})
```

### Casting

```php
// Cast to type
Expect::scalar()->castTo('string')
Expect::scalar()->castTo('int')
Expect::scalar()->castTo('bool')

// Cast to class (without constructor)
Expect::structure([
	'name' => Expect::string(),
	'age' => Expect::int(),
])->castTo(Person::class)

// Cast to class with constructor
Expect::structure([
	'host' => Expect::string(),
	'port' => Expect::int(),
])->castTo(DatabaseConfig::class)
// Creates: new DatabaseConfig(host: ..., port: ...)
```

Since 1.4 `null` is never cast to an object or enum, and `castTo('?string')` keeps `null`
while `castTo('string')` turns it into `''`.

### Normalization (before)

```php
// Normalize before validation
Expect::arrayOf('string')
	->before(fn($v) => is_string($v) ? explode(' ', $v) : $v)

// Now accepts both:
// - ['a', 'b', 'c']
// - 'a b c' (converted to array)
```

An element has **one** `before()` handler: a second call replaces the first (and since 1.4
raises `E_USER_DEPRECATED`, as 2.0 will chain them). Put all normalization into one handler.

### Merging and JSON Schema

`$processor->processMultiple($schema, [$defaults, $local])` merges configuration layers. Since 1.4
`mergeMode(MergeMode::Replace)` (or `AppendKeys`, `OverwriteKeys`) and `mergeWith()` choose how a
layer combines with the previous one. `Nette\Schema\JsonSchema::export($schema)` (since 1.4) gives
a JSON Schema document for editors; defaults, `castTo()` and `transform()` are not exported.

### Structure Options

```php
// Allow extra items
Expect::structure([
	'known' => Expect::string(),
])->otherItems(Expect::mixed())

// Skip default values in output
Expect::structure([
	'debug' => Expect::bool(false),
])->skipDefaults()

// Extend structure
$base = Expect::structure(['name' => Expect::string()]);
$extended = $base->extend(['email' => Expect::email()]);
```

### From Class

Generate schema from class properties:

```php
class Config
{
	public string $name;
	public ?string $email = null;
	public bool $debug = false;
}

$schema = Expect::from(new Config);

// Override specific fields
$schema = Expect::from(new Config, [
	'email' => Expect::email()->required(),
]);
```

### Deprecation

```php
$schema = Expect::structure([
	'oldOption' => Expect::int()->deprecated('Use newOption instead'),
	'newOption' => Expect::int(),
]);

$processor->process($schema, $data);
$warnings = $processor->getWarnings();
```

### Practical Examples

**Configuration validation:**

```php
$configSchema = Expect::structure([
	'database' => Expect::structure([
		'driver' => Expect::anyOf('mysql', 'pgsql', 'sqlite')->required(),
		'host' => Expect::string('localhost'),
		'port' => Expect::int(),
		'name' => Expect::string()->required(),
		'user' => Expect::string()->required(),
		'password' => Expect::string()->nullable(),
	])->castTo('array'),

	'cache' => Expect::structure([
		'enabled' => Expect::bool(true),
		'ttl' => Expect::int(3600)->min(0),
	]),

	'mail' => Expect::structure([
		'from' => Expect::email()->required(),
		'smtp' => Expect::structure([
			'host' => Expect::string(),
			'port' => Expect::int(587),
			'secure' => Expect::anyOf('tls', 'ssl', null),
		]),
	]),
]);
```

**API input validation:**

```php
$createUserSchema = Expect::structure([
	'username' => Expect::string()
		->required()
		->min(3)->max(20)
		->pattern('[a-z0-9_]+'),
	'email' => Expect::email()->required(),
	'password' => Expect::string()->required()->min(8),
	'roles' => Expect::listOf(
		Expect::anyOf('user', 'admin', 'moderator')
	)->default(['user'])->mergeDefaults(false),
])->castTo('array');
```

**Defaults of arrays and lists are MERGED into the input, not replaced.** Without
`mergeDefaults(false)` above, a client sending `['admin']` ends up with
`['user', 'admin']` – the default silently survives. That is harmless for config
(where merging is the point) and a privilege bug for API input.

### Documentation

The full Nette documentation for this topic is bundled with the plugin. Read a page when you need details beyond this skill:

- [Schema](../../docs/schema/index.md) – overview and the Processor
- [Data Types](../../docs/schema/types.md) – every Expect method: types, defaults, ranges, structures, tuples
- [Validation and Normalization](../../docs/schema/normalization.md) – `assert()`, `transform()`, `castTo()`, `before()`, `from()`, errors
- [Merging Configurations](../../docs/schema/merging.md) – `processMultiple()`, `mergeMode()`, `mergeWith()`
- [Exporting to JSON Schema](../../docs/schema/json-schema.md) – export and its differences from validation
- [Custom Elements](../../docs/schema/custom-elements.md) – implementing the Schema interface

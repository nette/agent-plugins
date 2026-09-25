---
name: nette-utils
description: Provides Nette Utils helper classes. Use when working with Arrays, Strings, Image, Finder, FileSystem, Json, Validators, DateTime, Html element builder, Random, Callback, Type, or SmartObject from nette/utils. Do NOT use for Nette Schema, Nette Forms, Nette Database, Latte filters, or DI configuration.
---

## Nette Utils

A collection of useful PHP utility classes for everyday tasks.

```shell
composer require nette/utils
```

For detailed references:
- [Arrays, ArrayHash, ArrayList](references/arrays.md)
- [Strings and regex functions](references/strings.md)
- [Image manipulation](references/image.md)
- [File searching with Finder](references/finder.md)

---

## DateTime

Extended `DateTime` class with strict validation and DST fixes. There is also
`Nette\Utils\DateTimeImmutable` (since 4.1.5) with the same API on top of
`\DateTimeImmutable` — **prefer it for new code**, mutable date objects are a classic source of
action-at-a-distance bugs.

```php
use Nette\Utils\DateTime;

// Create from various formats
DateTime::from(1138013640);              // from timestamp
DateTime::from('2024-02-26 04:15:32');   // from string
DateTime::from($dateTimeInterface);       // from object

// Create from parts (throws on invalid date)
DateTime::fromParts(2024, 2, 26, 4, 15);

// Immutable modification
$clone = $original->modifyClone('+1 day');

// Convert relative time to seconds
DateTime::relativeToSeconds('10 minutes'); // 600
DateTime::relativeToSeconds('-1 hour');    // -3600

// JSON serialization (ISO 8601)
echo json_encode($dateTime); // "2024-02-26T04:15:32+01:00"
```

---

## Json

Safe JSON encoding/decoding with exceptions.

```php
use Nette\Utils\Json;

// Encode
$json = Json::encode($data);
$json = Json::encode($data, pretty: true);        // formatted
$json = Json::encode($data, asciiSafe: true);     // escape unicode
$json = Json::encode($data, htmlSafe: true);      // escape < > &
$json = Json::encode($data, forceObjects: true);  // arrays as objects

// Decode
$data = Json::decode($json);                    // returns stdClass
$data = Json::decode($json, forceArrays: true); // returns array (note the plural)

// Both throw Nette\Utils\JsonException on error
```

---

## Validators

Value validation and type checking.

```php
use Nette\Utils\Validators;

// Type checking
Validators::is($value, 'int');              // true/false
Validators::is($value, 'int|string|bool');  // union types
Validators::is($value, 'int:0..100');       // range
Validators::is($value, 'string:10..20');    // length range
Validators::is($value, 'array:1..5');       // count range

// Specific validators
Validators::isEmail('user@example.com');    // true
Validators::isUrl('https://nette.org');     // true
Validators::isUri('mailto:info@nette.org'); // true
Validators::isNumeric('123');               // true (string number)
Validators::isNumericInt('123');            // true (string integer)
Validators::isUnicode($string);             // valid UTF-8?
Validators::isInRange($value, [0, 100]);    // in range?
Validators::isNone($value);                 // 0, '', false, null, []?

// Assertion (throws on failure)
Validators::assert($value, 'string:5..10');
Validators::assertField($array, 'key', 'int');
```

### Expected Types

| Type | Description |
|------|-------------|
| `int`, `float`, `bool`, `string`, `array`, `null` | PHP types |
| `scalar` | `int\|float\|bool\|string` |
| `list` | indexed array |
| `number` | `int\|float` |
| `numeric` | number or numeric string |
| `unicode` | valid UTF-8 string |
| `email`, `url`, `uri` | format validation |
| `alnum`, `alpha`, `digit`, `lower`, `upper` | character classes |
| `class`, `interface` | existing class/interface |
| `file`, `directory` | existing path |

---

## FileSystem

File operations with exception handling.

```php
use Nette\Utils\FileSystem;

// Read/Write
$content = FileSystem::read('/path/to/file');
FileSystem::write('/path/to/file', $content);

// Read large files line by line
foreach (FileSystem::readLines('/path/to/file') as $line) {
	echo $line;
}

// Copy/Move/Delete
FileSystem::copy($source, $target);
FileSystem::rename($source, $target);
FileSystem::delete($path);  // works on directories too

// Directory operations
FileSystem::createDir('/path/to/dir');
FileSystem::makeWritable('/path');

// Path utilities
FileSystem::isAbsolute('../path');                    // false
FileSystem::normalizePath('/file/../path');           // '/path'
FileSystem::joinPaths('a', 'b', 'file.txt');          // 'a/b/file.txt'
FileSystem::resolvePath('/base', '../file.txt');      // '/file.txt'
FileSystem::unixSlashes('path\\to\\file');            // 'path/to/file'
```

---

## Floats

Safe floating-point comparisons.

```php
use Nette\Utils\Floats;

// Compare floats (handles precision issues)
Floats::isZero(0.0);                    // true
Floats::areEqual(0.1 + 0.2, 0.3);       // true (!)
Floats::isLessThan($a, $b);
Floats::isLessThanOrEqualTo($a, $b);
Floats::isGreaterThan($a, $b);
Floats::isGreaterThanOrEqualTo($a, $b);

// Compare with result
Floats::compare($a, $b);  // -1, 0, or 1
```

---

## Random

Cryptographically secure random values.

```php
use Nette\Utils\Random;

// Random string (default: 0-9, a-z)
Random::generate(10);                    // 'a4b3c2d1e0'
Random::generate(10, 'A-Z');             // 'XYZABCDEF'
Random::generate(10, '0-9A-Za-z');       // 'aB3cD4eF5g'
Random::generate(10, 'A-Za-z!@#$%');     // 'aBc!@Def#$'
```

---

## Paginator

Pagination calculations.

```php
use Nette\Utils\Paginator;

$paginator = new Paginator;
$paginator->setItemCount(100);  // total items
$paginator->setItemsPerPage(10);
$paginator->setPage(3);

echo $paginator->getPageCount();    // 10
echo $paginator->getOffset();       // 20 (for SQL OFFSET)
echo $paginator->getLength();       // 10 (for SQL LIMIT)
echo $paginator->isFirst();         // false
echo $paginator->isLast();          // false
```

---

## Html

HTML element builder.

```php
use Nette\Utils\Html;

// Create element
$el = Html::el('a', ['href' => 'https://nette.org']);
$el->setText('Nette');
echo $el;  // <a href="https://nette.org">Nette</a>

// Fluent interface
$el = Html::el('div')
	->id('container')
	->class('main active')
	->data('id', 123)
	->setHtml('<p>Content</p>');

// Shorthand
Html::el('input', ['type' => 'text', 'name' => 'email']);
Html::el('div class="box"');  // from string
```

**Escaping is explicit and it is the one thing to get right here.** `setText()` / `addText()`
escape, `setHtml()` / `addHtml()` do not — reach for the `Html` variants only when the string
is provably safe. `add()` escapes strings but inserts `Html` objects as they are.

`class` and `style` behave as arrays, which is what makes conditional markup readable —
a `null` item is skipped, so no `if` is needed around it:

```php
$el->class[] = 'active';
$el->class[] = $isTop ? 'top' : null;   // null is ignored
$el->style['color'] = 'green';
$el->data('config', ['a' => 1]);        // array is JSON-encoded
```

---

## Process

Running external processes (since 4.1.4) — use this instead of `exec()`, `shell_exec()` or
pulling in `symfony/process`.

```php
use Nette\Utils\Process;

// No shell involved, arguments are passed as an array -> nothing to escape
$p = Process::runExecutable('git', ['log', '-1', '--format=%H']);
$p->ensureSuccess();              // returns void, do not chain
echo $p->getStdOutput();          // note the capital O

// Shell string: convenient for pipes, NEVER build it from untrusted input
$p = Process::runCommand('git log --oneline | head -n 20');
```

Both take `$env`, `$options`, `$stdin`, `$stdout`, `$stderr`, `$directory` and `$timeout`
(default 60 s) — with that many parameters, pass them as named arguments
(`timeout: 30`). Instance methods: `wait()`, `isRunning()`, `getExitCode()`, `isSuccess()`,
`ensureSuccess()`, `getStdOutput()`, `getStdError()`, `terminate()`, `detach()`, `getPid()`.
Reading output throws if it was redirected, discarded or piped instead of captured.

## Callback

Working with PHP callables.

```php
use Nette\Utils\Callback;

// Normalize to closure – use PHP itself, there is no Callback::closure() in Utils 4
$closure = $callable(...);
$closure = $obj->method(...);

// Check validity
Callback::check($callable);  // throws if invalid

// Invoke a native function and turn its warnings into an exception.
// First argument is a function NAME, not a callable; the handler gets (string $message, int $severity).
Callback::invokeSafe(
	'preg_match',
	[$pattern, $subject],
	fn(string $message) => throw new \RuntimeException($message),
);

// Reflection
$reflection = Callback::toReflection($callable);
```

---

## Type

PHP type utilities.

```php
use Nette\Utils\Type;

// Get type from reflection
$type = Type::fromReflection($reflectionProperty);
$type = Type::fromReflection($reflectionParameter);

// Parse type string
$type = Type::fromString('int|string|null');

// Type info
$type->getSingleName();     // 'int' or null if union
$type->getNames();          // ['int', 'string', 'null']
$type->isUnion();           // true
$type->isIntersection();    // false
$type->isBuiltin();         // false – only true for a SINGLE built-in type
$type->allows('null');      // true – there is no allowsNull() method
$type->isClass();           // false
```

---

## SmartObject Trait

Getter/setter property access for classes. Its historical jobs are done by PHP itself now;
for new code prefer PHP 8.4 property hooks. The trait is still useful for exposing
getters as read-only properties.

**The `@property` annotation is mandatory** – without it the magic access throws
`MemberAccessException`, because the trait only maps properties declared in the docblock.

```php
use Nette\SmartObject;

/**
 * @property string $name
 */
class MyClass
{
	use SmartObject;

	private string $name;

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}
}

$obj = new MyClass;
$obj->name = 'John';    // calls setName()
echo $obj->name;        // calls getName()
```

### Online Documentation

For details, see the official documentation:

- [Arrays](https://doc.nette.org/en/utils/arrays) – array helpers
- [Strings](https://doc.nette.org/en/utils/strings) – string functions and regex
- [Images](https://doc.nette.org/en/utils/images) – image manipulation
- [FileSystem](https://doc.nette.org/en/utils/filesystem) – file operations
- [Finder](https://doc.nette.org/en/utils/finder) – file searching
- [JSON](https://doc.nette.org/en/utils/json) – safe JSON handling
- [Validators](https://doc.nette.org/en/utils/validators) – type checking and validation

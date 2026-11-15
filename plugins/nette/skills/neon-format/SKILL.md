---
name: neon-format
description: Invoke before creating or modifying .neon files. Provides NEON syntax and Nette configuration conventions. Use when writing or editing .neon config files, asking about NEON syntax (entities, multiline strings, inline notation, special values, escaping), converting YAML to NEON, parsing or encoding NEON in PHP (Neon::decode, Neon::encode), editing a NEON file from code while keeping its formatting (Neon::parse), running neon-lint, or debugging NEON syntax errors. For phpstan.neon, use the phpstan-analysis skill (from nette-dev plugin) instead. Do not trigger for Nette DI concepts like autowiring and service registration without .neon file context.
---

## NEON Format

NEON (Nette Object Notation) is a human-readable data format used for configuration files in Nette. Similar to YAML but with support for entities and tab indentation.

```shell
composer require nette/neon
```

### Mappings

Key-value pairs with required space after colon:

```neon
street: 742 Evergreen Terrace
city: Springfield
country: USA
```

Inline notation with braces:

```neon
{street: 742 Evergreen Terrace, city: Springfield, country: USA}
```

### Sequences

Indexed arrays with hyphen and space:

```neon
- Cat
- Dog
- Goldfish
```

Inline notation with brackets:

```neon
[Cat, Dog, Goldfish]
```

### Nesting

Indentation defines structure:

```neon
pets:
	- Cat
	- Dog
cars:
	- Volvo
	- Skoda
```

Block and inline can be combined:

```neon
pets: [Cat, Dog]
cars:
	- Volvo
	- Skoda
```

### Strings

Unquoted, single-quoted, or double-quoted:

```neon
- An unquoted string
- 'Single-quoted string'
- "Double-quoted with \t escapes"
```

Quote strings containing: `# " ' , : = - [ ] { } ( )`

Double a quote to include it: `'It''s working'`

Multiline strings with triple quotes:

```neon
'''
	first line
		second line
	third line
'''
```

### Special Values

```neon
# Numbers
count: 12
price: 12.3
scientific: +1.2e-34
binary: 0b11010
octal: 0o666
hex: 0x7A

# Null
value: null
empty:

# Booleans
enabled: true
disabled: false
active: yes
inactive: no

# Dates (auto-converted to DateTimeImmutable)
date: 2016-06-03
datetime: 2016-06-03 19:00:00
with_tz: 2016-06-03 19:00:00 +02:00
```

Unlike YAML: `on`/`off` are strings, `0777` is the decimal 777, and `1_000`, `0X10`, `-0x1`
and an invalid date such as `2024-02-30` stay strings.

### Entities

Function-like structures for DI configuration:

```neon
Column(type: int, nulls: yes)
```

Chained entities:

```neon
Column(type: int) Field(id: 1)
```

Multiline entity:

```neon
Column(
	type: int
	nulls: yes
)
```

### Comments

```neon
# This line is ignored
street: 742 Evergreen Terrace  # inline comment
```

### Block vs Inline Notation

Use **inline** for short, simple values (up to ~3 items):
```neon
extensions: [foo, bar]
roles: {admin: true, user: false}
```

Use **block** for anything complex, nested, or long:
```neon
services:
	- App\Model\UserService
	- App\Model\OrderService
```

### Key Rules

- Space after `:` is required (`key: value`, not `key:value`)
- Use **tabs** for indentation. Spaces are valid too (`Neon::encode()` just defaults to tabs);
  unlike YAML, NEON accepts either, so never "fix" space-indented NEON
- Block notation cannot be nested inside inline notation
- Unquoted strings cannot start/end with spaces or look like numbers/booleans/dates

## PHP API

```php
use Nette\Neon\Neon;
```

### encode(mixed $value, bool|callable $blockMode=false, string $indentation="\t"): string

Converts PHP value to NEON. Pass `true` to `$blockMode` for multiline output, or since 4.0
a callable `fn($array, $path): bool` deciding per array. The constant `Neon::BLOCK` is deprecated.

```php
Neon::encode($value);       // inline NEON
Neon::encode($value, true); // multiline NEON
Neon::encode($value, fn($value, $path) => !$path); // blocks on the first level only
```

### decode(string $neon): mixed

Parses NEON string to PHP value. Dates become `DateTimeImmutable`, entities become `Nette\Neon\Entity`.

```php
Neon::decode('hello: world'); // ['hello' => 'world']
```

### decodeFile(string $file): mixed

Parses NEON file to PHP value (removes BOM).

```php
Neon::decodeFile('config.neon');
```

All methods throw `Nette\Neon\Exception` on error.

### Editing a File: parse()

To change a file programmatically, never `decode()` and `encode()` it: that loses comments,
formatting and number styles. Since nette/neon 4.0 `Neon::parse()` gives a lossless tree:

```php
$doc = Neon::parse(file_get_contents('config.neon'));
$doc->setValue(['database', 'host'], 'db.example.com'); // adds a missing last key; a missing parent throws
$doc->addItem(['services'], 'App\Model\OrderFacade');
$doc->removeItem(['database', 'debug']);
$doc->setKey(['mail'], 'mailer');
file_put_contents('config.neon', $doc->print());
```

Only the touched values change, written in the style of the old ones (`yes` becomes `no`).

### Lint Command

Check syntax errors in `.neon` files or directories:

```shell
vendor/bin/neon-lint <path>
```

It prints `file:line:column: message` per problem and exits 0 when all files are valid, 1 on a
finding. Since 4.0 `--check` also verifies the file reads back byte for byte, `--json` gives JSON output.

### Documentation

The full Nette documentation for this topic is bundled with the plugin. Read a page when you need details beyond this skill:

- [NEON Format](../../docs/neon/format.md) – the complete syntax
- [NEON for PHP](../../docs/neon/php.md) – `Neon::encode()`, `Neon::decode()`, Evaluator, the linter
- [Editing NEON Files](../../docs/neon/editing.md) – lossless changes through the syntax tree

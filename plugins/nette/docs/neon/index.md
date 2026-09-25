# Nette NEON

NEON is a human-friendly data serialization language. It is used in Nette for configuration files. [Nette\Neon\Neon](https://api.nette.org/neon/master/Nette/Neon/Neon.html) is a static class for working with NEON.

Get to know the [NEON format](format.md) and [try it out](https://fiddle.nette.org/neon/).

All examples assume the following alias is defined:

```php
use Nette\Neon\Neon;
```

## Installation

Download and install the package using [Composer](https://doc.nette.org/en/best-practices/composer):

```shell
composer require nette/neon
```

You can check for syntax errors in `*.neon` files using the `neon-lint` console command:

```shell
vendor/bin/neon-lint [--debug] <path>
```

## `encode(mixed $value, bool $blockMode=false, string $indentation="\t"): string`

Returns `$value` converted to NEON. You can pass `true` to the `$blockMode` parameter to create multiline output. The `$indentation` parameter specifies the characters used for indentation (default is tab).

```php
Neon::encode($value); // Returns $value converted to NEON
Neon::encode($value, true); // Returns $value converted to multiline NEON
```

The `encode()` method throws `Nette\Neon\Exception` on error.

```php
try {
	$neon = Neon::encode($value);
} catch (Nette\Neon\Exception $e) {
	// Exception handling
}
```

## `decode(string $input): mixed`

Converts the given NEON string to a PHP value.

Returns scalars, arrays, [dates](format.md#dates) as DateTimeImmutable objects, and [entities](format.md#entities) as [Nette\Neon\Entity](https://api.nette.org/neon/master/Nette/Neon/Entity.html) objects.

```php
Neon::decode('hello: world'); // Returns an array ['hello' => 'world']
```

The `decode()` method throws `Nette\Neon\Exception` on error.

```php
try {
	$value = Neon::decode($neon);
} catch (Nette\Neon\Exception $e) {
	// Exception handling
}
```

## `decodeFile(string $file): mixed`

Converts the contents of a file from NEON to PHP and removes any BOM.

```php
Neon::decodeFile('config.neon');
```

The `decodeFile()` method throws `Nette\Neon\Exception` on error.

If you are upgrading to a newer version, see the [upgrading](upgrading.md) page.

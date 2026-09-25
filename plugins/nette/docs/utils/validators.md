# Value Validators

Contents:

- [Basic Usage](#basic-usage)
- [Expected Types](#expected-types)
- [Assertion](#assertion)
  - [`assert($value, string $expected, string $label='variable'): void`](#assertvalue-string-expected-string-labelvariable-void)
  - [`assertField(array $array, string|int $key, ?string $expected=null, string $label="item '%' in array"): void`](#assertfieldarray-array-stringint-key-string-expectednull-string-labelitem--in-array-void)
- [Validators](#validators)
  - [`is($value, string $expected): bool`](#isvalue-string-expected-bool)
  - [`everyIs(iterable $values, string $expected): bool`](#everyisiterable-values-string-expected-bool)
  - [`isEmail(string $value): bool`](#isemailstring-value-bool)
  - [`isInRange(mixed $value, array $range): bool`](#isinrangemixed-value-array-range-bool)
  - [`isNone(mixed $value): bool`](#isnonemixed-value-bool)
  - [`isNumeric(mixed $value): bool`](#isnumericmixed-value-bool)
  - [`isNumericInt(mixed $value): bool`](#isnumericintmixed-value-bool)
  - [`isPhpIdentifier(string $value): bool`](#isphpidentifierstring-value-bool)
  - [`isBuiltinType(string $type): bool`](#isbuiltintypestring-type-bool)
  - [`isTypeDeclaration(string $type): bool`](#istypedeclarationstring-type-bool)
  - [`isClassKeyword(string $name): bool`](#isclasskeywordstring-name-bool)
  - [`isUnicode(mixed $value): bool`](#isunicodemixed-value-bool)
  - [`isUrl(string $value): bool`](#isurlstring-value-bool)
  - [`isUri(string $value): bool`](#isuristring-value-bool)


Need to quickly and easily verify that a variable contains, for example, a valid email address? Then [Nette\Utils\Validators](https://api.nette.org/utils/master/Nette/Utils/Validators.html) will come in handy, a static class with useful functions for validating values.

Installation:

```shell
composer require nette/utils
```

All examples assume the following class alias is defined:

```php
use Nette\Utils\Validators;
```

## Basic Usage

The `Validators` class provides numerous methods for checking values, such as [isUnicode()](#isunicodemixed-value-bool), [isEmail()](#isemailstring-value-bool), [isUrl()](#isurlstring-value-bool), etc., for use in your code:

```php
if (!Validators::isEmail($email)) {
	throw new InvalidArgumentException('Invalid email address provided.');
}
```

Furthermore, it can verify whether the value satisfies the so-called [expected types](#expected-types), which is a string where the individual options are separated by a vertical bar `|`. This makes it easy to verify union types using [is()](#isvalue-string-expected-bool):

```php
if (!Validators::is($val, 'int|string|bool')) {
	// Handle invalid type...
}
```

This also allows you to create systems where expectations need to be written as strings (e.g., in annotations or configurations) and then validate values against them.

You can also declare an [assertion](#assertvalue-string-expected-string-labelvariable-void), which throws an exception if the expectation is not met.

## Expected Types

Expected types form a string consisting of one or more variants separated by a pipe `|`, similar to how types are written in PHP (e.g., `'int|string|bool'`). Nullable notation `?int` is also accepted.

An array where all elements are of a certain type is written in the form `int[]`.

Some types can be followed by a colon and a length `:length` or a range `:[min]..[max]`, e.g., `string:10` (a string of 10 bytes length), `float:10..` (a number 10 or greater), `array:..10` (an array with up to ten elements), or `list:10..20` (a list with 10 to 20 elements), or a regular expression like `pattern:[0-9]+`.

Overview of types and rules:

<table>
<tr><th colspan="2">PHP types</th></tr>
<tr><td>array</td><td>range for the number of elements can be specified</td></tr>
<tr><td>bool</td><td> </td></tr>
<tr><td>boolean</td><td>alias for bool</td></tr>
<tr><td>float</td><td>range for the value can be specified</td></tr>
<tr><td>int</td><td>range for the value can be specified</td></tr>
<tr><td>integer</td><td>alias for int</td></tr>
<tr><td>null</td><td> </td></tr>
<tr><td>object</td><td> </td></tr>
<tr><td>resource</td><td> </td></tr>
<tr><td>scalar</td><td>`int</td><td>float</td><td>bool</td><td>string`</td></tr>
<tr><td>string</td><td>range for the length in bytes can be specified</td><td> </td><td> </td><td> </td></tr>
<tr><td>callable</td><td> </td><td> </td><td> </td><td> </td></tr>
<tr><td>iterable</td><td> </td><td> </td><td> </td><td> </td></tr>
<tr><td>mixed</td><td> </td><td> </td><td> </td><td> </td></tr>
<tr><th colspan="2">Pseudo-types</th><th> </th><th> </th><th> </th></tr>
<tr><td>list</td><td>indexed array, range for the number of elements can be specified</td><td> </td><td> </td><td> </td></tr>
<tr><td>none</td><td>empty value: &#039;&#039;, null, false, 0, 0.0, []</td><td> </td><td> </td><td> </td></tr>
<tr><td>number</td><td>`int</td><td>float`</td><td> </td><td> </td></tr>
<tr><td>numeric</td><td>number including string representation</td><td> </td><td> </td><td> </td></tr>
<tr><td>numericint</td><td>integer including string representation</td><td> </td><td> </td><td> </td></tr>
<tr><td>unicode</td><td>UTF-8 string, range for the length in characters can be specified</td><td> </td><td> </td><td> </td></tr>
<tr><th colspan="2">Character class (must not be an empty string)</th><th> </th><th> </th><th> </th></tr>
<tr><td>alnum</td><td>all characters are alphanumeric</td><td> </td><td> </td><td> </td></tr>
<tr><td>alpha</td><td>all characters are letters [A-Za-z]</td><td> </td><td> </td><td> </td></tr>
<tr><td>digit</td><td>all characters are digits</td><td> </td><td> </td><td> </td></tr>
<tr><td>lower</td><td>all characters are lowercase letters [a-z]</td><td> </td><td> </td><td> </td></tr>
<tr><td>space</td><td>all characters are whitespace</td><td> </td><td> </td><td> </td></tr>
<tr><td>upper</td><td>all characters are uppercase letters [A-Z]</td><td> </td><td> </td><td> </td></tr>
<tr><td>xdigit</td><td>all characters are hexadecimal digits [0-9A-Fa-f]</td><td> </td><td> </td><td> </td></tr>
<tr><th colspan="2">Syntax validation</th><th> </th><th> </th><th> </th></tr>
<tr><td>pattern</td><td>a regular expression that the entire string must match</td><td> </td><td> </td><td> </td></tr>
<tr><td>email</td><td>Email</td><td> </td><td> </td><td> </td></tr>
<tr><td>identifier</td><td>PHP identifier</td><td> </td><td> </td><td> </td></tr>
<tr><td>url</td><td>URL</td><td> </td><td> </td><td> </td></tr>
<tr><td>uri</td><td>URI</td><td> </td><td> </td><td> </td></tr>
<tr><th colspan="2">Environment validation</th><th> </th><th> </th><th> </th></tr>
<tr><td>class</td><td>is an existing class name</td><td> </td><td> </td><td> </td></tr>
<tr><td>interface</td><td>is an existing interface name</td><td> </td><td> </td><td> </td></tr>
<tr><td>directory</td><td>is an existing directory path</td><td> </td><td> </td><td> </td></tr>
<tr><td>file</td><td>is an existing file path</td><td> </td><td> </td><td> </td></tr>
</table>

## Assertion

### `assert($value, string $expected, string $label='variable'): void`

Verifies that the value is one of the [expected types](#expected-types) separated by a pipe. If not, it throws an [Nette\Utils\AssertionException](https://api.nette.org/utils/master/Nette/Utils/AssertionException.html). The word `variable` in the exception message can be replaced by the `$label` parameter.

```php
Validators::assert('Nette', 'string:5'); // OK (string 'Nette' has 5 bytes)
Validators::assert('Lorem ipsum dolor sit', 'string:78');
// AssertionException: The variable expects to be string in range 78, string 'Lorem ipsum dolor sit' given.
```

### `assertField(array $array, string|int $key, ?string $expected=null, string $label="item '%' in array"): void`

Verifies that the element with key `$key` in array `$array` is one of the [expected types](#expected-types) separated by a pipe. If not, it throws an [Nette\Utils\AssertionException](https://api.nette.org/utils/master/Nette/Utils/AssertionException.html). The string `item '%' in array` in the exception message can be replaced by the `$label` parameter.

```php
$arr = ['foo' => 'Nette'];

Validators::assertField($arr, 'foo', 'string:5'); // OK
Validators::assertField($arr, 'bar', 'string:15');
// AssertionException: Missing item 'bar' in array.
Validators::assertField($arr, 'foo', 'int');
// AssertionException: The item 'foo' in array expects to be int, string 'Nette' given.
```

## Validators

### `is($value, string $expected): bool`

Checks if the value is one of the [expected types](#expected-types) separated by a pipe.

```php
Validators::is(1, 'int|float');  // true
Validators::is(23, 'int:0..10'); // false (23 is outside the range 0-10)
Validators::is('Nette Framework', 'string:15');     // true, length is 15 bytes
Validators::is('Nette Framework', 'string:8..');    // true
Validators::is('Nette Framework', 'string:30..40'); // false
```

### `everyIs(iterable $values, string $expected): bool`

Checks whether every value in the iterable is one of the [expected types](#expected-types) separated by a pipe. It works like [is()](#isvalue-string-expected-bool) applied to each item.

```php
$list = ['Nette', 'Framework', 2020];
Validators::everyIs($list, 'string');     // false (2020 is not a string)
Validators::everyIs($list, 'string|int'); // true
```

### `isEmail(string $value): bool`

Verifies that the value is a valid email address. It does not verify that the domain actually exists, only the syntax is verified. The function also accounts for future [TLDs](https://en.wikipedia.org/wiki/Top-level_domain), which may also be in unicode.

```php
Validators::isEmail('example@nette.org'); // true
Validators::isEmail('example@localhost'); // false
Validators::isEmail('nette');             // false
```

### `isInRange(mixed $value, array $range): bool`

Checks if the value is within the given range `[min, max]`, where the upper or lower bound can be omitted (`null`). Numbers, strings, and DateTime objects can be compared.

If both boundaries are missing (`[null, null]`) or the value is `null`, it returns `false`.

```php
Validators::isInRange(5, [0, 5]);     // true
Validators::isInRange(23, [null, 5]); // false
Validators::isInRange(23, [5]);       // true (equivalent to [5, null])
Validators::isInRange(1, [5]);        // false
```

### `isNone(mixed $value): bool`

Checks if the value is `0`, `''`, `false`, `null`, `0.0` or `[]`.

```php
Validators::isNone(0); // true
Validators::isNone(''); // true
Validators::isNone(false); // true
Validators::isNone(null); // true
Validators::isNone('nette'); // false
```

### `isNumeric(mixed $value): bool`

Checks if the value is a number or a number represented as a string.

```php
Validators::isNumeric(23);      // true
Validators::isNumeric(1.78);    // true
Validators::isNumeric('+42');   // true
Validators::isNumeric('3.14');  // true
Validators::isNumeric('nette'); // false
Validators::isNumeric('1e6');   // false (scientific notation not accepted)
```

### `isNumericInt(mixed $value): bool`

Checks if the value is an integer or an integer represented as a string.

```php
Validators::isNumericInt(23);      // true
Validators::isNumericInt(1.78);    // false
Validators::isNumericInt('+42');   // true
Validators::isNumericInt('3.14');  // false
Validators::isNumericInt('nette'); // false
```

### `isPhpIdentifier(string $value): bool`

Checks if the value is a syntactically valid identifier in PHP (e.g., for class names, method names, function names, etc.).

```php
Validators::isPhpIdentifier('');        // false
Validators::isPhpIdentifier('Hello1');  // true
Validators::isPhpIdentifier('1Hello');  // false
Validators::isPhpIdentifier('one two'); // false
```

### `isBuiltinType(string $type): bool`

Determines if `$type` is a PHP built-in type (e.g., `string`, `int`, `array`, `bool`). Otherwise, it's assumed to be a class name.

```php
Validators::isBuiltinType('string'); // true
Validators::isBuiltinType('Foo');    // false
```

### `isTypeDeclaration(string $type): bool`

Checks whether the given type declaration string is syntactically valid according to PHP's type declaration rules (including union, intersection, DNF types).

```php
Validators::isTypeDeclaration('?string');      // true
Validators::isTypeDeclaration('string|null');  // true
Validators::isTypeDeclaration('Foo&Bar');      // true
Validators::isTypeDeclaration('(A&C)|null');   // true

Validators::isTypeDeclaration('?string|null'); // false
Validators::isTypeDeclaration('|foo');         // false
Validators::isTypeDeclaration('(A|B)');        // false
```

### `isClassKeyword(string $name): bool`

Determines if `$name` is one of the internal type keywords `self`, `parent`, or `static`.

```php
Validators::isClassKeyword('self'); // true
Validators::isClassKeyword('Foo');  // false
```

### `isUnicode(mixed $value): bool`

Checks if the value is a valid UTF-8 string.

```php
Validators::isUnicode('nette'); // true
Validators::isUnicode('');      // true
Validators::isUnicode("\xA0");  // false (invalid UTF-8 sequence)
```

### `isUrl(string $value): bool`

Checks if the value is a valid absolute URL address according to RFC 3986.

```php
Validators::isUrl('https://nette.org:8080/path?query#fragment'); // true
Validators::isUrl('http://localhost');            // true
Validators::isUrl('http://192.168.1.1');          // true
Validators::isUrl('http://[::1]');                // true
Validators::isUrl('http://user:pass@nette.org');  // false (userinfo part not validated by this function)
Validators::isUrl('nette.org');                   // false (missing scheme)
```

### `isUri(string $value): bool`

Verifies that the value is a valid URI address, meaning it's a string starting with a syntactically valid scheme followed by a colon (e.g., `http:`, `https:`, `mailto:`, `ftp:`).

```php
Validators::isUri('https://nette.org');           // true
Validators::isUri('mailto:gandalf@example.org');  // true
Validators::isUri('nette.org');                   // false (missing scheme)
```

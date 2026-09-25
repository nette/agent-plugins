# String Functions

Contents:

- [Letter Case](#letter-case)
  - [`lower(string $s): string`](#lowerstring-s-string)
  - [`upper(string $s): string`](#upperstring-s-string)
  - [`firstUpper(string $s): string`](#firstupperstring-s-string)
  - [`firstLower(string $s): string`](#firstlowerstring-s-string)
  - [`capitalize(string $s): string`](#capitalizestring-s-string)
- [Editing a String](#editing-a-string)
  - [`normalize(string $s): string`](#normalizestring-s-string)
  - [`unixNewLines(string $s): string`](#unixnewlinesstring-s-string)
  - [`platformNewLines(string $s): string`](#platformnewlinesstring-s-string)
  - [`webalize(string $s, ?string $charlist=null, bool $lower=true): string`](#webalizestring-s-string-charlistnull-bool-lowertrue-string)
  - [`trim(string $s, string $charlist=self::TrimCharacters): string`](#trimstring-s-string-charlistselftrimcharacters-string)
  - [`truncate(string $s, int $maxLen, string $append='…'): string`](#truncatestring-s-int-maxlen-string-append-string)
  - [`indent(string $s, int $level=1, string $chars="\t"): string`](#indentstring-s-int-level1-string-charst-string)
  - [`padLeft(string $s, int $length, string $pad=' '): string`](#padleftstring-s-int-length-string-pad--string)
  - [`padRight(string $s, int $length, string $pad=' '): string`](#padrightstring-s-int-length-string-pad--string)
  - [`substring(string $s, int $start, ?int $length=null): string`](#substringstring-s-int-start-int-lengthnull-string)
  - [`reverse(string $s): string`](#reversestring-s-string)
  - [`length(string $s): int`](#lengthstring-s-int)
  - [`startsWith(string $haystack, string $needle): bool` (deprecated)](#startswithstring-haystack-string-needle-bool-deprecated)
  - [`endsWith(string $haystack, string $needle): bool` (deprecated)](#endswithstring-haystack-string-needle-bool-deprecated)
  - [`contains(string $haystack, string $needle): bool` (deprecated)](#containsstring-haystack-string-needle-bool-deprecated)
  - [`compare(string $left, string $right, ?int $length=null): bool`](#comparestring-left-string-right-int-lengthnull-bool)
  - [`findPrefix(array $strings): string`](#findprefixarray-strings-string)
  - [`before(string $haystack, string $needle, int $nth=1): ?string`](#beforestring-haystack-string-needle-int-nth1-string)
  - [`after(string $haystack, string $needle, int $nth=1): ?string`](#afterstring-haystack-string-needle-int-nth1-string)
  - [`indexOf(string $haystack, string $needle, int $nth=1): ?int`](#indexofstring-haystack-string-needle-int-nth1-int)
- [Encoding](#encoding)
  - [`fixEncoding(string $s): string`](#fixencodingstring-s-string)
  - [`checkEncoding(string $s): bool` (deprecated)](#checkencodingstring-s-bool-deprecated)
  - [`toAscii(string $s): string`](#toasciistring-s-string)
  - [`chr(int $code): string`](#chrint-code-string)
  - [`ord(string $c): int`](#ordstring-c-int)
- [Regular Expressions](#regular-expressions)
  - [`split(string $subject, string $pattern, bool $captureOffset=false, bool $skipEmpty=false, int $limit=-1, bool $utf8=false): array`](#splitstring-subject-string-pattern-bool-captureoffsetfalse-bool-skipemptyfalse-int-limit-1-bool-utf8false-array)
  - [`match(string $subject, string $pattern, bool $captureOffset=false, int $offset=0, bool $unmatchedAsNull=false, bool $utf8=false): ?array`](#matchstring-subject-string-pattern-bool-captureoffsetfalse-int-offset0-bool-unmatchedasnullfalse-bool-utf8false-array)
  - [`matchAll(string $subject, string $pattern, bool $captureOffset=false, int $offset=0, bool $unmatchedAsNull=false, bool $patternOrder=false, bool $utf8=false, bool $lazy=false): array|Generator`](#matchallstring-subject-string-pattern-bool-captureoffsetfalse-int-offset0-bool-unmatchedasnullfalse-bool-patternorderfalse-bool-utf8false-bool-lazyfalse-arraygenerator)
  - [`replace(string $subject, string|array $pattern, string|callable $replacement=, int $limit=-1, bool $captureOffset=false, bool $unmatchedAsNull=false, bool $utf8=false): string`](#replacestring-subject-stringarray-pattern-stringcallable-replacement-int-limit-1-bool-captureoffsetfalse-bool-unmatchedasnullfalse-bool-utf8false-string)


[Nette\Utils\Strings](https://api.nette.org/utils/master/Nette/Utils/Strings.html) is a static class containing useful functions for working with UTF-8 encoded strings.

Installation:

```shell
composer require nette/utils
```

All examples assume the following class alias is defined:

```php
use Nette\Utils\Strings;
```

## Letter Case

These functions require the PHP extension `mbstring`.

### `lower(string $s): string`

Converts a UTF-8 string to lowercase.

```php
Strings::lower('Hello World'); // 'hello world'
```

### `upper(string $s): string`

Converts a UTF-8 string to uppercase.

```php
Strings::upper('Hello World'); // 'HELLO WORLD'
```

### `firstUpper(string $s): string`

Converts the first character of a UTF-8 string to uppercase and leaves the other characters unchanged.

```php
Strings::firstUpper('hello world'); // 'Hello world'
```

### `firstLower(string $s): string`

Converts the first character of a UTF-8 string to lowercase and leaves the other characters unchanged.

```php
Strings::firstLower('Hello world'); // 'hello world'
```

### `capitalize(string $s): string`

Converts the first character of every word in a UTF-8 string to uppercase and the others to lowercase.

```php
Strings::capitalize('hello world'); // 'Hello World'
```

## Editing a String

### `normalize(string $s): string`

Removes control characters, normalizes line endings to `\n`, trims leading and trailing blank lines, trims trailing spaces on lines, and normalizes UTF-8 to the NFC normal form.

### `unixNewLines(string $s): string`

Converts line endings to `\n` used on Unix systems. Line endings are: `\n`, `\r`, `\r\n`, U+2028 line separator, U+2029 paragraph separator.

```php
$unixLikeLines = Strings::unixNewLines($string);
```

### `platformNewLines(string $s): string`

Converts line endings to characters specific to the current platform, i.e., `\r\n` on Windows and `\n` elsewhere. Line endings are: `\n`, `\r`, `\r\n`, U+2028 line separator, U+2029 paragraph separator.

```php
$platformLines = Strings::platformNewLines($string);
```

### `webalize(string $s, ?string $charlist=null, bool $lower=true): string`

Modifies a UTF-8 string to the form used in URLs, i.e., removes diacritics and replaces all characters except letters of the English alphabet and numbers with hyphens.

```php
Strings::webalize('žluťoučký kůň'); // 'zlutoucky-kun'
```

If other characters should be preserved, they can be specified in the second parameter.

```php
Strings::webalize('10. image_id', '._'); // '10.-image_id'
```

The third parameter can suppress conversion to lowercase.

```php
Strings::webalize('Dobrý den', null, false); // 'Dobry-den'
```

> [!CAUTION]
> Requires the PHP extension `intl`.

### `trim(string $s, string $charlist=self::TrimCharacters): string`

Strips leading and trailing whitespace (or other characters specified by the second parameter) from a UTF-8 string.

```php
Strings::trim('  Hello  '); // 'Hello'
```

### `truncate(string $s, int $maxLen, string $append='…'): string`

Truncates a UTF-8 string to the specified maximum length, trying to preserve whole words. If the string is truncated, an ellipsis (changeable via the third parameter) is added to the end.

```php
$text = 'Hello, how are you today?';
Strings::truncate($text, 5);       // 'Hell…'
Strings::truncate($text, 20);      // 'Hello, how are you…'
Strings::truncate($text, 30);      // 'Hello, how are you today?'
Strings::truncate($text, 20, '~'); // 'Hello, how are you~'
```

### `indent(string $s, int $level=1, string $chars="\t"): string`

Indents a multiline text from the left. The second parameter specifies the number of indentation characters, and the third parameter specifies the character(s) to use for indentation (default is tab).

```php
Strings::indent('Nette');         // "\tNette"
Strings::indent('Nette', 2, '+'); // '++Nette'
```

### `padLeft(string $s, int $length, string $pad=' '): string`

Pads a UTF-8 string to a specified length by prepending the `$pad` string from the left.

```php
Strings::padLeft('Nette', 6);        // ' Nette'
Strings::padLeft('Nette', 8, '+*');  // '+*+Nette'
```

### `padRight(string $s, int $length, string $pad=' '): string`

Pads a UTF-8 string to a specified length by appending the `$pad` string from the right.

```php
Strings::padRight('Nette', 6);       // 'Nette '
Strings::padRight('Nette', 8, '+*'); // 'Nette+*+'
```

### `substring(string $s, int $start, ?int $length=null): string`

Returns a portion of a UTF-8 string `$s` specified by the starting position `$start` and length `$length`. If `$start` is negative, the returned string will start at the `$start`-th character from the end.

```php
Strings::substring('Nette Framework', 0, 5); // 'Nette'
Strings::substring('Nette Framework', 6);    // 'Framework'
Strings::substring('Nette Framework', -4);   // 'work'
```

### `reverse(string $s): string`

Reverses a UTF-8 string.

```php
Strings::reverse('Nette'); // 'etteN'
```

### `length(string $s): int`

Returns the number of characters (not bytes) in a UTF-8 string.

This is the number of Unicode code points, which may differ from the number of graphemes.

```php
Strings::length('Nette');   // 5
Strings::length('červená'); // 7
```

### `startsWith(string $haystack, string $needle): bool` (deprecated)

Checks if the string `$haystack` starts with the string `$needle`.

```php
$haystack = 'Starts';
$needle = 'St';
Strings::startsWith($haystack, $needle); // true
```

Use the native `str_starts_with()` function.

### `endsWith(string $haystack, string $needle): bool` (deprecated)

Checks if the string `$haystack` ends with the string `$needle`.

```php
$haystack = 'Ends';
$needle = 'ds';
Strings::endsWith($haystack, $needle); // true
```

Use the native `str_ends_with()` function.

### `contains(string $haystack, string $needle): bool` (deprecated)

Checks if the string `$haystack` contains the string `$needle`.

```php
$haystack = 'Auditorium';
$needle = 'dit';
Strings::contains($haystack, $needle); // true
```

Use the native `str_contains()` function.

### `compare(string $left, string $right, ?int $length=null): bool`

Compares two UTF-8 strings or their parts, case-insensitively. If `$length` is null, compares the entire strings. If negative, compares the corresponding number of characters from the end of the strings. Otherwise, compares the corresponding number of characters from the beginning.

```php
Strings::compare('Nette', 'nette');     // true
Strings::compare('Nette', 'next', 2);   // true - first 2 characters match
Strings::compare('Nette', 'Latte', -2); // true - last 2 characters match
```

### `findPrefix(array $strings): string`

Finds the common prefix of the strings. Returns an empty string if no common prefix is found.

```php
Strings::findPrefix(['prefix-a', 'prefix-bb', 'prefix-c']); // 'prefix-'
Strings::findPrefix(['Nette', 'is', 'great']);              // ''
```

### `before(string $haystack, string $needle, int $nth=1): ?string`

Returns the portion of string `$haystack` before the `$nth` occurrence of string `$needle`. Returns `null` if `$needle` is not found. If `$nth` is negative, it searches from the end of the string.

```php
Strings::before('Nette_is_great', '_', 1);  // 'Nette'
Strings::before('Nette_is_great', '_', -2); // 'Nette'
Strings::before('Nette_is_great', ' ');     // null
Strings::before('Nette_is_great', '_', 3);  // null
```

### `after(string $haystack, string $needle, int $nth=1): ?string`

Returns the portion of string `$haystack` after the `$nth` occurrence of string `$needle`. Returns `null` if `$needle` is not found. If `$nth` is negative, it searches from the end of the string.

```php
Strings::after('Nette_is_great', '_', 2);  // 'great'
Strings::after('Nette_is_great', '_', -1); // 'great'
Strings::after('Nette_is_great', ' ');     // null
Strings::after('Nette_is_great', '_', 3);  // null
```

### `indexOf(string $haystack, string $needle, int $nth=1): ?int`

Returns the character position of the `$nth` occurrence of string `$needle` in string `$haystack`. Returns `null` if `$needle` is not found. If `$nth` is negative, it searches from the end of the string.

```php
Strings::indexOf('abc abc abc', 'abc', 2);  // 4
Strings::indexOf('abc abc abc', 'abc', -1); // 8
Strings::indexOf('abc abc abc', 'd');       // null
```

## Encoding

### `fixEncoding(string $s): string`

Removes invalid UTF-8 characters from a string.

```php
$correctString = Strings::fixEncoding($invalidString);
```

### `checkEncoding(string $s): bool` (deprecated)

Checks if a string is a valid UTF-8 string.

```php
$isUtf8 = Strings::checkEncoding($string);
```

Use [Nette\Utils\Validators::isUnicode()](validators.md#isunicodemixed-value-bool).

### `toAscii(string $s): string`

Converts a UTF-8 string to ASCII, i.e., removes diacritics, etc.

```php
Strings::toAscii('žluťoučký kůň'); // 'zlutoucky kun'
```

> [!CAUTION]
> Requires the PHP extension `intl`.

### `chr(int $code): string`

Returns a specific character in UTF-8 from a code point (a number in the range 0×0000..D7FF or 0xE000..10FFFF).

```php
Strings::chr(0xA9); // '©' in UTF-8 encoding
```

### `ord(string $c): int`

Returns the code point of a specific character in UTF-8 (a number in the range 0×0000..D7FF or 0xE000..10FFFF).

```php
Strings::ord('©'); // 169 (0xA9)
```

## Regular Expressions

The `Strings` class offers functions for working with regular expressions. Unlike native PHP functions, they feature a more understandable API, better Unicode support, and, crucially, error detection. Any error during compilation or expression processing throws a `Nette\RegexpException`.

### `split(string $subject, string $pattern, bool $captureOffset=false, bool $skipEmpty=false, int $limit=-1, bool $utf8=false): array`

Splits a string into an array using a regular expression. Expressions in parentheses will also be captured and returned.

```php
Strings::split('hello, world', '~,\s*~');
// ['hello', 'world']

Strings::split('hello, world', '~(,)\s*~');
// ['hello', ',', 'world']
```

If `$skipEmpty` is `true`, only non-empty items will be returned:

```php
Strings::split('hello, world, ', '~,\s*~');
// ['hello', 'world', '']

Strings::split('hello, world, ', '~,\s*~', skipEmpty: true);
// ['hello', 'world']
```

If `$limit` is specified, only substrings up to the limit are returned, and the rest of the string is placed in the last element. A limit of –1 or 0 means no limit.

```php
Strings::split('hello, world, third', '~,\s*~', limit: 2);
// ['hello', 'world, third']
```

If `$utf8` is `true`, evaluation switches to Unicode mode, similar to using the `u` modifier.

If `$captureOffset` is `true`, the position of each match in the string will also be returned (in bytes; in characters if `$utf8` is set). This changes the return value to an array where each element is a pair consisting of the matched string and its position.

```php
Strings::split('žlutý, kůň', '~,\s*~', captureOffset: true);
// [['žlutý', 0], ['kůň', 9]]

Strings::split('žlutý, kůň', '~,\s*~', captureOffset: true, utf8: true);
// [['žlutý', 0], ['kůň', 7]] // positions are in characters
```

### `match(string $subject, string $pattern, bool $captureOffset=false, int $offset=0, bool $unmatchedAsNull=false, bool $utf8=false): ?array`

Searches a string for a part matching a regular expression and returns an array containing the found expression and individual subexpressions, or `null` if no match is found.

```php
Strings::match('hello!', '~\w+(!+)~');
// ['hello!', '!']

Strings::match('hello!', '~X~');
// null
```

If `$unmatchedAsNull` is `true`, unmatched subpatterns are returned as `null`; otherwise, they are returned as an empty string or omitted entirely:

```php
Strings::match('hello', '~\w+(!+)?~');
// ['hello'] (the optional group !+ did not match)

Strings::match('hello', '~\w+(!+)?~', unmatchedAsNull: true);
// ['hello', null]
```

If `$utf8` is `true`, evaluation switches to Unicode mode, similar to using the `u` modifier:

```php
Strings::match('žlutý kůň', '~\w+~'); // Without UTF-8
// ['lut'] (matches only ASCII word characters)

Strings::match('žlutý kůň', '~\w+~', utf8: true); // With UTF-8
// ['žlutý'] (matches Unicode word characters)
```

The `$offset` parameter can specify the starting position for the search (in bytes; in characters if `$utf8` is set).

If `$captureOffset` is `true`, the position of each match in the string will also be returned (in bytes; in characters if `$utf8` is set). This changes the return value to an array where each element is a pair consisting of the matched string and its offset:

```php
Strings::match('žlutý!', '~\w+(!+)?~', captureOffset: true); // Without UTF-8
// [['lut', 2]] (only ASCII match, offset in bytes)

Strings::match('žlutý!', '~\w+(!+)?~', captureOffset: true, utf8: true); // With UTF-8
// [['žlutý!', 0], ['!', 5]] (Unicode match, offsets in characters)
```

### `matchAll(string $subject, string $pattern, bool $captureOffset=false, int $offset=0, bool $unmatchedAsNull=false, bool $patternOrder=false, bool $utf8=false, bool $lazy=false): array|Generator`

Searches a string for all occurrences matching a regular expression and returns an array of arrays containing the found expression and individual subexpressions.

```php
Strings::matchAll('hello, world!!', '~\w+(!+)?~');
/* [
	0 => ['hello'],
	1 => ['world!!', '!!'],
] */
```

If `$patternOrder` is `true`, the structure of the results changes: the first element is an array of full pattern matches, the second is an array of strings matching the first parenthesized subpattern, and so on:

```php
Strings::matchAll('hello, world!!', '~\w+(!+)?~', patternOrder: true);
/* [
	0 => ['hello', 'world!!'],
	1 => ['', '!!'],
] */
```

If `$unmatchedAsNull` is `true`, unmatched subpatterns are returned as `null`; otherwise, they are returned as an empty string or omitted:

```php
Strings::matchAll('hello, world!!', '~\w+(!+)?~', unmatchedAsNull: true);
/* [
	0 => ['hello', null],
	1 => ['world!!', '!!'],
] */
```

If `$utf8` is `true`, evaluation switches to Unicode mode, similar to using the `u` modifier:

```php
Strings::matchAll('žlutý kůň', '~\w+~');
/* [
	0 => ['lut'],
	1 => ['k'],
] */

Strings::matchAll('žlutý kůň', '~\w+~', utf8: true);
/* [
	0 => ['žlutý'],
	1 => ['kůň'],
] */
```

The `$offset` parameter can specify the starting position for the search (in bytes; in characters if `$utf8` is set).

If `$captureOffset` is `true`, the position of each match in the string will also be returned (in bytes; in characters if `$utf8` is set). This changes the return value structure, where each match element is a pair `[matched_string, position]`:

```php
Strings::matchAll('žlutý kůň', '~\w+~', captureOffset: true);
/* [
	0 => [['lut', 2]],
	1 => [['k', 8]],
] */

Strings::matchAll('žlutý kůň', '~\w+~', captureOffset: true, utf8: true);
/* [
	0 => [['žlutý', 0]],
	1 => [['kůň', 6]],
] */
```

If `$lazy` is `true`, the function returns a `Generator` instead of an array. This offers significant performance benefits when working with large strings, as matches are found incrementally rather than processing the entire string at once. This allows efficient handling of extremely large inputs. Additionally, you can interrupt processing at any time if you find the desired match, saving computation time.

```php
$matches = Strings::matchAll($largeText, '~\w+~', lazy: true);
foreach ($matches as $match) {
    echo "Found: $match[0]\n";
    // Processing can be interrupted at any time, e.g., with break;
}
```

### `replace(string $subject, string|array $pattern, string|callable $replacement=, int $limit=-1, bool $captureOffset=false, bool $unmatchedAsNull=false, bool $utf8=false): string`

Replaces all occurrences matching a regular expression. `$replacement` is either a replacement string mask or a callback function.

```php
Strings::replace('hello, world!', '~\w+~', '--');
// '--, --!'

Strings::replace('hello, world!', '~\w+~', fn($m) => strrev($m[0]));
// 'olleh, dlrow!'
```

The function also allows multiple replacements by passing an array in the format `pattern => replacement` as the second parameter:

```php
Strings::replace('hello, world!', [
	'~\w+~' => '--',
	'~,\s+~' => ' ',
]);
// '-- --!'
```

The `$limit` parameter restricts the number of replacements performed. A limit of –1 means no limit.

If `$utf8` is `true`, evaluation switches to Unicode mode, similar to using the `u` modifier.

```php
Strings::replace('žlutý kůň', '~\w+~', '--');
// 'ž--ý --ůň'

Strings::replace('žlutý kůň', '~\w+~', '--', utf8: true);
// '-- --'
```

If `$captureOffset` is `true`, the position of each match in the string (in bytes; in characters if `$utf8` is set) is also passed to the callback. This changes the structure of the passed array, where each element is a pair `[matched_string, position]`.

```php
Strings::replace(
	'žlutý kůň',
	'~\w+~',
	function (array $m) { dump($m); return ''; },
	captureOffset: true,
);
// dumps [['lut', 2]] and [['k', 8]]

Strings::replace(
	'žlutý kůň',
	'~\w+~',
	function (array $m) { dump($m); return ''; },
	captureOffset: true,
	utf8: true,
);
// dumps [['žlutý', 0]] and [['kůň', 6]]
```

If `$unmatchedAsNull` is `true`, unmatched subpatterns are passed to the callback as `null`; otherwise, they are passed as an empty string or omitted:

```php
Strings::replace(
	'ac',
	'~(a)(b)*(c)~',
	function (array $m) { dump($m); return ''; },
);
// dumps ['ac', 'a', '', 'c']

Strings::replace(
	'ac',
	'~(a)(b)*(c)~',
	function (array $m) { dump($m); return ''; },
	unmatchedAsNull: true,
);
// dumps ['ac', 'a', null, 'c']
```

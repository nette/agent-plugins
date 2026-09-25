# Latte Functions

Contents:

- [Usage](#usage)
- [Functions](#functions)
  - [`clamp(int|float $value, int|float $min, int|float $max): int|float`](#clampintfloat-value-intfloat-min-intfloat-max-intfloat)
  - [`divisibleBy(int $value, int $by): bool`](#divisiblebyint-value-int-by-bool)
  - [`even(int $value): bool`](#evenint-value-bool)
  - [`first(string|iterable $value): mixed`](#firststringiterable-value-mixed)
  - [`group(iterable $data, string|int|\Closure $by): iterable` (since 3.0.16)](#groupiterable-data-stringintclosure-by-iterable-since-3016)
  - [`hasBlock(string $name): bool` (since 3.0.10)](#hasblockstring-name-bool-since-3010)
  - [`hasTemplate(string $name): bool` (since 3.0.22)](#hastemplatestring-name-bool-since-3022)
  - [`last(string|array $value): mixed`](#laststringarray-value-mixed)
  - [`odd(int $value): bool`](#oddint-value-bool)
  - [`slice(string|iterable $value, int $start, ?int $length=null, bool $preserveKeys=false): string|array|\Generator`](#slicestringiterable-value-int-start-int-lengthnull-bool-preservekeysfalse-stringarraygenerator)


In addition to common PHP functions, you can also use these functions in templates.

|  |  |
| --- | --- |
| `clamp` | [clamps a value to the given range](#clampintfloat-value-intfloat-min-intfloat-max-intfloat) |
| `divisibleBy` | [checks if a variable is divisible by a number](#divisiblebyint-value-int-by-bool) |
| `even` | [checks if the given number is even](#evenint-value-bool) |
| `first` | [returns the first element of an array or character of a string](#firststringiterable-value-mixed) |
| `group` | [groups data according to various criteria](#groupiterable-data-stringintclosure-by-iterable-since-3016) |
| `hasBlock` | [detects the existence of a block](#hasblockstring-name-bool-since-3010) |
| `hasTemplate` | [detects the existence of a template](#hastemplatestring-name-bool-since-3022) |
| `last` | [returns the last element of an array or character of a string](#laststringarray-value-mixed) |
| `odd` | [checks if the given number is odd](#oddint-value-bool) |
| `slice` | [extracts a slice of an array or a string](#slicestringiterable-value-int-start-int-lengthnull-bool-preservekeysfalse-stringarraygenerator) |

## Usage

Functions are used in the same way as common PHP functions and can be used in all expressions:

```latte
<p>{clamp($num, 1, 100)}</p>

{if odd($num)} ... {/if}
```

[Custom functions](custom-functions.md) can be registered this way:

```php
$latte = new Latte\Engine;
$latte->addFunction('shortify', fn(string $s, int $len = 10) => mb_substr($s, 0, $len));
```

It is then called in the template like this:

```latte
<p>{shortify($text)}</p>
<p>{shortify($text, 100)}</p>
```

## Functions

### `clamp(int|float $value, int|float $min, int|float $max): int|float`

Clamps a value to the given inclusive range of min and max.

```latte
{=clamp($level, 0, 255)}
```

See also the [clamp filter](filters.md#clampintfloat-min-intfloat-max).

### `divisibleBy(int $value, int $by): bool`

Checks if a variable is divisible by a number.

```latte
{if divisibleBy($num, 5)} ... {/if}
```

### `even(int $value): bool`

Checks if the given number is even.

```latte
{if even($num)} ... {/if}
```

### `first(string|iterable $value): mixed`

Returns the first element of an array or the first character of a string:

```latte
{=first([1, 2, 3, 4])}    {* outputs 1 *}
{=first('abcd')}          {* outputs 'a' *}
```

See also [last](#laststringarray-value-mixed), [first filter](filters.md#first).

### `group(iterable $data, string|int|\Closure $by): iterable` (since 3.0.16)

This function groups data according to different criteria.

In this example, rows in the table are grouped by the `categoryId` column. The output is an iterable structure of groups, where the key is the value in the `categoryId` column. [Read the detailed guide](cookbook/grouping.md).

```latte
{foreach group($items, categoryId) as $categoryId => $categoryItems}
    <ul>
        {foreach $categoryItems as $item}
            <li>{$item->name}</li>
        {/foreach}
    </ul>
{/foreach}
```

See also the [group filter](filters.md#groupstringintclosure-by-iterable-since-3016).

### `hasBlock(string $name): bool` (since 3.0.10)

Checks if a block of the specified name exists:

```latte
{if hasBlock(header)} ... {/if}
```

See also [block existence check](template-inheritance.md#checking-block-existence-ifset).

### `hasTemplate(string $name): bool` (since 3.0.22)

Determines whether a template of the specified name exists:

```latte
{if hasTemplate('foo.latte')} ... {/if}
```

### `last(string|array $value): mixed`

Returns the last element of an array or the last character of a string:

```latte
{=last([1, 2, 3, 4])}    {* outputs 4 *}
{=last('abcd')}          {* outputs 'd' *}
```

See also [first](#firststringiterable-value-mixed), [last filter](filters.md#last).

### `odd(int $value): bool`

Checks if the given number is odd.

```latte
{if odd($num)} ... {/if}
```

### `slice(string|iterable $value, int $start, ?int $length=null, bool $preserveKeys=false): string|array|\Generator`

Extracts a slice of an array, a string, or an iterator.

```latte
{=slice('hello', 1, 2)}           {* outputs 'el' *}
{=slice(['a', 'b', 'c'], 1, 2)}   {* outputs ['b', 'c'] *}
```

The function works like the PHP function `array_slice` for arrays or `mb_substr` for strings, with a fallback to the `iconv_substr` function in UTF‑8 mode. If the input is a general iterator, it is converted into a generator that yields the sliced elements.

If `start` is non-negative, the sequence will start at that offset from the beginning of the array/string. If `start` is negative, the sequence will start that far from the end.

If `length` is given and is positive, then the sequence will have up to that many elements. If the input is shorter than the `length`, then only the available elements will be present. If `length` is given and is negative, the sequence will stop that many elements from the end of the input. If it is omitted, the sequence will have everything from `start` up until the end of the input.

By default, the function reorders and resets the integer array keys. This behavior can be changed by setting `preserveKeys` to true. String keys are always preserved, regardless of this parameter.

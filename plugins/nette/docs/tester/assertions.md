# Assertions

Contents:

- [`Assert::same($expected, $actual, ?string $description=null)`](#assertsameexpected-actual-string-descriptionnull)
- [`Assert::notSame($expected, $actual, ?string $description=null)`](#assertnotsameexpected-actual-string-descriptionnull)
- [`Assert::equal($expected, $actual, ?string $description=null, bool $matchOrder=false, bool $matchIdentity=false)`](#assertequalexpected-actual-string-descriptionnull-bool-matchorderfalse-bool-matchidentityfalse)
- [`Assert::notEqual($expected, $actual, ?string $description=null)`](#assertnotequalexpected-actual-string-descriptionnull)
- [`Assert::contains($needle, string|array $actual, ?string $description=null)`](#assertcontainsneedle-stringarray-actual-string-descriptionnull)
- [`Assert::notContains($needle, string|array $actual, ?string $description=null)`](#assertnotcontainsneedle-stringarray-actual-string-descriptionnull)
- [`Assert::hasKey(string|int $needle, array $actual, ?string $description=null)` (since 2.4.0)](#asserthaskeystringint-needle-array-actual-string-descriptionnull-since-240)
- [`Assert::hasNotKey(string|int $needle, array $actual, ?string $description=null)` (since 2.4.0)](#asserthasnotkeystringint-needle-array-actual-string-descriptionnull-since-240)
- [`Assert::true($value, ?string $description=null)`](#asserttruevalue-string-descriptionnull)
- [`Assert::truthy($value, ?string $description=null)`](#asserttruthyvalue-string-descriptionnull)
- [`Assert::false($value, ?string $description=null)`](#assertfalsevalue-string-descriptionnull)
- [`Assert::falsey($value, ?string $description=null)`](#assertfalseyvalue-string-descriptionnull)
- [`Assert::null($value, ?string $description=null)`](#assertnullvalue-string-descriptionnull)
- [`Assert::notNull($value, ?string $description=null)`](#assertnotnullvalue-string-descriptionnull)
- [`Assert::nan($value, ?string $description=null)`](#assertnanvalue-string-descriptionnull)
- [`Assert::count($count, Countable|array $value, ?string $description=null)`](#assertcountcount-countablearray-value-string-descriptionnull)
- [`Assert::type(string|object $type, $value, ?string $description=null)`](#asserttypestringobject-type-value-string-descriptionnull)
- [`Assert::exception(callable $callable, string $class, ?string $message=null, $code=null)`](#assertexceptioncallable-callable-string-class-string-messagenull-codenull)
- [`Assert::error(string $callable, int|string|array $type, ?string $message=null)`](#asserterrorstring-callable-intstringarray-type-string-messagenull)
- [`Assert::noError(callable $callable)`](#assertnoerrorcallable-callable)
- [`Assert::match(string $pattern, $actual, ?string $description=null)`](#assertmatchstring-pattern-actual-string-descriptionnull)
- [`Assert::notMatch(string $pattern, $actual, ?string $description=null)` (since 2.5.6)](#assertnotmatchstring-pattern-actual-string-descriptionnull-since-256)
- [`Assert::matchFile(string $file, $actual, ?string $description=null)`](#assertmatchfilestring-file-actual-string-descriptionnull)
- [`Assert::fail(string $message, $actual=null, $expected=null)`](#assertfailstring-message-actualnull-expectednull)
- [Expectations](#expectations)
- [Exploring Failed Assertions](#exploring-failed-assertions)


Assertions are used to confirm that the actual value matches the expected value. They are methods of the `Tester\Assert` class.

Choose the most suitable assertions. `Assert::same($a, $b)` is better than `Assert::true($a === $b)`, because it displays a meaningful error message upon failure. In the second case, we only get `false should be true` which tells us nothing about the contents of the variables `$a` and `$b`.

Most assertions can also have an optional description in the `$description` parameter, which is displayed in the error message if the expectation fails.

Examples assume the following alias is created:

```php
use Tester\Assert;
```

## `Assert::same($expected, $actual, ?string $description=null)`

`$expected` must be identical to `$actual`. It's the same as the PHP operator `===`.

## `Assert::notSame($expected, $actual, ?string $description=null)`

Opposite of `Assert::same()`, meaning it's the same as the PHP operator `!==`.

## `Assert::equal($expected, $actual, ?string $description=null, bool $matchOrder=false, bool $matchIdentity=false)`

`$expected` must be equal to `$actual`. Unlike `Assert::same()`, object identity, the order of key ⇒ value pairs in arrays, and marginally different decimal numbers are ignored, which can be changed by setting `$matchIdentity` and `$matchOrder`.

The following cases are equal from the perspective of `equal()`, but not `same()`:

```php
Assert::equal(0.3, 0.1 + 0.2);
Assert::equal($obj, clone $obj);
Assert::equal(
	['first' => 11, 'second' => 22],
	['second' => 22, 'first' => 11],
);
```

But beware, the arrays `[1, 2]` and `[2, 1]` are not the same, because only the order of values differs, not the key ⇒ value pairs. The array `[1, 2]` can also be written as `[0 => 1, 1 => 2]`, and `[1 => 2, 0 => 1]` will therefore be considered the same.

You can also use so-called [Expectations](#expectations) in `$expected`.

## `Assert::notEqual($expected, $actual, ?string $description=null)`

Opposite of `Assert::equal()`.

## `Assert::contains($needle, string|array $actual, ?string $description=null)`

If `$actual` is a string, it must contain the substring `$needle`. If it is an array, it must contain the element `$needle` (compared strictly).

## `Assert::notContains($needle, string|array $actual, ?string $description=null)`

Opposite of `Assert::contains()`.

## `Assert::hasKey(string|int $needle, array $actual, ?string $description=null)` (since 2.4.0)

`$actual` must be an array and must contain the key `$needle`.

## `Assert::hasNotKey(string|int $needle, array $actual, ?string $description=null)` (since 2.4.0)

`$actual` must be an array and must not contain the key `$needle`.

## `Assert::true($value, ?string $description=null)`

`$value` must be `true`, i.e., `$value === true`.

## `Assert::truthy($value, ?string $description=null)`

`$value` must be truthy, i.e., it satisfies the condition `if ($value) ...`.

## `Assert::false($value, ?string $description=null)`

`$value` must be `false`, i.e., `$value === false`.

## `Assert::falsey($value, ?string $description=null)`

`$value` must be falsey, i.e., it satisfies the condition `if (!$value) ...`.

## `Assert::null($value, ?string $description=null)`

`$value` must be `null`, i.e., `$value === null`.

## `Assert::notNull($value, ?string $description=null)`

`$value` must not be `null`, i.e., `$value !== null`.

## `Assert::nan($value, ?string $description=null)`

`$value` must be Not a Number. For testing NAN values, use exclusively `Assert::nan()`. The NAN value is very specific, and assertions like `Assert::same()` or `Assert::equal()` may behave unexpectedly.

## `Assert::count($count, Countable|array $value, ?string $description=null)`

The number of elements in `$value` must be `$count`. It's the same as `count($value) === $count`.

## `Assert::type(string|object $type, $value, ?string $description=null)`

`$value` must be of the given type. As `$type` we can use a string:

- `array`
- `list` – array indexed according to an ascending series of numeric keys from zero
- `bool`
- `callable`
- `float`
- `int`
- `null`
- `object`
- `resource`
- `scalar`
- `string`
- class name or directly an object, then `$value instanceof $type` must hold

## `Assert::exception(callable $callable, string $class, ?string $message=null, $code=null)`

When calling `$callable`, an exception of class `$class` must be thrown. If we specify `$message`, the exception message must also [match the pattern](#assertmatchstring-pattern-actual-string-descriptionnull). And if we specify `$code`, the codes must also strictly match.

For example, the following test will fail because the exception message does not match:

```php
Assert::exception(
	fn() => throw new App\InvalidValueException('Zero value'),
	App\InvalidValueException::class,
	'Value is too low',
);
```

`Assert::exception()` returns the thrown exception, allowing you to test a nested exception as well.

```php
$e = Assert::exception(
	fn() => throw new MyException('Something is wrong', 0, new RuntimeException),
	MyException::class,
	'Something is wrong',
);

Assert::type(RuntimeException::class, $e->getPrevious());
```

## `Assert::error(string $callable, int|string|array $type, ?string $message=null)`

Checks that the function `$callable` generated the expected errors (i.e., warnings, notices, etc.). As `$type` specify one of the `E_...` constants, for example `E_WARNING`. And if we specify `$message`, the error message must also [match the pattern](#assertmatchstring-pattern-actual-string-descriptionnull). For example:

```php
Assert::error(
	fn() => $i++,
	E_NOTICE,
	'Undefined variable: i',
);
```

If the callback generates more errors, we must expect them all in the exact order. In this case, pass an array in `$type`:

```php
Assert::error(function () {
	$a++;
	$b++;
}, [
	[E_NOTICE, 'Undefined variable: a'],
	[E_NOTICE, 'Undefined variable: b'],
]);
```

If you specify the class name as `$type`, it behaves the same as `Assert::exception()`.

## `Assert::noError(callable $callable)`

Checks that the function `$callable` did not generate any warning, error or exception. It is useful for testing pieces of code where there is no other assertion.

## `Assert::match(string $pattern, $actual, ?string $description=null)`

`$actual` must match the pattern `$pattern`. We can use two variants of patterns: regular expressions or wildcards.

If we pass a regular expression as `$pattern`, we must use `~` or `#` to delimit it. Other delimiters are not supported. For example, a test where `$var` must contain only hexadecimal digits:

```php
Assert::match('#^[0-9a-f]+$#i', $var);
```

The second variant is similar to comparing ordinary strings, but we can use various wildcards in `$pattern`:

- `%a%` one or more of anything except line ending characters
- `%a?%` zero or more of anything except line ending characters
- `%A%` one or more of anything including line ending characters
- `%A?%` zero or more of anything including line ending characters
- `%s%` one or more whitespace characters except line ending characters
- `%s?%` zero or more whitespace characters except line ending characters
- `%S%` one or more characters except whitespace characters
- `%S?%` zero or more characters except whitespace characters
- `%c%` a single character of any sort (except line ending)
- `%d%` one or more digits
- `%d?%` zero or more digits
- `%i%` signed integer value
- `%f%` floating-point number
- `%h%` one or more hexadecimal digits
- `%w%` one or more alphanumeric characters
- `%ds%` directory separator (`/` or `\`)
- `%%` one % character

Examples:

```php
# Again, test for a hexadecimal number
Assert::match('%h%', $var);

# Generalization of file path and line number
Assert::match('Error in file %a% on line %i%', $errorMessage);
```

## `Assert::notMatch(string $pattern, $actual, ?string $description=null)` (since 2.5.6)

Opposite of `Assert::match()`.

## `Assert::matchFile(string $file, $actual, ?string $description=null)`

This assertion is identical to [Assert::match()](#assertmatchstring-pattern-actual-string-descriptionnull), but the pattern is loaded from the file `$file`. This is useful for testing very long strings. The test file remains clear.

## `Assert::fail(string $message, $actual=null, $expected=null)`

This assertion always fails. Sometimes it's just useful. Optionally, we can specify the expected and actual value.

## Expectations

When we want to compare more complex structures with non-constant elements, the assertions mentioned above may not be sufficient. For example, we are testing a method that creates a new user and returns its attributes as an array. We do not know the value of the password hash, but we know that it must be a hexadecimal string. And about the next element, we only know that it must be a `DateTime` object.

In these situations, we can use `Tester\Expect` inside the `$expected` parameter of the `Assert::equal()` and `Assert::notEqual()` methods, using which the structure can be easily described.

```php
use Tester\Expect;

Assert::equal([
	'id' => Expect::type('int'),                   # we expect an integer
	'username' => 'milo',
	'password' => Expect::match('%h%'),            # we expect a string matching the pattern
	'created_at' => Expect::type(DateTime::class), # we expect an instance of the class
], User::create(123, 'milo', 'RandomPaSsWoRd'));
```

With `Expect`, we can perform almost the same assertions as with `Assert`. Thus, methods `Expect::same()`, `Expect::match()`, `Expect::count()`, etc. are available to us. In addition, we can chain them:

```php
Expect::type(MyIterator::class)->andCount(5);  # we expect MyIterator and the number of elements is 5
```

Alternatively, we can write our own assertion handlers.

```php
Expect::that(function ($value) {
	# return false if the expectation fails
});
```

## Exploring Failed Assertions

When an assertion fails, Tester prints what the error is. If we compare complex structures, Tester creates dumps of the compared values and saves them to the `output` directory. For example, if the fictional test `Arrays.recursive.phpt` fails, the dumps will be stored as follows:

```
app/
└── tests/
	├── output/
	│   ├── Arrays.recursive.actual    # actual value
	│   └── Arrays.recursive.expected  # expected value
	│
	└── Arrays.recursive.phpt          # failing test
```

We can change the directory name via `Tester\Dumper::$dumpDir`.

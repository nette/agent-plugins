---
name: nette-tester
description: Use this skill whenever writing, modifying, or running .phpt test files with Nette Tester. Invoke for any task involving Tester\Assert methods (Assert::same, Assert::match, Assert::exception, etc.), test bootstrap setup, vendor/bin/tester commands, or debugging failing test output (.expected/.actual files). Also use when the user needs to write tests for a Nette project and asks about test structure, the test() function, testException(), or assertion methods. This skill is specifically for Nette Tester – do not use for PHPUnit, Pest, Jest, Vitest, or other testing frameworks, and do not use for general PHP testing without Nette context.
---

## Testing with Nette Tester

Nette Tester is a testing framework for PHP. Test files use the `.phpt` extension by convention;
the runner also collects `*Test.php`.

Two failure modes worth knowing before writing a single test:

- **A test that executes no assertion is an error** – `Error: This test forgets to execute an
  assertion.` (exit code 178). It is on by default via `Environment::setup()`.
- **`exit()` / `die()` in a test ends it with code 0, i.e. as a PASS.** Use `Assert::fail()` to
  fail deliberately.

```shell
composer require nette/tester --dev
```

### Bootstrap File

The bootstrap file should set up the Tester environment and enable helper functions:

```php
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();  // enables test(), testException(), testNoError(), setUp()
```

### Basic Test Structure

```php
<?php declare(strict_types=1);

use Tester\Assert;
require __DIR__ . '/../bootstrap.php';


test('Calculator adds numbers correctly', function () {
	$calc = new App\Model\Calculator;
	Assert::same(5, $calc->add(2, 3));
});


test('Calculator throws on division by zero', function () {
	$calc = new App\Model\Calculator;
	Assert::exception(
		fn() => $calc->divide(10, 0),
		\DivisionByZeroError::class,
	);
});
```

Key points:
- Use the `test()` function for each test case
- The first parameter of `test()` should be a clear description of what is being tested
- Do not add comments before `test()` calls - the description parameter serves this purpose
- Group related tests in the same file
- Test file naming: `{ClassName}.phpt` or `{ClassName}.{feature}.phpt`

### Test Annotations

In the **first docblock** of the test file, before `require`. Case-insensitive; no effect when the file is run as plain `php test.phpt`. The first non-`@` line is the test title (written `TEST: ...`).

| Annotation | Syntax | Notes |
|---|---|---|
| `@skip` | – | always skipped |
| `@phpVersion` | `[op] version` | op: `<= < == = != <> >= >`, default `>=` |
| `@phpExtension` | `pdo, pdo_mysql` | comma/space separated; repeatable |
| `@dataProvider` | `file.ini[, query]` | path relative to the test file; a `.php` file returning array/Traversable works too |
| `@dataProvider?` | `? file.ini` | leading `?` = skip (not fail) when the file is missing |
| `@multiple` | `N` | runs the file exactly N times |
| `@testCase` | – | file holds a `Tester\TestCase`; runner runs each method in its own process |
| `@exitCode` | `N` | default 0 |
| `@outputMatch` / `@outputMatchFile` | pattern / `file` | `Assert::match` / `matchFile` against stdout |
| `@phpIni` | `key=value` | same as the runner's `-d key=value`; repeatable |

**Trap — `@phpVersion` with an exact version.** The skip condition is `version_compare(annotation, actualPhpVersion, op)`, so equality skips. `@phpVersion 8.4.3` on PHP 8.4.3 is **skipped**; write two components (`@phpVersion 8.4`), which sorts below `8.4.0` and therefore runs.

In the `@dataProvider` file form the test loads its own data set: `$args = Tester\Environment::loadData();` — called once per run, returning one INI section. INI is parsed with `INI_SCANNER_TYPED` (values arrive typed). The optional query filters sections: its tokens are matched left to right against the whitespace-separated parts of the section name, operators `<= =< < == = != <> >= => >` (bare token = `=`), numeric tokens compared by `version_compare`. So `@dataProvider databases.ini postgresql, >=9.0` selects `[postgresql 9.1]`.

### `Tester\TestCase`

```php
class RectangleTest extends Tester\TestCase
{
	protected function setUp() {}      // before each test method
	protected function tearDown() {}   // after each test method
	public function testArea() { Assert::same(6, (new Rect(2, 3))->area()); }
}

(new RectangleTest)->run();   // MANDATORY, last line of the file
```

- Discovery pattern is `#^test[A-Z0-9_]#`: `testArea`, `test_area`, `test2` are tests — **`testarea` silently is not**.
- A `test*` method must be `public`; a protected one is discovered, then fails with `Method X is not public. Make it public or rename it.`
- **Forgetting `->run()`**: with `@testCase` the runner reports `Cannot list TestCase methods in file '…'. Do you call TestCase::run() in it?`; without it the file merely defines a class and dies as `This test forgets to execute an assertion.` (exit 178).
- Order per method: `setUp()`, test, `tearDown()`. If the test throws, `tearDown()` still runs but errors inside it are suppressed. A failure in `setUp()`/`tearDown()` fails the test.
- In v2.6.1 `run()` aborts at the **first** failing method; `@testCase` sidesteps that by isolating each method.
- `$this->skip('reason')` skips the current method from anywhere inside it.

```php
/** @throws RuntimeException  Wrong argument order */   // class + optional message pattern; once per method
/** @dataProvider getArgs */      // value without a dot -> method of this class
/** @dataProvider args.ini x>1 */ // value with a dot -> ini/php file relative to the test file + optional query
public function testLoop($a, $b) {}
```

The provider returns an array/`Traversable` of arrays. String-keyed sets are merged over the method's parameter defaults; list-keyed sets are passed positionally. A method with required parameters and no `@dataProvider` throws `TestCaseException`.

### Assertions Overview

- `Assert::same($expected, $actual)` - strict identity (`===`)
- `Assert::notSame($expected, $actual)` - not strictly equal
- `Assert::equal($expected, $actual)` - loose comparison (ignores object identity, array key order, float epsilon)
- `Assert::notEqual($expected, $actual)`
- `Assert::true($actual)`, `Assert::false($actual)`, `Assert::null($actual)`, `Assert::notNull($actual)`
- `Assert::truthy($actual)`, `Assert::falsey($actual)`
- `Assert::contains($needle, $haystack)` - checks substring or array element; **avoid for testing output** (see Weak Tests below)
- `Assert::notContains($needle, $haystack)`
- `Assert::hasKey($key, $array)`, `Assert::hasNotKey($key, $array)`
- `Assert::count($count, $value)`
- `Assert::type($type, $value)` - class/interface or built-in type (`'string'`, `'int'`, `'list'`, etc.)
- `Assert::match($pattern, $actual)` - pattern matching with placeholders (see below)
- `Assert::matchFile($file, $actual)` - pattern loaded from file
- `Assert::exception($fn, $class, $message, $code)` - asserts exception is thrown
- `Assert::error($fn, $type, $message)` - asserts PHP error/warning/deprecation is generated
- `Assert::noError($fn)` - asserts no errors or exceptions

### Weak Tests

A test exists to fail when the code breaks. Before finishing one, ask: **would it fail if the
code were wrong?** If several plausible breakages would still pass, the test only proves that
the code runs.

| Weak | Why | Instead |
|---|---|---|
| `Assert::contains($needle, $html)`, `match('%A%x%A%')` | pass for any broken output that keeps the needle | `same()` / `match()` / `matchFile()` on the whole output |
| `Assert::notContains('x', $out)`, `notSame($a->toArray(), $b->toArray())` | countless wrong values differ or lack `x` | the exact value, or the difference on one key |
| `Assert::truthy($out)`, `type('string', $out)`, `count() > 0` | prove only that something came back | the expected value |
| `Assert::same($x->a(), $x->b())`, `> 1` | both sides break the same way | the known value |
| `Assert::exception(fn, Exception::class)`, message `'%a%key%a%'` | a rejection for a wrong reason passes | the concrete class and the whole message, `%a%` only for parts that vary |
| `Assert::null($x->a ?? null)`, `$x?->parent?->parent` in an argument | `??` and `?->` swallow the error | `Assert::type()` first, then the value |
| `Assert::noError(fn)` where the result matters | runs, checks nothing | assert the result |
| `assert()` in a test helper | depends on `zend.assertions`, which is `-1` in a production `php.ini` | `Assert::type()` |

The same weakness has three common sources:

- **The expected value cannot disagree.** It comes from the tested code itself (one method
  delegates to the other, `$expected = $sut->run($in)`), it is computed by the test's own helper,
  it repeats the decision of the code (the same version condition), or a repeated call is compared
  with itself, which shows determinism, not purity (compare `serialize()` of the input before and
  after instead).
- **The input never reaches the code under test**: an early return, a syntax without the closing
  delimiter it needs, a reference the test defines itself, input the operation has nothing to do
  with, a parameter whose branch is chosen by a threshold the input does not cross (two workers
  over one file). A data-driven or meta-test (`Assert::same([], $failures)`, assertions in a loop
  over found items) passes when it finds nothing: assert that it checked something, with a
  threshold from the measured value.
- **The test does not check what it claims.** A description promising more than the assertions
  ("exactly once", "every type" with one type, a method name that no longer exists), a comment
  "checks" next to a call without an assertion, setup that plays no role, a "does not report /
  change / call" test without the same input in the form that does, a validator run only over
  clean data, a missing side effect inferred from a result a fallback would give too (observe the
  file, the content, the mtime). Comparing only the output misses state the output does not show,
  such as parent pointers; add a structural check over the same data.

Also wrong in the other direction: an exact number where the contract says only "it changed"
(a revision), or a number that depends on the environment (files in a directory) instead of being
derived from the data.

#### Not weak, keep it

- One assertion on a documented default value. A tautology is testing PHP itself (`clone`,
  `extends`, `readonly`) or the same default in many tests.
- `Assert::type(X::class, $e)` right after `$e = Assert::exception(…, X::class)`: unlike
  `type('string', $out)` above, it narrows `X|null` for PHPStan.
- A test of a foreign library that guards an assumption your code relies on.
- An order the code creates on purpose (`ksort()` before output).
- A duplicate case on which the truth of another test's description rests.

To verify that a new check can fail, break the code on purpose and watch it fail; do not judge it.

### Pattern Matching with Assert::match

`Assert::match($pattern, $actual)` compares a string against a pattern with placeholders. `Assert::matchFile($file, $actual)` works the same way but loads the pattern from a file.

Available placeholders:

| Pattern | Meaning |
|---------|---------|
| `%a%` | one or more of anything except end of line |
| `%a?%` | zero or more of anything except end of line |
| `%A%` | one or more of anything including end of line |
| `%A?%` | zero or more of anything including end of line |
| `%s%` / `%s?%` | one or more / zero or more whitespace (except EOL) |
| `%S%` / `%S?%` | one or more / zero or more non-whitespace |
| `%c%` | a single character (except end of line) |
| `%d%` / `%d?%` | one or more / zero or more digits |
| `%i%` | signed integer |
| `%f%` | floating point number |
| `%h%` | one or more HEX digits |
| `%w%` | one or more alphanumeric characters |
| `%ds%` | directory separator (`/` or `\`) |
| `%%` | literal `%` character |

Important behavior:
- **Ungreedy matching** - placeholders match as little text as possible, so `%a%` captures the shortest possible string
- **Line endings are normalized** - `\r\n` and `\n` are treated as equivalent, so tests work cross-platform
- **Trailing whitespace is ignored**
- Patterns can also be raw regexps delimited by `~` or `#`

```php
Assert::match('<div class="item">%a%</div>', $html);

// For larger patterns, use NOWDOC syntax
Assert::match(<<<'XX'
	<html>
	<body>%A%</body>
	</html>
	XX, $html);

// Or load the pattern from a file (supports the same placeholders)
Assert::matchFile(__DIR__ . '/expected/output.html', $actual);
```

When `Assert::matchFile()` fails, the expected and actual output are written to the test output directory as `.expected` and `.actual` files.

### Testing Exceptions

For simple single-call exceptions, use the concise `fn()` style:

```php
Assert::exception(
	fn() => Arrays::pick($arr, 'undefined'),
	Nette\InvalidArgumentException::class,
	"Missing item '%s%'.",
);
```

The `Assert::exception()` method:
1. First parameter: A closure that should throw the exception
2. Second parameter: Expected exception class
3. Third parameter (optional): Expected exception message, can contain match placeholders (`%a%`, `%s%`, etc.)

For testing PHP errors and deprecations:

```php
Assert::error(
	fn() => $object->deprecatedMethod(),
	E_USER_DEPRECATED,
	'This method is deprecated',
);
```

If the entire `test()` block is to end with an exception, you can use `testException()`:

```php
testException('throws exception for invalid input', function () {
	$mapper = new FilesystemMapper(__DIR__ . '/fixtures');
	$mapper->getAsset('missing.txt');
}, AssetNotFoundException::class, "Asset file 'missing.txt' not found at path: %a%");
```

### `Assert::exception()` returns the exception

```php
public static function exception(callable $function, string $class, ?string $message = null, int|string|null $code = null): ?\Throwable
```

The 3rd argument is an `Assert::match` pattern, the 4th compares `getCode()` strictly. The caught exception is **returned**, so a previous/nested one can be asserted further. `Assert::throws()` is a plain alias.

```php
$e = Assert::exception(fn() => $obj->save(), DbException::class, 'Insert failed%a%', 1062);
Assert::type(PDOException::class, $e->getPrevious());
```

### setUp() for Shared Setup

Use `setUp()` to run common initialization before each `test()` block:

```php
$db = null;

setUp(function () use (&$db) {
	$db = new TestDatabase;
});

test('insert works', function () use (&$db) {
	$db->table('user')->insert(['name' => 'John']);
	Assert::count(1, $db->table('user')->fetchAll());
});

test('each test gets fresh setup', function () use (&$db) {
	// setUp() runs again before this test
	Assert::type(TestDatabase::class, $db);
});
```

### Helpers

| Call | Purpose |
|---|---|
| `DomQuery::fromHtml($html)` / `::fromXml($xml)` | CSS querying; extends `SimpleXMLElement`, so text is `(string) $el`, attributes `$el['href']`. `find($sel): list<DomQuery>`, `has($sel)`, `matches($sel)` (2.5.3), `closest($sel)` (2.5.5, needs PHP 8.4) |
| `FileMock::create(string $content = '', ?string $extension = null): string` | returns a `mock://N.ext` URL usable with `fopen`, `file_get_contents`, `parse_ini_file`, … |
| `Helpers::purge(string $dir): void` | creates the dir, wipes its contents; throws on an empty string or a root path |
| `Environment::lock(string $name, string $path): void` | serializes parallel tests; **the only sane fix for several tests hitting one database** |
| `Environment::skip(string $message = ''): void` | skips the running test |
| `Assert::with(object\|string $objectOrClass, Closure $fn): mixed` | runs `$fn` bound to the object so `$this` reaches private members (a class-string binds statically); returns the closure's value |

`Environment::lock()`'s `$path` defaults to `''`, putting the lock file in the filesystem root — always pass a directory: `Tester\Environment::lock('database', __DIR__ . '/tmp')`.

`Tester\Expect` builds partial constraints, honoured **only inside `Assert::equal()`**, never in `Assert::same()`:

```php
Assert::equal([
	'id' => Expect::type('int'),
	'name' => Expect::match('%a%ová')->andType('string'),
	'tags' => Expect::count(3),
], $row);
```

Static factories mirror the assertions (`same`, `notSame`, `equal`, `contains`, `true`, `null`, `nan`, `truthy`, `count`, `type`, `match`, `matchFile`, …); chain more with the `and*` prefix, or use `Expect::that(fn($v) => $v > 0)` for a callback (returning `false` fails).

`Tester\HttpAssert` (since 2.5.6) drives real HTTP requests over cURL; `deny*` variants exist for all three:

```php
HttpAssert::fetch($url, method: 'POST', headers: [...], cookies: [...], follow: false, body: '{}')
	->expectCode(201)->expectHeader('Content-Type', contains: 'json')->expectBody(matches: '%A%ok%A%');
```

### Essential Commands

```bash
vendor/bin/tester tests/ -s                     # run everything, show skip reasons
php tests/common/Engine.phpt                    # run one test directly, without the runner
vendor/bin/tester tests/ --coverage coverage.html --coverage-src app/
```

| Flag | Effect |
|---|---|
| `-s` | show why tests were skipped (otherwise skips are silent) |
| `-j <num>` | parallel jobs, **default 8** — not 1 |
| `--stop-on-fail` | stop at the first failure |
| `-w \| --watch <path>` | after the run keep watching the path and re-run on change; repeatable |
| `-o <format>[:<file>]` | `console`, `console-lines`, `tap`, `junit`, `log`, `none`; repeatable, e.g. `-o junit:out.xml -o none` |
| `--setup <path>` | PHP script loaded at startup with `Tester\Runner\Runner $runner` in scope |
| `--coverage <file>` + `--coverage-src <path>` | needs Xdebug or PCOV **and** `Environment::setup()` in the tests, else the report is empty |

### Test Output Directory

When a test fails, Nette Tester writes the expected and actual output into an `output` directory next to the test files (e.g. `tests/Tracy/output/`). For each failing test `Foo.phpt`, you will find:

- `Foo.expected` - what the test expected to see
- `Foo.actual` - what was actually produced

**Always look at these files first** when investigating test failures. Comparing `.expected` vs `.actual` shows the exact difference and is much more informative than the short failure message printed by the runner.

Accepting a new expected file from `.actual`:

- **The first failing `test()` ends the file** – it rethrows the assertion. Further `matchFile()`
  calls in that file have not run yet; accept one, run again, repeat.
- **Clear `output/` before the run**: `.actual` files of earlier runs stay there.
- **Read the diff before accepting it.** A snapshot regenerated blindly records bugs as expected
  behavior, and a typo in the source fixture can silently stop exercising the feature at all.
- `.actual` may differ in line endings and in the final newline from the original (`matchFile`
  ignores trailing whitespace); diff with `--strip-trailing-cr`.
- To find an exact expected value, put a placeholder into the assertion and read `.actual`. It
  shows what the code does, not what it should do: judge it before you accept it.

### Documentation

The full Nette documentation for this topic is bundled with the plugin. Read a page when you need details beyond this skill:

- [Getting Started](../../docs/tester/guide.md) – installation, the first test
- [Writing Tests](../../docs/tester/writing-tests.md) – test files, `test()`, structure
- [Assertions](../../docs/tester/assertions.md) – every Assert method
- [TestCase](../../docs/tester/testcase.md) – test classes
- [Test Annotations](../../docs/tester/test-annotations.md) – @testCase, @dataProvider, @skip
- [Helpers](../../docs/tester/helpers.md) – HttpAssert, DomQuery, FileMock, Environment
- [Running Tests](../../docs/tester/running-tests.md) – command-line options

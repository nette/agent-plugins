---
name: phpstan-analysis
description: Invoke BEFORE running PHPStan or fixing PHPStan errors. Covers error resolution strategy (refactoring > phpDoc > ignoring), common Nette error patterns, baseline management, and type tests. Use this whenever the user mentions PHPStan, static analysis, type errors, wants to suppress warnings, or manage the baseline - even for a single error.
---

## PHPStan Analysis

### Running PHPStan

Never use `--error-format=json` - its output format can change between PHPStan versions and is not designed for stable machine consumption. For machine-readable output, use `--error-format=raw`.

**Take the verdict from the exit code, never from grepping the output.** A filter defines what you see, so anything it misses reads as success - and some findings (an unmatched `ignoreErrors` pattern, for one) are not tied to any file and carry no file prefix at all.

### Target Levels

Target level for Nette libraries is **8**. Levels higher than 8 are not worth pursuing - the additional strictness (e.g., `non-empty-string`, `positive-int`) catches very few real bugs relative to the annotation burden.

- **Level 7**: Union types checked
- **Level 8**: Null checks, strict types (our target)

### nette/phpstan-rules

Installed by all Nette libraries. It works in both directions, and mixing them up is a real
hazard:

- **It narrows types and silences false positives** — many PHPStan errors disappear without
  manual fixes. Don't fight those (list below).
- **It also REPORTS errors of its own and turns on stricter reporting.** Two rules of its own:
  `nette.abortException` (an `AbortException` caught and not rethrown — the fix is to rethrow it
  in its own `catch`, which is not guessable) and `nette.strings.regexpPattern` (an invalid
  regular expression). On top of that `extension.neon` forces nine flags on, including
  `checkMissingCallableSignature`, `reportMaybesInMethodSignatures` and
  `reportAlwaysTrueInLastCondition` — so some errors exist **only because** this extension is
  installed. Those are genuine findings; never silence them.

It also registers `universalObjectCratesClasses` for `DefaultTemplate`, `Database\Row`,
`SessionSection`, `SimpleIdentity` and `Html`, so the most common `property.notFound` in a Nette
app is already solved — don't add `@property` annotations for those.

Don't add asserts, casts, or `@var` for errors that fall into these categories:

- **Nette helpers**: `Strings::match()`, `Arrays::invoke()`, `Helpers::falseToNull()`, `Expect::array()`, `Html` magic methods (`setXxx`/`getXxx`/`addXxx`), `Container::getComponent()` and `$this['name']`, Form `$form['name']`
- **Native PHP functions**: `|false` / `|null` removed where unrealistic (`getcwd`, `json_encode`, `preg_*`, intl/GD/DOM/etc.)
  - For `preg_*` with the `u` modifier, `false` can genuinely occur on invalid UTF-8 input, yet it is stripped **by design** — a regex call site should not double as input validation. So a dead `=== false` check after `preg_*` (`identical.alwaysFalse`) is not a bug in the extension: refactor to an explicit up-front `preg_match('##u', $s)` validation, the one form where `false` is preserved.
- **After `Tester\Assert`**: `notNull()`, `type()`, `true()`, etc. narrow the type
- **Silenced false positives**: arrow fns passed to `test()` / `Assert::exception()`, runtime variadic-closure type validation, Form event-handler callbacks with narrow data parameter

Two features require config in `phpstan.neon` (NOT app's `common.neon`):

**Database row mapping** — narrows `Explorer::table()`, `ActiveRow::related()`, `::ref()` to concrete row classes. Keys may contain a single `*` wildcard; a bare `*` is the catch-all and substitutes PascalCase of the table name into `*` in the value. Exact keys win over wildcards; wildcards are tried in declaration order.

```neon
parameters:
	nette:
		database:
			mapping:
				tables:
					booking: App\Entity\BookingRow   # exact match
					event_*: App\Entity\Event*Row    # event_video → EventVideoRow
					*: App\Entity\*Row               # catch-all fallback
```

**Asset type narrowing** — narrows `Registry::getMapper()` / `getAsset()` / `tryGetAsset()` (and `FilesystemMapper::getAsset()` / `ViteMapper::getAsset()`) based on mapper ID and file extension. Values `file` and `vite` are shortcuts for the built-in `FilesystemMapper` / `ViteMapper`; any other value is treated as an FQCN of a custom mapper class.

```neon
parameters:
	nette:
		assets:
			mapping:
				default: file              # FilesystemMapper
				images: file
				vite: vite                 # ViteMapper
				custom: App\MyMapper       # custom mapper FQCN
```

Full reference: https://doc.nette.org/en/tools/phpstan-rules

---

## Error Resolution Strategy

### Resolution Priority

Resolve every error in this order of preference. Only fall back to the next step when the current one genuinely doesn't apply — this ladder is the backbone of the whole skill:

1. **Refactoring** - if an error reveals a design weakness, fix the design first
2. **phpDoc** - if the code is correct but its types are imprecise
3. **`assert()`** - sparingly, only when the type cannot be expressed otherwise
4. **Ignore in `phpstan.neon`** - for systematic or intentional patterns, always with a comment explaining why
5. **Baseline** - last resort, keep minimal

Two hard rules override the ladder at every step:

- **Never silence errors** - a fix must not hide a potential problem (see "Never Silence Errors" below).
- **Never use `@phpstan-ignore` annotations** - keep checker-specific directives out of source code; ignore in `phpstan.neon` instead.

### Agree on the Resolutions First

Before changing code, present the errors grouped by identifier, with the rung of the ladder chosen for each group and the reason.

### Refactoring as First Choice

Always ask: **does this error reveal a real design issue?** Examples:

- **Overly broad return types** - method returns `mixed` or `object` but always returns a specific type; narrow the return type
- **Interface too loose** - code calls a method on implementation but not on interface; extend the interface
- **Mixed responsibilities** - class handles too many types; split it
- **Unnecessary dynamic access** - `__get`/`__set` where typed properties would work

The goal is not to "make PHPStan happy" but to use its feedback as a catalyst for better code.

---

## Code Fixes Guidelines

### Never Silence Errors

The code worked before. A fix that hides an error degrades code quality.

```php
// Before - json_encode returns false on error and we find out (type error)
function foo(): string {
	return json_encode($this->value);
}

// WRONG - error is hidden
function foo(): string {
	return (string) json_encode($this->value);
}
```

Better solutions, in order: use `Json::encode()`, or add an explicit check that throws. Only when neither applies, fall back to the baseline (last resort, per the resolution priority).

### Throw Expression Pattern

```php
// Before - fopen can return false
$f = fopen($file, 'r');

// Correct fix
$f = fopen($file, 'r') ?: throw new IOException("Cannot open file $file");
```

### Beware of `/** @var */` in Method Bodies

`/** @var Type */` in method body is taken authoritatively by PHPStan - it completely disables type checking for that variable. Use only when no better solution exists.

### Don't "refine" bare `callable` into `callable(...mixed): mixed`

When PHPStan reports a missing callable signature (`missingType.callable`), it's tempting to write `callable(...mixed): mixed`. **Don't.** That type is *narrower* than bare `callable`, not wider, because of parameter contravariance:

- `callable(...mixed): mixed` claims the callee may be invoked with *any* arguments, so only callbacks whose parameters are all `mixed`-compatible (or which have no required typed params) satisfy it.
- A normal callback like `function (UiForm $form, mixed $value): void {}` is then **rejected** at call sites: `expects callable(mixed...): mixed, Closure(UiForm, mixed): void given`.

Bare `callable` is PHPStan's top type for callables — it accepts anything invokable regardless of signature, and `$cb(...$args)` inside the function still type-checks. So for "invoke arbitrary user callbacks" APIs keep `callable` (e.g. `@param iterable<callable> $callbacks`) and, if `missingType.callable` fires, ignore it for that file in `phpstan.neon` with a comment. An explicit signature buys nothing and introduces false positives. (nette/utils `Arrays::invoke()` is an example.)

The same applies to bare **`\Closure`** — when the value is guaranteed to be a closure (stored property, result of `Closure::fromCallable()`, etc.) but its signature is unknown or intentionally polymorphic, use plain `\Closure` without parameters. `missingType.callable` then fires on it too and is ignored on the same grounds.

---

## phpDoc and Type Annotations

For phpDoc conventions (when to skip docs, array types, writing style), see the **php-doc** skill.

Key rules specific to PHPStan compatibility:

- phpDoc type must always match the native type:

| Native Type | Wrong phpDoc | Correct phpDoc |
|-------------|--------------|----------------|
| `array\|string` | `mixed[]` | `mixed[]\|string` |
| `array\|null` | `int[]` | `int[]\|null` |
| `object\|array` | `stdClass` | `stdClass\|array` |

- Don't use overly granular types (`positive-int`, `non-empty-string`, `non-empty-array`, `non-falsy-string`) - they rarely catch real bugs but add significant annotation maintenance burden
- Use `class-string<T>`, `array<string, Foo>`, `list<int>`, `array{name: string, age: int}` - these are useful for PHPStan and worth maintaining

**Array notation preference:**
- `foo[]` - always prefer for simple types (shortest notation)
- `array<foo|bar>` - for union types (more readable than `(foo|bar)[]`)
- `array<string, foo>` or `list<foo>` - when keys are not generic

---

## Common Nette Error Patterns

### `property.nonObject` - property access on `array|object`

In DI Extensions, `$this->config` returns `array|object` but is `stdClass`. This is one of the legitimate cases for `/** @var */` in method body - the type cannot be expressed otherwise:

```php
public function loadConfiguration(): void
{
	/** @var \stdClass $config */
	$config = $this->config;
}
```

### `method.notFound` / `staticMethod.notFound` / `arguments.count`

Calling method on interface that exists only on implementation. Fix type if possible, or use `assert()`:

```php
$component = $container->getComponent($name);
assert($component instanceof Component);
$component->saveState($params);
```

Note: when accessing components via `$this['name']` or `$this->getComponent('name')` with a constant string and a matching `createComponent<Name>()` factory on the same class, phpstan-rules narrows the type automatically — no assert needed. The assert pattern above applies when the name is dynamic or the factory lives elsewhere.

### `property.uninitializedReadonly` / `property.readOnlyAssignNotInConstructor`

Readonly properties initialized via inject methods (Nette DI pattern). Ignore - this is an intentional framework pattern.

### `new.static` - unsafe usage of `new static()`

If intentional design pattern (derive/factory methods), ignore in phpstan.neon. Or change to `new self` if subclassing isn't expected.

### `closure.unusedUse`

Variable in `use ($var)` used in `require`'d file. Ignore - false positive.

### `function.alreadyNarrowedType`

PHPStan knows the type is already narrowed. Remove unnecessary condition, or ignore if it serves as runtime validation.

### `catch.neverThrown`

Verify if the catch is actually needed. If so, ignore.

---

## Ignoring Errors

Ignoring sits at the bottom of the resolution ladder (see "Resolution Priority") — exhaust refactoring, phpDoc, and `assert()` first. When ignoring is genuinely the right call, prefer `phpstan.neon` for systematic or intentional patterns (always with a comment) over `phpstan-baseline.neon` (last resort, minimize).

### Forbidden and Discouraged Suppressions

These rules apply to **both** `phpstan.neon` `ignoreErrors` and `phpstan-baseline.neon`.

**MUST NOT be suppressed — always fix:**

- **`phpDoc.parseError`** — broken phpDoc syntax. Suppressing it leaves the phpDoc permanently unparseable; fix the syntax.
- **`argument.templateType`** — a generic template parameter cannot be inferred from arguments. The template is either misdesigned or redundant; redesign the generic or drop the template parameter.
- **Anything in `tests/types/*`** — these files are the library's type contract (`TypeAssert` / `assertType`). Suppressing an error here silently invalidates the contract and defeats the purpose of type tests.

**SHOULD NOT be suppressed — fix unless truly unavoidable:**

- **`missingType.*`** (e.g. `missingType.iterableValue`, `missingType.parameter`, `missingType.return`) — the type is almost always expressible (`array<…>`, concrete class, `list<>`, `array{…}`). Suppress only when the type genuinely cannot be expressed. Exception: `missingType.callable` for "invoke arbitrary user callbacks" APIs (see "Don't refine bare `callable`" above) — that one is legitimate.
- **`parameter.phpDocType`** — phpDoc type doesn't match native type. This is almost always a real documentation bug; fix the phpDoc rather than hide it.

### Systematic Patterns in phpstan.neon

**Target ignores narrowly — never write a blank check.** An entry like

```neon
# WRONG — blanket suppression hides every future method.notFound in this file
-   identifier: method.notFound
	path: src/Forms/Controls/SubmitButton.php
```

silences not only the intended pattern but every future legitimate `method.notFound` in that file — including real typos and broken refactorings.

#### The model pattern: one bullet = one specific phenomenon

Each entry should describe a **single concrete phenomenon** — a specific message in a specific scope — not "ignore identifier X in file Y". Constraining tools, in order of strength:

- **`message:`** — regex matching the exact error message. The strongest safeguard: protects against future drift of *different* errors with the same `identifier` in the same file. Always include it when the message is reasonably stable.
- **`count:`** — pin the number of occurrences. If a new instance appears (or one disappears), PHPStan reports a mismatch and the developer is forced to look.
- **`identifier:`** — narrows to one error kind.
- **Path scoping** — three forms, choose the most specific:
  - **`path: src/Foo/Bar.php`** — single file (preferred when the phenomenon lives in one file)
  - **`path: src/Foo/Bar/*`** — wildcard mask for a directory tree (when the same phenomenon recurs across siblings)
  - **`paths: [...]`** — explicit list of files (when the phenomenon lives in a handful of unrelated files)

**Important: `count:` requires `path:` (singular), not `paths:` (plural).** With a wildcard mask in `path:` you still get `count:` — that's the compact form for "this phenomenon appears N times across this tree". With `paths:` you sacrifice `count:` for enumeration; compensate with a tight `message:` regex.

#### Canonical examples

```neon
parameters:
	ignoreErrors:
		# One phenomenon across a directory tree (wildcard path + count)
		-   # Latte nodes use new static() by design for extensibility
			identifier: new.static
			path: src/Bridges/FormsLatte/Nodes/*
			count: 6

		# One phenomenon across an explicit list of files (paths array + tight message regex)
		-   # parent::getControl()/getLabel() returns the wider public contract (Html|string|null),
			# but BaseControl implementation deterministically returns Html, so chaining is safe.
			identifier: method.nonObject
			message: '#^Cannot call method \w+\(\) on Nette\\Utils\\Html\|string(\|null)?\.$#'
			paths:
				- src/Forms/Controls/Checkbox.php
				- src/Forms/Controls/CheckboxList.php
				- src/Forms/Controls/RadioList.php

		# One phenomenon in one file (single path + message + count — strongest form)
		-   # SubmitButton::getScopeForValidation() walks getParent() which is typed as
			# Container|Control; lookupPath() exists on Container at runtime.
			identifier: method.notFound
			message: '#^Call to an undefined method Nette\\Forms\\Container\|Nette\\Forms\\Control::lookupPath\(\)\.$#'
			path: src/Forms/Controls/SubmitButton.php
			count: 1
```

**Canonical reference:** `nette/forms` `phpstan.neon` follows this pattern throughout — use it as the template when refactoring other configs.

Always include a comment explaining why the error is ignored.

### Baseline

```bash
vendor/bin/phpstan analyse --generate-baseline
```

Use only for false positives that are not systematic, or individual cases where fix requires BC break.

---

## phpstan.neon Structure

```neon
parameters:
	level: 8

	paths:
		- src

	excludePaths:
		- src/compatibility.php
		# other files for historical compatibility

	ignoreErrors:
		# systematic patterns with comments

includes:
	- phpstan-baseline.neon
```

Exclude files for backward compatibility with historical versions (compatibility.php, Latte 2 support, etc.).

---

## Type Tests

Files in `tests/types/*.php` verify that types in the library are defined correctly.

**Purpose:**
- Guarantee that the library won't cause type problems for users
- Protect against unintended type changes during refactoring
- Especially important for complex generics

These tests must always pass and must never be ignored.

**Using TypeAssert (from nette/phpstan-rules):**

```php
use Nette\PHPStan\Tester\TypeAssert;

TypeAssert::assertTypes(__DIR__ . '/data/types.php');
TypeAssert::assertNoErrors(__DIR__ . '/data/clean.php');
```

**Data file with assertType:**

```php
use function PHPStan\Testing\assertType;

assertType('non-empty-string', getcwd());
assertType('string', Normalizer::normalize('foo'));
```

---

## After the Fixes

Run the tests: a refactoring done for PHPStan can change behavior.

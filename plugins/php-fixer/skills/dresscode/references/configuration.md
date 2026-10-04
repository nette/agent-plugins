# DressCode Configuration

The configuration is one file in the project root, `dresscode.neon` or `dresscode.php`; both say the same, every NEON key being a named argument of `DressCode\Config`, the sections of the decisions together under `decisions`. Having both is an error. A committed `dresscode.neon.dist` may lie beside it; the file without `.dist` then replaces it whole, for a local deviation. All paths are relative to the directory of the file, which becomes the project root.

A key at the top is either one of the reserved keys below or a section of decisions (`file`, `indentation`, `spacing`, `braces`, `hygiene`, `mistakes`, `upgrading`…, a section of a plugin, `project`). A typo fails the load with a suggestion (`Key spacing.cal is unknown; write spacing.call`), and `dresscode config` shows what the file resolves to.

## A Fuller Example

```neon
use:
	- perCs
	- cleanup

indentation:
	unit: tab

file:
	lineEnding: LF
	lineLength: 100
	longLines: forbidden          # report the lines nothing could split

spacing:
	arrowFunction: 'fn ($x) => $x'

literals:
	quotes: single

expressions:
	comparison:
		loose: forbidden

controlFlow:
	elseAfterReturn: keep         # leave it alone, whatever the presets say

multiline:
	trailingComma:
		argument: required
	edges:
		array: [apart, attached]  # both pass, `apart` is written

mistakes:
	debugOutput: forbidden
	debugOutputFunctions: [..., dump]

targets:
	php: '8.2'

typeAnalysis: phpstan

nameResolution: certain

severity:
	file.longLines: warning

paths:
	- src
	- tests

excludePaths:
	- tests/fixtures
	- '*.generated.php'

fileExtensions:
	- php
	- phpt

overrides:
	- paths: [tests]
	  blankLines: {afterImports: 2}
	  severity: {literals: warning}

baseline: dresscode-baseline.neon
```

## Standards and Sets

`use` names the presets the project builds on, the standard first and the sets after it, each a file of the same shape as the configuration. The standard is one of `perCs` (PER Coding Style 3.1: four spaces, lines up to 120), `psr12` (four spaces, 120), `nette` (tabs, 140, and much of the hygiene of the code) or `symfony` (four spaces, no limit). A preset of a package is written with its vendor (`acme/house`), a file of the project by its path. A single entry needs no list (`use: nette`).

Beside the standard go the sets, exactly these six:

| set | decides | for example |
|---|---|---|
| `modernizations` | the construct the target PHP has where an older one says the same | `upgrading.syntax.nullsafe`, `upgrading.syntax.match` |
| `deprecations` | what the target PHP or a library deprecated or removed | `upgrading.php.deprecatedCall`, `upgrading.declarations.deprecatedMember` |
| `cleanup` | code that is there for nothing | `imports.unused`, `hygiene.strlenEmptyTest` |
| `correctness` | what is most likely a mistake | `mistakes.repeatedAssignment`, `mistakes.unreachableCatch` |
| `types` | types written where PHP reads them | `types.declaration.parameter`, `types.declaration.return` |
| `compilerOptimizations` | global names in the form PHP optimizes at compile time | `qualification.function.optimized`, `qualification.constant.optimized` |

A set decides nothing about the looks, so it never fights the standard, and a decision the project writes itself lies above every preset. `nette` uses `cleanup`, `types` and `correctness` itself and sets some of their decisions back to `keep` (the native types of parameters, properties and return values); writing such a set again changes nothing, and the run warns that the entry does nothing. A decision outside the standards and the sets (`upgrading.classes.Override`, `literals.quotes` over `perCs`) is written by the project. `dresscode config` lists in its `Use` line every plugin and preset the run laid.

## Decisions

A decision is written under its section, nested as its path is (`braces: {position: {class: nextLine}}` is `braces.position.class`). What a value may be depends on the decision, which `dresscode explain <path>` prints with the values of the four standards:

- a presence: `required`, `forbidden`, or `optional` (as the author wrote it), as in `hygiene: {is_null: forbidden}`;
- another word (`nextLine`, `single`, `tab`, `LF`);
- a shape the construct takes, written by its example in single quotes (`spacing: {call: 'foo($a, $b)'}`, `spacing: {arrowFunction: 'fn ($x) => $x'}`); unquoted, NEON would read it as an entity;
- a list of words or shapes for a tolerance, where the decision takes one: each passes and the first is written where none matches (`multiline: {edges: {array: [apart, attached]}}`, `qualification: {function: {optimized: [backslashed, bare]}}`);
- a count `2`, a range `0-2`, an open count `1+`, or `none` where the decision names it (`file.lineLength: none`);
- a list of names, or a map of names to values (the maps of `upgrading.api`);
- `yes` or `no`, which only a parameter takes (`upgrading.api.packageData: yes`);
- `keep`, which leaves the thing alone. On a section or a structure (`controlFlow: keep`) it covers every decision under it, and a later layer brings back one key at a time.

A word written on a group applies to every key under it that takes it: `multiline: {trailingComma: required}` sets the trailing comma of every list, `braces: {position: nextLine}` every brace position.

A requirement turns its rule on wherever it is not `keep`; a parameter only refines a requirement (`file.lineLength`, `mistakes.debugOutputFunctions`), has a default instead of `keep` and turns nothing on. A rule of the project itself is registered by its class under `rules` (`rules: [App\CodeStyle\RawQueriesRule]`), and the configuration writes its decision under the section `project` like any other (`project: {rawQueries: forbidden}`).

## How Layers Combine

For each file, from the lowest layer: the presets of `use` in the order written (each above what it uses, every preset laid once), the decisions of the configuration, the matching `overrides` in the order written, the command line (`--use`, `--set`).

- A word, a shape, a count or a flag comes from the highest layer that names it.
- A list replaces the list below it, its default included: `mistakes: {debugOutputFunctions: [var_dump]}` drops `print_r` and `var_export` from the list. `...` in the list stands for the list below: `[..., dump]` adds `dump` to it.
- A map merges by name; an entry `name: keep` withdraws that entry from every layer below, the data of the packages included, and `keep` as the whole value withdraws the map. A key written with `!` (`replacedClasses!:`) replaces the maps below with its own entries.
- A list key of the profile (`fixRisky`, `namespaces`) adds up across layers; a higher layer cannot remove an item.
- `keep` on a section or a structure clears what the layers below said under it.

A preset carries only decisions, `use`, `suppressionComments` and `severity`. The facts of the project (`targets`, `typeAnalysis`, `namespaces`, `nameResolution`), the scope (`paths`, `excludePaths`, `fileExtensions`, `skipWhen`, `baseline`, `cacheDir`), what the project brings in (plugins in `use`, `rules`, `analyses`) and `fixRisky` are the project's.

## Style

The style is decisions like any other:

- `indentation.unit`: `tab`, `4 spaces` or `2 spaces`; `indentation.tabWidth` how many columns a tab counts for.
- `file.lineEnding`: `LF` or `CRLF` in capitals (`lf` is an error), or `majority` (each file keeps the ending it mostly has; the default of every standard).
- `file.lineLength`: the widest line, a tab counting to the next stop, or `none`. It is shared by every rule that needs it: the multiline decisions break a signature, a condition or an array wider than the limit. `file.longLines` reports the long lines that remain, which need a person: `forbidden` in every standard but `symfony`, and `perCs` and `psr12` make it only warn through `severity`.

## Target Versions

The target PHP version is the lowest one `require.php` of `composer.json` allows; a constraint without a lower bound (`*`, `<8.4`) means PHP 8.0, the oldest DressCode supports. Write `targets` only when the code is written for another version or the version cannot be read.

The version of a package is read the same way, from the constraint of the project when the project requires the package itself, from the installed version otherwise. `targets` may name a package too, which then wins over `composer.json` (used to convert code before an update, see [the upgrading guide](upgrading.md#libraries)). Write every version in quotes, because NEON reads `3.10` as the number 3.1:

```neon
targets:
	php: '8.2'
	nette/forms: '3.3'
```

## Types From PHPStan

`typeAnalysis: phpstan` makes DressCode ask the PHPStan installed beside it, with the project's `phpstan.neon` and extensions, for the types of the code. Decisions that need types (the deprecated API of a library, `upgrading.classes.Override`, `literals.classNameInString`…) take effect only with it, and many risky fixes become safe where the types prove it.

- Install both into the project: `composer require --dev phpstan/phpstan dresscode/dresscode`. A global DressCode with a global PHPStan works too, but with the global version of PHPStan and only extensions installed globally.
- A decision needing types that a preset writes takes no effect without the key, silently (`dresscode config` says `no effect: types`); one the configuration writes is an error (exit `3`).
- The run is noticeably slower, and the cache does not see changes outside the checked file (a new `@deprecated` in another class, a changed `phpstan.neon`); run with `--no-cache` after such a change and in CI.

## Names in Namespaces

An unqualified `strlen()` or `PHP_EOL` inside a namespace may mean a function or constant of that namespace, so fixes that assume the global one are risky. `nameResolution: certain` says the `namespaces` lists name every function and constant the project declares in namespaces, which makes those fixes safe; a declaration missing from the lists is then reported under `namespaces.functions` or `namespaces.constants`. `dresscode init` writes both from the code. A part of the project that declares helpers without listing them (tests, typically) gets `nameResolution: uncertain` in an override.

```neon
nameResolution: certain
namespaces:
	functions:
		- 'App\Utils\{formatPrice, slugify}'
	constants:
		- App\VERSION
```

## Risky Fixes and Severity

`fixRisky` lists the decisions (or sections, or presets) whose risky fixes the project accepts; `fix` then makes those and reports the rest. `--fix-risky=<name>` does the same for one run. Never add to it without the user's consent. `types`, which is both a section and a set, is written `types.*` for the section and `dresscode/types` for the set; the bare name is refused.

`severity` maps names to `warning` or `error`: a decision, a group, a section, a preset or a rule class. A warning is reported and keeps the exit code at `0`; `--max-warnings <n>` sets a threshold, and `--severity <name>=warning` does the same for one run. Within one layer the most specific name wins (a decision over its group, a group over its section, a section over a preset). A severity holds for the value written by its layer or below it: a higher layer that writes the value of the decision anew makes it an error again unless it says the severity too. It changes no fix, and suits what `fix` cannot do, above all `file.longLines`; `dresscode config` marks such a decision `only warns`.

```neon
severity:
	file.longLines: warning
	upgrading: warning
	upgrading.php.deprecatedCall: error
```

## Paths

- `paths`: what a run without paths on the command line checks.
- `excludePaths`: added to the defaults (`vendor`, `node_modules`, `temp`, `tmp`, `log`, and everything starting with a dot); nothing can bring an excluded path back. A pattern with a slash is anchored to the root (`tests/fixtures`), one without matches a name at any depth (`fixtures`); `*` stays within a segment, `**` crosses directories.
- `fileExtensions`: `php` by default; add `phpt` for Nette Tester.
- `skipWhen`: a callback receiving the content and the path of a file, for skipping generated code by its header (`App\CodeStyle\Generated::isGenerated(...)`).

## Overrides

An entry of `overrides` gives the files matching its `paths` a profile of their own: decisions, `use` with presets, `nameResolution`, `namespaces`, `fixRisky`, `severity`, `suppressionComments`, and of `targets` only `php`. Entries apply in the order written. To exempt a path from a decision, set it to `keep` there; when the part only needs another value, set that value instead.

```neon
overrides:
	- paths: [src]
	  use: [compilerOptimizations]
	- paths: [legacy]
	  expressions: {comparison: keep}
	- paths: [resources/stubs]
	  indentation: {unit: 2 spaces}
```

## Suppression Comments of the Project

`suppressionComments` maps a pattern of a comment the project already writes to the decisions it silences on its line, as if `dresscode:ignore` stood there. The preset `nette` maps `// intentionally ==` and `// intentionally !=` to `expressions.comparison.loose` and `mistakes.strictArgument`, and `// @ <reason>` to `mistakes.errorSuppression`.

```neon
suppressionComments:
	'~\bintentionally\s+[!=]=~': expressions.comparison.loose
```

## Packages and Plugins

A package of rules or presets brings them through a plugin, which it announces in its `composer.json` (`extra.dresscode.plugin`). The plugin runs code at every check, so it loads only where `use` names the package or the plugin class (`use: [nette, dresscode/rules-nette]`); otherwise the run warns that it skipped it. A plugin is the project's: a preset or an override cannot name one. A plugin never decides anything on its own: its decisions live under the section its manifest names (`nette` for `dresscode/rules-nette`), and the project writes them. The `upgrading.neon` data files of a package apply without the plugin, where `upgrading.libraries.packageData` is `yes`, which the sets `deprecations` and `modernizations` set.

## Other Keys

- `baseline`: the baseline file, written by `dresscode baseline`.
- `cacheDir`: where the cache of clean files lives; by default the directory `dresscode` in the system temp. In CI, point it into the project and let CI keep it between runs.
- `analyses`: registers analyses of custom rules.

## The Same in PHP

```php
<?php declare(strict_types=1);

use DressCode\Config;

return new Config(
	use: ['perCs', 'modernizations'],
	decisions: [
		'literals' => ['quotes' => 'single'],
		'controlFlow' => ['elseAfterReturn' => 'keep'],
	],
	paths: ['src', 'tests'],
	excludePaths: ['tests/fixtures'],
);
```

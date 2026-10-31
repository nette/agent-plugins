---
name: dresscode
description: "How to run and configure DressCode, the PHP code style checker and fixer that also rewrites code for newer PHP and library versions. Use when running `dresscode` yourself (check, fix, config, explain, catalogue, init, baseline, upgrade), writing or changing `dresscode.neon` or `dresscode.php`, reading its report (violations, risky fixes, exit codes), changing a decision, silencing a finding or adding a baseline, choosing a standard or a set, setting DressCode up in CI or a Git hook, or upgrading a project to a newer PHP or library version with it. Ordinary PHP edits do not need it; the php-auto-fixer skill covers the hook that fixes them."
---

# DressCode

DressCode checks and fixes the style of PHP code and, in the same run, rewrites it for newer PHP versions and library APIs. It works on a lossless syntax tree, so the lines nothing touches stay byte for byte the same. The php-fixer hook already runs it on every PHP file you edit (the `php-auto-fixer` skill); this skill is about running it yourself and changing what it does.

The manual is at https://dresscode.run (see Documentation), but the installed tool describes itself more precisely, for exactly its version and this project's configuration (see Ask the Tool).

## How It Thinks

- **The configuration names decisions, never rules.** Every key is a thing in the code and its value says how it is written: `braces: {position: {class: nextLine}}`, `spacing: {call: 'foo($a, $b)'}`, `hygiene: {is_null: forbidden}`. A decision has a path (`braces.position.class`), and that path is what a finding is reported under, what `explain` explains and what a suppression names. The rules behind the decisions are never named.
- **It has no style of its own.** Without a configuration file or `--use <standard>` it refuses to run. The look of the code comes from one standard: `perCs` (PER Coding Style 3.1, four spaces), `psr12`, `nette` (PER Coding Style with tabs, the layout of the Nette libraries and much of the hygiene), `symfony` or `laravel` (the preset laravel of Laravel Pint).
- **Sets say what is wanted beyond the looks**, and there are exactly six: `modernizations`, `deprecations`, `cleanup`, `correctness`, `types`, `compilerOptimizations`. A set goes into `use` after the standard (`use: [nette, modernizations]`) and decides nothing about the layout. A decision the project cannot use yet (its PHP is too old, it needs types, the library is missing) waits silently. No standard uses `modernizations` or `deprecations`.
- **Two PHP versions.** DressCode itself needs PHP 8.4 or newer. The version the code is written for is the lowest one `require.php` in `composer.json` allows, and no fix writes syntax that version lacks. The header line `Target PHP 8.2 from composer.json` says which one applies.
- **A risky fix** may change what the code does. It is never made without consent; DressCode reports it together with what would decide it.
- **The configuration is searched from the working directory upwards**, not from the checked files. Run DressCode from the project root, or name the file with `--config`.

## Running It

Use the project's `vendor/bin/dresscode` when there is one: only that copy sees the project's PHPStan and rule packages. Otherwise use the global one, `dresscode` on the PATH or `php "<composer global config home>/vendor/bin/dresscode"`. Run it as `php vendor/bin/dresscode …` from the directory holding `dresscode.neon`. When DressCode is not installed, tell the user; installing it is their decision, and the user can run the `install-php-fixer` skill for it.

| command | what it does |
|---|---|
| `check [paths]` | reports violations, writes nothing |
| `check --diff [paths]` | shows what `fix` would change, writes nothing |
| `fix [paths]` | fixes what it can and reports the rest |
| `config [--file <path>]` | the decisions as the run resolves them, in the shape of the file, each with the layer that set it and why it takes no effect where it does not |
| `explain [path]` | what a decision or a section is for, its values, its value here and in the five standards; every decision the configuration makes without a path |
| `catalogue` | every decision there is, those the configuration makes marked `*`, with the names of other tools each covers |
| `init` | measures the project's code and writes `dresscode.neon` |
| `baseline [paths]` | writes the violations a fix leaves into the baseline file |
| `migrate config [file]` | translates a PHP CS Fixer, PHP_CodeSniffer or Laravel Pint configuration, the one the project has without a file |
| `migrate suppressions [paths]` | rewrites `phpcs:` suppression comments to `dresscode:` |

Without paths, a command takes `paths` of the configuration; a directory containing configured paths is narrowed to them.

**Exit codes:** `0` clean; `1` violations remain (also a file that does not parse, a refused baseline, or more warnings than `--max-warnings`), which is a finding, not a failure of the tool; `2` a file failed (a rule threw, rules did not converge, the file changed during the fix); `3` a mistake on the command line or in the configuration (unknown key or decision, no configuration). Judge a run by its exit code, never by filtering its output.

Switches for one run, none of which touches the configuration file:

- `--set <path>=<value>` sets a decision, the value in NEON: `--set hygiene.is_null=forbidden`, `--set file.lineLength=100`.
- `--use <name>` adds a preset (a standard or a set) or a plugin over the configuration, or names the standard where there is no configuration: `--use psr12`, `--use modernizations`.
- `--severity <name>=<warning|error>` says how the violations of a decision, a section, a preset or a rule count: `--severity file.longLines=warning`.
- `--only <name>` narrows the run to a decision, a section or a preset, without changing any value: what it rewrites still comes out in the project's style, and it never brings back a decision the project left at `keep`. `types` is both a section and a set, so it is written `types.*` for the section or `dresscode/types` for the set; the bare name is refused.
- `--fix-risky[=<name>]` allows risky fixes, all of them or those of one decision, section or preset.
- `--no-cache` processes every file, `--format json` gives a document to parse.

## Ask the Tool

- `dresscode explain <path>`: what a decision wants, the values it takes, its value here with the layer that set it, and its page. Ask it before you change a decision or argue with a violation. `explain <section>` (`explain blankLines`, `explain upgrading`) lists every decision of a section.
- `dresscode check --diff --set <path>=<value> --only <path> <file>`: what a decision would do to a file, also one the project does not make.
- `dresscode config`: the truth about the configuration. After every change of it, run this and check that the decision shows the expected value; `config --file <path>` includes the overrides of that file. A decision that takes no effect says why (`no effect: php`, `no effect: types`).
- `dresscode catalogue`: the path of a decision you know as a rule of PHP CS Fixer or PHP_CodeSniffer (`single_quote` is `literals.quotes`).
- `dresscode <command> --help`: the switches of the installed version.

## Documentation

Each decision of the core has a page at `https://dresscode.run/decisions/{path}` (`https://dresscode.run/decisions/braces.position.class`), with examples of the code each value wants. Read it when `explain` is not enough, or when DressCode is not installed. A decision of a package has its page where the package says, which `explain` prints.

Other pages of https://dresscode.run worth knowing:

- `decisions/`: every decision, by section
- `presets-reference`: what `perCs`, `psr12`, `nette`, `symfony`, `laravel` and the six sets decide
- `configuration`, `cli`, `suppressing`: the configuration file, the command line, suppressions and the baseline
- `upgrading-php`, `upgrading-libraries`, `types`, `namespaces`: the upgrades, the types from PHPStan and the resolution of names
- `troubleshooting`: why a run is clean when it should not be, and what the messages mean

## What Needs the User's Consent

- **`fix` over more than the files of your task.** `check` is always safe. A project-wide fix belongs in a commit of its own from a clean working tree; offer it, do not run it unasked.
- **Accepting risky fixes**: an entry in `fixRisky`, or `--fix-risky`.
- **Setting a decision to `keep`, silencing a finding in the code, or writing a baseline.** Never silence or baseline violations your own change introduced; fix them.
- **Raising `require.php` in `composer.json`**, which turns on the decisions of a newer PHP across the project.
- **Installing anything**: DressCode, PHPStan, a rule package.

After a `fix` run through the shell, read again every file it reported as `rewritten` before editing it: the hook only reports its own runs, and an edit built on the old content fails or undoes the fix. `fix --format agent` over the files of your task tells you instead what it changed in each, as a diff, and what is left, as the hook does.

`fix --ask-risky`, which asks about each risky fix, needs an interactive terminal; you cannot run it, so suggest it to the user.

## Reading the Report

```
src/Cart.php
  error     8:47  Expected a line break after the opening brace.                                 braces.position.function
                  Then also, on line 9: Expected the statement indented by 3 tabs, 2 tabs found. indentation.unit
  risky     9:10  The `in_array()` call must pass `$strict = true`.                              mistakes.strictArgument
                  Risky because a strict search no longer finds a value of another type.
  warning  17:10  A plain string must be written with single quotes.                             literals.quotes

1 risky fix waits, the type is unknown: set `typeAnalysis: phpstan`. See https://dresscode.run/types#enable

FOUND  28 violations, 9 of them following from others, 2 warnings, a fix leaves 1 in 12 of 214 files
```

- Each violation gives the line and column, what the code should look like, and on the right the path of the decision it breaks. That path is all you need to change the decision, explain it or silence it.
- The summary says how many violations a fix leaves; only those need you or the user.
- An indented `Then also:` under a violation is what only fixing it brings about; it is settled together with it.
- `fix` lists each file it changed with `rewritten` and below it what the fixed text still violates, with positions in the fixed file.
- `risky` marks a fix waiting for consent, with `Risky because …` under it. One the types or `nameResolution` would decide counts as a warning; one only a person can decide counts as an error. `warning` marks a decision that `severity` makes only warn (`file.longLines` in `perCs` and `psr12`); warnings do not change the exit code unless `--max-warnings` is exceeded.

A risky fix waiting for consent is counted above the summary by what would decide it:

- **"the type is unknown: set `typeAnalysis: phpstan`"**: the types PHPStan knows would decide the fix. Propose `typeAnalysis: phpstan` to the user (see [the configuration reference](references/configuration.md#types-from-phpstan)); it needs `phpstan/phpstan` installed beside DressCode, which is again the user's decision. When the line says "check them with `fix --ask-risky`" instead, the types are on and do not tell, or PHPStan is missing.
- **"a name may reach a function of the namespace"**: an unqualified `is_null()` in a namespace could be a function of that namespace. `nameResolution: certain`, with the `namespaces` lists of what the project declares, removes the doubt; `dresscode init` measures the lists. Propose it.
- **"changes what the code does"**: only the user can decide. Show the effect with `dresscode check --fix-risky --diff`, which writes nothing, and with their consent run `fix --fix-risky=<path>`, add the decision to `fixRisky`, or mark a place that must stay as it is with `dresscode:ignore`.

## Violations a Fix Leaves

Some violations need a person: a class name in the wrong case (its users would have to change too), a line too long to break. Fix them by hand. Where the decision is wrong at that one place, silence that occurrence by its path and say why after `--`:

```php
$isEmpty = $value == null; // dresscode:ignore expressions.comparison.loose -- null and '' both mean no value here
```

- At the end of a line it covers that line; on a line of its own, the whole construct starting on the next line (a statement, a member, a single array item or argument).
- A section or a structure covers every decision under it (`dresscode:ignore upgrading`). Several paths are separated by commas. Without a path it silences everything; avoid that.
- `// dresscode:disable <path>` … `// dresscode:enable` covers a block, `// dresscode:ignoreFile` a file, `keep` in an override a path of the project.
- A silenced violation is not fixed either.
- A path that names no decision silences nothing, and the run says so. The comments `phpcs:ignore` and the like, with the rule names of PHP_CodeSniffer, are understood too, and `dresscode migrate suppressions` rewrites them to paths.

## Baseline

A baseline lets a project adopt a decision its code does not meet yet: the recorded violations stay quiet and every new one is reported. It holds only what a fix leaves, so the order is fixed:

1. `dresscode fix`; `baseline` refuses (exit `1`) while a fix would still change something.
2. `dresscode baseline` writes `dresscode-baseline.neon` beside the configuration.
3. `baseline: dresscode-baseline.neon` in the configuration makes it apply.

`OK  312 violations in the baseline in 214 files` means the violations are there, only recorded. A warning that entries of the baseline no longer match a violation means some got fixed; run `fix` and generate the baseline again. Regenerating it to swallow violations of new code defeats it.

## Configuration

A minimal `dresscode.neon` in the project root:

```neon
use:
	- nette
	- modernizations

paths:
	- src
	- tests
```

What else goes there, with the layering of presets, overrides and the command line, is in [the configuration reference](references/configuration.md). The mistakes to avoid:

- Every name is camelCase: keys, sections, decisions, presets and the words of values (`lineLength`, `nextLine`, `perCs`), except a decision named after a PHP function, class or method (`hygiene.is_null`, `upgrading.classes.Override`, `mistakes.__serialize`).
- The keys of the former models are errors now: `presets`, `extends`, `groups`, `plugins`, `rules` with rule names, `types`, `indent`, `lineLength`, `lineEnding`, `warnOnly`, the sections `cleanup` and `correctness`, and a rule name as a key. The standard, the sets and the plugins go to `use`, the types to `typeAnalysis`, the style to its decisions (`indentation.unit`, `file.lineLength`, `file.lineEnding`), warnings to `severity`; the former sections are `hygiene` and `mistakes`, while the sets `cleanup` and `correctness` keep their names. `rules` remains only for a rule class of the project itself.
- A rule name of another tool (`no_unused_imports`) is no key; `catalogue` gives its decision, and `dresscode migrate config` translates a whole foreign configuration.
- Values: a presence is `required`, `forbidden` or `optional`; a shape is its example in single quotes (`spacing: {call: 'foo($a, $b)'}`, unquoted NEON would read it as an entity); a list of words or shapes is a tolerance, each passing and the first written (`multiline: {edges: {array: [apart, attached]}}`); `yes` and `no` only for a parameter (`upgrading.packages: yes`). A word on a group applies to every key under it that takes it (`multiline: {trailingComma: required}`).
- `keep` leaves a thing alone: on a decision it stops enforcing it, on a section or a structure every decision under it. There is no `false` or `off`.
- A decision taking a list (`mistakes.debugOutputFunctions`) replaces the list below it, its default included, unless the list holds `...`, which stands for the list below (`[..., dump]`); a map (`upgrading.api.replacedClasses`) merges by name, `name: keep` withdraws an entry, and `!` after the key (`replacedClasses!:`) replaces the maps below.
- `nette` is two things: the standard in `use`, and the section of the decisions of the package `dresscode/upgrade-nette` (`nette: {linkArguments: required}`). A decision of a package always lives under the section its plugin names, and the plugin loads only where `use` names its package (`use: [nette, upgrade-nette]`), or in `dresscode upgrade`.
- A part of the project that needs different values gets an entry in `overrides`, not a decision turned off everywhere.
- An existing configuration is the project's: change it only as asked, never write a new one over it.

A project without a configuration: ask the user which standard to follow, or run `dresscode init`, which measures the indentation, the quotes and the shape of conditions from the code and writes the file (`perCs` unless `--use` names another standard, for a project formatted by Laravel Pint the standard of its Pint preset). It never overwrites an existing configuration.

## Upgrading PHP and Libraries

With the set `modernizations`, DressCode rewrites code for what the target PHP version brought (`str_contains()`, `?->`, `match`, `array_any()`, the pipe operator…); with `deprecations`, it rewrites what PHP or a library deprecated, the libraries described by upgrade packages such as `dresscode/upgrade-nette`. The procedure, the risky parts and the library packages are in [the upgrading guide](references/upgrading.md); read it before such a task.

## CI and Git Hooks

- CI runs `dresscode check`, not `fix`. On GitHub Actions the output turns into annotations by itself; elsewhere `--format checkstyle` or `--format json`. With `typeAnalysis: phpstan`, run the check in CI with `--no-cache`.
- A pre-commit hook passes the staged files: `dresscode check --format lines --skip-excluded <files>`. `lines` prints nothing when clean and one line per finding otherwise (`path:line:col: severity: message [decision]`), and `--skip-excluded` keeps `excludePaths` working for files named on the command line. `git show :<file> | dresscode check --stdin <file>` checks the staged content instead of the working tree.

## When Something Is Off

- **"No `dresscode.neon` or `dresscode.php` found"**: run it from the project root or pass `--config`; if the project really has none, see Configuration.
- **"No paths given and none configured"**: name the paths, or set `paths`.
- **Clean when it should not be**: the summary says the violations are in the baseline; the file is excluded (by default `vendor`, `node_modules`, `temp`, `tmp`, `log` and everything starting with a dot, plus `excludePaths`); the decision takes no effect, and `dresscode config` says why after it (`keep`, `no effect: php`, `no effect: types`, a missing package) or does not list it because nothing decides it; `severity` makes the decision only warn (`config` says `only warns`), which keeps the exit code at `0`; a suppression comment covers it; or the cache skipped the file, for example after `phpstan.neon` or a class other files use changed, so run with `--no-cache`.
- **"Decision … needs PHP >=8.3 and the target is >=8.2; skipped"**: the target comes from `composer.json`; set `targets: {php: '8.3'}` only when the code is written for another version or the header says it could not read one.
- **"Key … is unknown"**: a typo, a foreign name or a key of the former model; the message suggests the right one where it knows it.
- **"Decision … needs the types of the code"** (exit `3`): a decision the project wrote needs `typeAnalysis: phpstan` and PHPStan installed beside DressCode.
- **"The command line: Unknown preset `literals.quotes`"**: a decision is set with `--set path=value`; `--rule`, `--group` and `dresscode rules` no longer exist.
- **"Package … brings the plugin …, which loads only where `use` names the package or the plugin; skipped"**: add the package to `use` when its rules are wanted; its upgrading data apply without it.
- **"Name `types` is both a section of decisions and the preset `dresscode/types`"**: in `--only`, `fixRisky` or `severity`, write `types.*` for the section or `dresscode/types` for the set.
- **"Key … does not take `adopted`; write `required` or `keep`"**: a value of the former vocabulary (`adopted`, `replaced`, `warning` as a value); the message names the words the decision takes.
- **"The file changed while it was being fixed"** (exit `2`): something else saved it; run `fix` again.
- **"Rules … do not converge"** (exit `2`): two rules, usually of a plugin or of the project, undo each other; set one of their decisions to `keep` and tell the user.
- **The editor reformats the file back**: its own PHP formatter fights DressCode; the user should turn off formatting on save.

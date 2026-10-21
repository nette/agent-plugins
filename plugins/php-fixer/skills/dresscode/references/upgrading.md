# Upgrading PHP and Libraries with DressCode

DressCode rewrites code for a newer PHP version and for the new API of libraries, and the project's standard formats the result in the same run, so the diff holds the upgrade and nothing else. Both are an upgrade the user starts; no standard does them on its own.

## A Newer PHP

The decisions of the set `modernizations` write what the target PHP version brought, for code written for PHP 8.0 up to PHP 8.6:

```php
$isExternal = strpos($url, '://') !== false;                  // str_contains($url, '://')                        8.0
$author = $article === null ? null : $article->getAuthor();   // $article?->getAuthor()                           8.0
$slug = trim(strtolower(strip_tags($title)));                 // $title |> strip_tags(...) |> strtolower(...) …   8.5
$rating = max(0, min(5, $votes));                             // clamp($votes, 0, 5)                              8.6
```

Among others: `match` for a simple `switch`, constructor property promotion, first-class callables, `array_any()` and its siblings for a loop that only searches (8.4), `array_first()` and `array_last()` (8.5), partial application (8.6). Each is a decision under `upgrading` (`upgrading.syntax.match`, `upgrading.functions.clamp`), which `dresscode explain upgrading` lists. The set `deprecations` rewrites what PHP deprecated (an implicitly nullable parameter, `${name}` in a string, `utf8_encode()`…) and reports what it cannot rewrite.

Every decision knows the PHP version that gave it its construct and takes no effect below the target, so nothing the project cannot compile gets written; `dresscode config` marks those waiting for a newer target `no effect: php`.

The procedure:

1. Start from a clean working tree, so the diff shows only what DressCode did.
2. Raise `require.php` in `composer.json` to the new version. This is the user's decision.
3. Run `dresscode upgrade --modernize`, which lays the sets `modernizations` and `deprecations` and the types over the configuration and keeps of the style what the code already follows. What could not be fixed stays in the report, and the summary says what decides the risky fixes waiting for consent: the types, `nameResolution`, or the user.
4. To keep the sets in every later `check` and `fix`, add them to `use` of the configuration, unless they are there:
   ```neon
   use:
   	- nette
   	- modernizations
   	- deprecations
   ```
5. Supply what the summary asks for, after asking the user, and let the user decide the rest (see the risky fixes below).
6. Run the project's tests, and commit the result on its own.

A project may move one PHP version at a time, a commit for each; the decisions follow whatever `composer.json` currently allows. To split one upgrade into reviewable commits, narrow the runs: `dresscode upgrade --modernize --only modernizations`, then `--only deprecations`, or one decision at a time (`--only upgrading.syntax.match`).

To try the sets before they go into the file, `dresscode check --diff --use modernizations --only modernizations` shows what they would rewrite, in the project's style.

## Risky Rewrites

- `upgrading.syntax.match`: `switch` compares loosely and `match` strictly, so `case 1` also caught `'1'`. With types, DressCode makes the fix where only integers or enum cases are compared.
- `upgrading.functions.arraySearchFunctions`: `array_any()` and its siblings take only arrays and stop at the answer, where `foreach` also walks an object. The types decide most places.
- `upgrading.phpdoc.deprecated`, the `#[\Deprecated]` attribute for a `@deprecated` annotation: deprecated code starts reporting at runtime, so it is always risky.
- `upgrading.syntax.firstClassCallables` writing a partial application, and `upgrading.syntax.cloneWith`: they evaluate their arguments once, when made.
- `upgrading.functions.substringFunctions` (`str_contains()`) and other rewrites of a global function inside a namespace are risky until `nameResolution: certain`.

Show the user what these would do with `dresscode check --fix-risky --diff`; with their consent, accept a decision in `fixRisky` or for one run with `fix --fix-risky=<path>`, or suggest `fix --ask-risky`, which asks about each fix in an interactive terminal. A place that must stay as written gets `// dresscode:ignore <path> -- reason`.

DressCode never writes code that would not compile, even with `--fix-risky`.

## Decisions No Set Makes

Some upgrades add a guarantee rather than replace an older spelling, so no set makes them and the project writes them itself:

- `upgrading.classes.Override: required` adds `#[\Override]` (PHP 8.3) to a method overriding its parent's; it needs `typeAnalysis: phpstan`.
- `mistakes.readonlyProperty: required` writes `readonly` (8.1) on a property only the constructor writes, and `upgrading.phpdoc.readonly: required` the keyword for a `@readonly` annotation. Both are risky: code outside the file may write a property that is not private, and PHP throws where the annotation only made an analyser complain. The set `modernizations` itself makes a `final` class whose properties are all `readonly` a `readonly` class (8.2).

```neon
mistakes:
	readonlyProperty: required

upgrading:
	classes:
		Override: required
	phpdoc:
		readonly: required
```

## Libraries

What each version of a library renamed, moved or removed lives in the data files of an upgrade package for its ecosystem:

| ecosystem | package |
|---|---|
| Nette | `dresscode/upgrade-nette` |
| Symfony | `dresscode/upgrade-symfony` |
| Laravel | `dresscode/upgrade-laravel` |
| Dibi, Texy | `dresscode/upgrade-deegee` |

`dresscode upgrade` makes the upgrade with no configuration: it lays the set `deprecations` and the types over the configuration, runs the rules the installed upgrade packages carry beside their data, and keeps of the style only what the code already follows. Where the project requires a library itself whose upgrade package is missing, it offers the package: in a terminal it asks, elsewhere it stops and names the command, and `--yes` installs it without asking, which changes the `composer.json` of a project that holds DressCode itself, so only with the user's consent. Run `upgrade` again until it rewrites nothing. `--only upgrade-nette` narrows it to the maps of one package and the rules reading them, and `--ask-risky` or `--fix-risky` decide the risky rewrites.

To have every `check` and `fix` watch for the old API too, name the package in `use`, without its vendor, next to the set `deprecations`:

```neon
typeAnalysis: phpstan

use:
	- nette
	- upgrade-nette
	- deprecations
```

The data apply where `upgrading.packages` is `yes`, which the sets `deprecations` and `modernizations` set, and those of PHP itself where `upgrading.php.deprecatedApi` is `forbidden`, which `nette` and `deprecations` set and under which what they find is reported (so `--only`, `severity` and `dresscode:ignore` name it); the rules a package carries beside its data load only where `use` names it, which `upgrade` does by itself, and its files turn them on from the version that made them right. Most of the rewrites need the types, because only the types tell whose method a call calls; without them only classes, functions and annotations get rewritten. Beyond the data, `upgrading.declarations.deprecatedMember` and `upgrading.declarations.deprecatedClass`, both in the set `deprecations`, rewrite what a library marks `@deprecated use …` in its code, again with types. Rules of a package that need logic decide under the section of the package (`nette: {linkArguments: required}`), which the project writes only to turn one off with `keep`. `--only` takes a package of data (`dresscode/upgrade-nette`), a library (`nette/forms`, `php`) or a decision. The code of a class is never rewritten by the entries about that class itself, so a library upgrading its own code keeps its implementation; its tests of a deprecated API, which exercise it on purpose, need an entry in `overrides` that leaves them alone.

Every rewrite works on the lowest version the constraint allows; the installed version never decides. A data section is rewritten once that lowest version reaches it (or the version its entry names in `from`), a later section the constraint still allows is only reported (`… is deprecated: replaced by X, since acme/lib 4.0`), and a library the project does not require, or requires without a lower bound, is only reported. `dresscode config` shows a `Packages` line with each library, `php` among them, its lowest version, its data file and the versions the data lead to next, `(reported)` marking those reported already.

Upgrade one major version at a time, each step with the user's consent: raise the lower bound of the constraint in `composer.json`, `composer update --prefer-lowest` so that the installed version is the one the code must work with and its `@deprecated` annotations count (DressCode ignores those of a package installed newer than the lower bound, and the `Lowest` line of `upgrade` names such packages), `dresscode upgrade`, run the tests and PHPStan, commit; then `composer update` back to the newest versions, or the next step. Converting from very old versions in one go also works, but without the annotations of the skipped versions.

Two things the rewrite cannot reach:

- **A class of the project extending a class the new version deletes.** After the update its parent is unknown and calls through `$this` are not rewritten. Convert such code before `composer update`, while the old version is installed, by naming the new version in `targets`:
  ```neon
  targets:
  	nette/application: '3.2'
  ```
  DressCode then writes the API of 3.2 over the installed older version, and only reports a class the old version does not have yet; the next `upgrade` after `composer update` completes it. Remove the item from `targets` afterwards, or it keeps overriding `composer.json`.
- **A changed meaning.** The data fix the spelling: a property renamed whose value also changed keeps the old comparison. PHPStan and the tests find such places.

Data rewrites are not risky, except where the rewritten expression would be evaluated differently (a default moved behind `??`, a property read on the left of `??`, an unqualified function in a namespace); those wait for `fixRisky`. What the data forbid (`upgrading.api.forbiddenClasses`, `forbiddenMembers`, `forbiddenFunctions`) is only reported, with a sentence saying what to write instead; that is work for you.

## A Rename Inside the Project

The same decisions rewrite the project's own renames, given a map in the configuration:

```neon
upgrading:
	api:
		replacedClasses:
			App\Model\Customer: App\Model\Client
		replacedFunctions:
			formatPrice: App\Utils\formatPrice
```

`dresscode fix` then rewrites every occurrence: imports, types, `new`, static access, attributes. A function name inside a namespace is risky until `nameResolution: certain`, as above.

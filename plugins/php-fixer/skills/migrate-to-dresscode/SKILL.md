---
name: migrate-to-dresscode
description: Moves the php-fixer plugin of a project from the retired nette/coding-standard (ECS) to DressCode. Use when the php-fixer hook says the plugin now formats PHP with DressCode, which is not installed, and the user agrees to migrate. Never run it without the user's consent.
---

# Migrate to DressCode

Version 2 of the php-fixer plugin formats PHP with [DressCode](https://github.com/dg/dresscode). Until DressCode is installed, its hook still runs the `nette/coding-standard` of version 1 and tells you once a session to offer this migration. The migration installs DressCode, gives the project a `dresscode.neon` and shows what the new standard would change.

Run commands with the client's available shell tool and ask questions through its available question tool or chat. Tell the user what each step will do before it runs.

---

## Step 0: Pre-flight Checks

1. **PHP 8.4+**: `php --version`. If it fails or is older, stop and tell the user DressCode needs PHP 8.4.
2. **Composer**: `composer --version`. If it fails, stop and point to https://getcomposer.org/.
3. **The project root**: the directory with the project's `composer.json`, nearest above the files being edited. Without one, the project root is the repository root and DressCode can only be installed globally.

---

## Step 1: Installation

DressCode goes **into the project** when the project has PHPStan (`phpstan/phpstan` in `require` or `require-dev` of its `composer.json`), so that it uses the project's PHPStan and its extensions for the types of the code. Otherwise it goes **globally, together with PHPStan**: DressCode takes the types from the PHPStan installed beside it.

- With PHPStan, in the project root:
  ```bash
  composer require --dev dresscode/dresscode
  ```
- Without it, first update the old fixer when it is installed (`composer global show nette/coding-standard` succeeds), because the version locked in the global installation conflicts with DressCode:
  ```bash
  composer global update nette/coding-standard
  ```
  Then:
  ```bash
  composer global require dresscode/dresscode phpstan/phpstan
  ```

When the global installation still fails on a conflict with `nette/coding-standard`, offer to remove that first (Step 4) and try again.

---

## Step 2: Configuration

When the project root already has `dresscode.neon` or `dresscode.php` (or a `.dist` variant), keep it and skip this step. Otherwise write `dresscode.neon` there:

```neon
use: nette

paths:
	- app
	- tests

typeAnalysis: phpstan
```

`paths` lists the directories with the project's PHP code, which a run without file names checks; take them from the `autoload` and `autoload-dev` sections of `composer.json` plus the tests directory, and list only those that exist. When the tests are `.phpt` files (Nette Tester), add:

```neon
fileExtensions:
	- php
	- phpt
```

`typeAnalysis: phpstan` lets DressCode use the types of the code; it needs PHPStan beside DressCode, which Step 1 installed or the project already has.

---

## Step 3: What Changes

Run a check from the project root, with `vendor/bin/dresscode` or, for the global installation, `php "<composer global config home>/vendor/bin/dresscode"`:

```bash
vendor/bin/dresscode check
```

Exit code 1 only means there are violations. Summarize for the user how many files the standard would change and what kind of changes they are. DressCode's Nette standard differs from `nette/coding-standard` in details, so the hook will now also touch such places in the files you edit.

Do **not** run `dresscode fix` over the project on your own. Offer it; the user may prefer to reformat the whole project in one commit of its own.

Violations marked as risky (such as a missing `declare(strict_types=1)`) are left by `fix`, because fixing them may change what the code does. Explain that to the user. Only with their explicit consent for one decision run `dresscode fix --fix-risky=<path>` with the path the report shows on the right, for example `--fix-risky=file.strictTypes.declaration`, and show the diff; never allow all risky fixes at once. `dresscode fix --ask-risky`, which asks about each one, needs an interactive terminal, so leave it to the user.

---

## Step 4: The Old Fixer

The hook no longer needs the global `nette/coding-standard`. Ask whether to remove it, and mention that other projects of the user or their own scripts may still use it. Only if the user agrees:

```bash
composer global remove nette/coding-standard
```

---

## Done

From the next edit on, the hook formats PHP files with DressCode by the project's `dresscode.neon`. Projects without a `dresscode.neon` get the Nette coding standard.

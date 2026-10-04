---
name: php-auto-fixer
description: "CRITICAL: Read BEFORE writing or modifying any PHP file. A PostToolUse hook runs DressCode (`dresscode fix`) on PHP files after Claude Code Edit/Write or Codex apply_patch (when hooks are enabled and trusted) and reformats it by the project's coding standard. The hook tells you when it rewrote the file and what it could not fix. The fixer removes unused `use` statements, so add a `use` together with the code that references it, never alone in an earlier edit. This skill should be used whenever creating new PHP files, editing existing PHP code, adding methods, refactoring, or fixing bugs in PHP - even for small one-line changes."
---

# PHP Auto-Fixer

A PostToolUse hook runs `dresscode fix` after Claude Code `Edit`/`Write` or Codex `apply_patch` operations. A patch may edit multiple PHP files; the hook processes each one. In Codex, hooks must first be reviewed and trusted via `/hooks`. Edits made through shell commands are not covered, so run the project's fixer explicitly after those edits.

## Which Standard Applies

The hook runs DressCode the way the project runs it: with the nearest `dresscode.neon` (or `dresscode.php`) found upwards from the edited file, so the standard and the sets it uses, its decisions, `overrides`, the target PHP version and the baseline of the project all apply. A project without a configuration file gets the Nette coding standard (`dresscode/nette`).

Two things differ from a plain `dresscode fix`: a file the configuration's `excludePaths` leaves out is left as written, and `mistakes.debugOutput` is `keep`, so the debugging calls it would comment out (`var_dump()`, `print_r()`, `var_export()` and whatever `mistakes.debugOutputFunctions` adds) stay in the code while you are editing.

## What the Hook Tells You

- **"DressCode changed `<file>`; the changes below are already in the file"**: the file on disk no longer matches what you wrote. The diff that follows is what the fixer did, with the imports it removed named above it; build your next Edit on the file as the diff leaves it, or read the file again when the message says the changes are too many to show.
- **"Left in `<file>`, at the lines of the file as it is now"**: violations the fixer did not fix, each with its line, the message, the path of the decision it breaks (`literals.quotes`) and what to do about it. Fix them by hand, or leave them when the code is right and the decision is not (see below).
- **An error without either of those**: the run itself failed, usually a broken `dresscode.neon` (unknown key or decision, unknown preset, missing extension class). Fix the configuration or tell the user.
- **"DressCode did not finish on <file>"**: the run was stopped; read the file again before editing it further.
- **"The php-fixer plugin now formats PHP with DressCode, which is not installed"**: the user still has `nette/coding-standard` of plugin 1.x, which formatted the file this time. Tell the user and, if they agree, run the `migrate-to-dresscode` skill.
- Nothing: the file already followed the standard.

## Editing Order for `use` Statements

The fixer removes any `use` statement not referenced in the file. A `use` added in one edit and the code that needs it in the next therefore loses the import in between; the hook names the import it removed, but the import is gone all the same.

Always add `use` statements in the same Edit as the code that references them, or add the code first and the `use` second. Never add `use` statements alone in a separate Edit before the code.

## What the Fixer Does

- Removes unused `use` statements and sorts the rest
- Fixes indentation, spacing, blank lines and line breaks
- Enforces the project's standard (PER Coding Style with Nette modifications by default, such as tabs and `declare(strict_types=1)` on the line of the opening tag)

When the hook is active, let the fixer handle formatting and removal or sorting of `use` statements. If the hook is unavailable or the edit was made through the shell, run the project's fixer explicitly.

## Silencing a Finding

When a reported violation is deliberate, silence that one occurrence in the code by the path of its decision rather than setting the decision to `keep` for the project:

```php
$a = "double quotes here"; // dresscode:ignore literals.quotes -- the reason
```

## Excluding Paths

The fixer is configured only by the project's `dresscode.neon`, never by `.nette-claude.json`. To stop it from touching certain paths (e.g. `fixtures` folders), list them in `excludePaths`:

```neon
use: nette

excludePaths:
	- fixtures*
```

`vendor`, `node_modules`, `temp`, `tmp`, `log` and hidden directories (`.*`) are excluded by default, even without a configuration file.

## Running DressCode Yourself

To check or fix more than the edited file, change the configuration, decide risky fixes, write a baseline or upgrade the code to a newer PHP or library version, use the `dresscode` skill.

## Installation

If the fixer is not installed, use the `install-php-fixer` skill explicitly (`/php-fixer:install-php-fixer` in Claude Code, or select the skill in Codex).

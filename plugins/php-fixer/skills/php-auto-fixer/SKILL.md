---
name: php-auto-fixer
description: "CRITICAL: Read BEFORE writing or modifying any PHP file. A PostToolUse hook automatically runs nette/coding-standard (ECS) on PHP files after Claude Code Edit/Write or Codex apply_patch (when hooks are enabled and trusted). The fixer removes unused `use` statements - so never add `use` statements in a separate edit before the code that references them. Always include `use` imports in the same edit as the referencing code, or add the code first then `use` statements. This skill should be used whenever creating new PHP files, editing existing PHP code, adding methods, refactoring, or fixing bugs in PHP - even for small one-line changes."
---

# PHP Auto-Fixer

A PostToolUse hook runs `ecs fix` after Claude Code `Edit`/`Write` or Codex `apply_patch` operations. A patch may edit multiple PHP files; the hook processes each one. In Codex, hooks must first be reviewed and trusted via `/hooks`. Edits made through shell commands are not covered, so run the project's fixer explicitly after those edits.

## Editing Order for `use` Statements

The fixer removes any `use` statement not referenced in the file. A `use` added in one edit and the code that needs it in the next therefore loses the import in between.

Always add `use` statements in the same Edit as the code that references them, or add the code first and the `use` second. Never add `use` statements alone in a separate Edit before the code.

## What the Fixer Does

- Removes unused `use` statements and sorts the rest
- Fixes indentation, spacing and line breaks
- Enforces PSR-12 with Nette modifications (e.g., no space before parentheses in arrow functions)

When the hook is active, let the fixer handle formatting and removal or sorting of `use` statements. If the hook is unavailable or the edit was made through the shell, run the project's fixer explicitly.

## When the Fixer Reports Errors

When ECS cannot fix everything, the hook reports "Could not fix all coding standard issues in <file>" followed by the remaining violations; fix them by hand. A file that is not valid PHP is skipped without a report.

## Excluding Paths

To stop the fixer from touching certain paths (e.g. `fixtures` folders), add a `.nette-claude.json` file to the project root. It is shared by all Nette hooks in both Claude Code and Codex; each top-level key is a hook name:

```json
{
	"fix-php-style": {
		"exclude": ["fixtures*"]
	}
}
```

Patterns are gitignore-like, relative to the config file: a pattern without a slash (`fixtures`, `fixtures*`) matches a segment at any depth; a pattern with a slash (`tests/**/temp`) is a glob anchored to the config directory. The file is looked up upwards from the edited file.

## Installation

If the fixer is not installed, use the `install-php-fixer` skill explicitly (`/php-fixer:install-php-fixer` in Claude Code, or select the skill in Codex).

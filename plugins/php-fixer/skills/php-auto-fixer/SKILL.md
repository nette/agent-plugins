---
name: php-auto-fixer
description: "CRITICAL: Read BEFORE writing or modifying any PHP file. A PostToolUse hook automatically runs nette/coding-standard (ECS) on PHP files after Claude Code Edit/Write or Codex apply_patch (when hooks are enabled and trusted). The fixer removes unused `use` statements - so never add `use` statements in a separate edit before the code that references them. Always include `use` imports in the same edit as the referencing code, or add the code first then `use` statements. This skill should be used whenever creating new PHP files, editing existing PHP code, adding methods, refactoring, or fixing bugs in PHP - even for small one-line changes."
---

# PHP Auto-Fixer

A PostToolUse hook runs `ecs fix` after Claude Code `Edit`/`Write` or Codex `apply_patch` operations. A patch may edit multiple PHP files; the hook processes each one. In Codex, hooks must first be reviewed and trusted via `/hooks`. Edits made through shell commands are not covered, so run the project's fixer explicitly after those edits.

## Editing Order for `use` Statements

The fixer removes any `use` statement not referenced in the file. This creates a timing trap between consecutive edits:

1. Edit adds `use App\Model\Foo;`
2. Hook runs → `Foo` is not used anywhere → **removes the `use` statement**
3. Next Edit adds code using `Foo` → **fails because the `use` is gone**

### The Rule

Always add `use` statements in the same Edit as the code that references them. Alternatively, add the code first, then add the `use` statement in a follow-up edit.

Never add `use` statements alone in a separate Edit - they will be removed before the next edit adds the referencing code.

### Safe Patterns

**Single edit with both `use` and code** (preferred):

```php
use App\Model\UserRepository;

public function getUsers(UserRepository $repo): array
{
    return $repo->findAll();
}
```

**Code first, `use` second** (also safe):

1. First Edit: add the method body referencing `UserRepository`
2. Second Edit: add `use App\Model\UserRepository;` - now it's referenced, fixer keeps it

**`use` first, code second** (broken):

1. First Edit: add `use App\Model\UserRepository;` - fixer removes this immediately
2. Second Edit: add method body - `UserRepository` undefined

### Multiple Classes in One Edit

When adding code that references several new classes, include all their `use` statements in the same edit. Do not split `use` statements and code across separate edits.

## What the Fixer Does

- Removes unused `use` statements
- Sorts `use` statements alphabetically
- Fixes indentation, spacing, and line breaks
- Enforces PSR-12 with Nette modifications (e.g., no space before parentheses in arrow functions)

When the hook is active, let the fixer handle formatting. If the hook is unavailable or the edit was made through the shell, run the project's fixer explicitly.

## What Not to Do

- Do not remove `use` statements manually - the fixer handles unused imports
- Do not sort `use` statements manually - the fixer sorts them
- Do not fix whitespace or indentation manually - the fixer fixes it

## When the Fixer Reports Errors

The hook exits with an error when ECS cannot auto-fix all issues. Common causes:

- **PHP syntax error** in the file - fix the syntax first, fixer will run again on the next edit
- **Conflicting rules** - rare, usually resolved by re-running (edit the file again)

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

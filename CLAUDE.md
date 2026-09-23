# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains Claude Code plugins for the Nette Framework ecosystem:

- **`plugins/nette/`** - For developers building Nette Framework applications (skills only)
- **`plugins/nette-lint/`** - Automatic validation hooks for PHP, Latte, NEON and JavaScript/TypeScript
- **`plugins/nette-dev/`** - For Nette Framework contributors (coding standards and conventions)
- **`plugins/php-fixer/`** - Optional automatic PHP style fixing using nette/coding-standard

## Plugin Structure

Each plugin contains:
- `.claude-plugin/plugin.json` - Plugin metadata
- `skills/` - Contextual documentation that activates based on conversation

Some plugins also include:
- `hooks/` - PostToolUse hooks for file validation/fixing (`nette-lint`, `php-fixer`)

Duplicated across plugins:
- `hooks/hook-config.php` - Helper required by each plugin's hooks (`require __DIR__ . '/hook-config.php'`). Reads the project's `.nette-claude.json` and locates project tools/configs via `findUpwards()`. Plugins install **independently** (each into its own `cache/<marketplace>/<plugin>/<version>/` directory - a sibling `shared/` folder is NOT copied), so this file is **copied** into every hook-carrying plugin (`nette-lint`, `php-fixer`). Keep the copies in sync.

## Testing Plugins Locally

Enable plugins in development:
```bash
# In a project directory, add this repo as a local plugin source
claude code --plugin /path-to/agent-plugins/plugins/nette
```

## Hook Scripts

Standalone PHP scripts run on PostToolUse (`Edit|Write`). Each one:
1. Reads JSON input from stdin (`file_path`) via `file_get_contents('php://stdin')` + `json_decode`
2. Checks the file extension and skips non-matching files (exit 0)
3. Skips paths excluded in the project's `.nette-claude.json` via `isExcluded()` from the plugin's own `hooks/hook-config.php`
4. Locates its project tool/config (`latte-lint`, `vendor/bin/neon-lint`, ESLint config) via `findUpwards()` - searched upwards from the edited file, so monorepo sessions rooted above the app directory work too; the search stops one level above the nearest `.git` root
5. Runs its tool, then exits 0 on success or exit 2 on error (with stderr output)

Hooks run **in parallel and in non-deterministic order**, so each must be robust on its own - do not rely on one hook running before another. For example `fix-php-style` runs `php -l` itself and silently skips invalid PHP instead of depending on `lint-php`.

### Per-project configuration: `.nette-claude.json`

Read exclusively by the hooks, looked up upwards from the edited file (like `.gitignore`). Each top-level key is a hook name (`lint-php`, `lint-latte`, `lint-neon`, `lint-json`, `lint-js`, `fix-php-style`) with an `exclude` list of gitignore-like patterns resolved relative to the config directory. `lint-json` additionally accepts a `jsonc` list marking extra files as JSONC (comments + trailing commas allowed):

```json
{
	"fix-php-style": {
		"exclude": ["fixtures*"]
	}
}
```

## Skill Files

Each skill is a directory containing a `SKILL.md` file with YAML frontmatter:
```yaml
---
name: skill-name
description: When to activate this skill (used for contextual matching)
---
```

Detailed reference documentation goes into the `references/` subdirectory within each skill.

## Publishing

Plugins are published via the marketplace configuration in `.claude-plugin/marketplace.json`. Users install with:

```
/plugin marketplace add nette/agent-plugins
/plugin install nette@nette
```

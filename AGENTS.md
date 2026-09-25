# Repository Instructions

This file provides shared guidance to Claude Code and Codex when working with this repository. `CLAUDE.md` imports it; keep the instructions here.

## Project Overview

This repository contains plugins for the Nette Framework ecosystem, shared by Claude Code and Codex:

- **`plugins/nette/`** - For developers building Nette Framework applications (skills only)
- **`plugins/nette-lint/`** - Automatic validation hooks for PHP, Latte, NEON and JavaScript/TypeScript
- **`plugins/nette-dev/`** - For Nette Framework contributors (coding standards and conventions)
- **`plugins/php-fixer/`** - Optional automatic PHP style fixing using nette/coding-standard

## Plugin Structure

Each plugin contains:
- `.claude-plugin/plugin.json` - Plugin metadata, supported by both clients
- `skills/` - Contextual documentation that activates based on conversation

Some plugins also include:
- `hooks/` - PostToolUse hooks for file validation/fixing (`nette-lint`, `php-fixer`)

Duplicated across plugins:
- `hooks/hook-input.php` - Adapts Claude Code `tool_input.file_path` and Codex `apply_patch` (`tool_input.command`, relative to event `cwd`) to the single-file hooks. Direct edits and one-file patches run without a subprocess. Multi-file patches run only matching extensions, preserve the PHP ini, and aggregate errors and rewrite feedback. Do not resolve symlinks when finding per-project configuration. Keep both copies identical; `tests/hook-input.phpt` checks this.
- `hooks/hook-config.php` - Helper required by each plugin's hooks (`require __DIR__ . '/hook-config.php'`). Reads the project's `.nette-claude.json` and locates project tools/configs via `findUpwards()`. Plugins install **independently** (each into its own `cache/<marketplace>/<plugin>/<version>/` directory - a sibling `shared/` folder is NOT copied), so this file is **copied** into every hook-carrying plugin (`nette-lint`, `php-fixer`). Keep the copies in sync.

## Testing Plugins Locally

Enable plugins in development:
```bash
# In a project directory, add this repo as a local plugin source
claude --plugin-dir /path-to/agent-plugins/plugins/nette
```

Codex uses the same marketplace and plugin manifests (verified with CLI 0.156.1); do not duplicate them into a second catalog without a demonstrated compatibility need:

```sh
codex plugin marketplace add /path-to/agent-plugins
codex plugin add nette@nette
```

For hook plugins, review and trust their definitions with `/hooks`. Run the PHP regression suite with `php vendor/bin/tester tests -s`. `docs/compatibility.md` records the client checks and their limits.

## Hook Scripts

Standalone PHP scripts run on PostToolUse (`Edit|Write`, which also matches Codex `apply_patch`). Shell-based edits are not covered. Each one:
1. Reads JSON input from stdin through `readHookFile()` in `hook-input.php`; multi-file patches are dispatched to the same script with individual `file_path` inputs
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

Keep skill bodies portable between clients. Preserve manual-only installation using Claude Code's `disable-model-invocation` and Codex's `agents/openai.yaml` policy. Do not assume a particular shell syntax or client-specific tool name in shared instructions.

Where projects legitimately differ (where secrets live, how the app is deployed), a skill does not prescribe one way: it tells the model to follow the project's existing convention and, when there is none, to offer the user the options. Nette's own conventions (directory structure, database schema, the frontend stack) remain prescriptive.

## Publishing

Both clients use the marketplace configuration in `.claude-plugin/marketplace.json`. Claude Code users install with:

```
/plugin marketplace add nette/agent-plugins
/plugin install nette@nette
```

Codex users run `codex plugin marketplace add nette/agent-plugins` and `codex plugin add nette@nette`. Bump versions in the manifests of changed plugins before release so cached installations can be refreshed.

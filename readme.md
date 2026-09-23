# Nette Plugins for Claude Code

Plugins for [Claude Code](https://claude.com/product/claude-code) – the AI-powered coding assistant by Anthropic. These plugins give Claude deep knowledge of the Nette Framework ecosystem, including best practices, coding conventions, and automatic file validation.

<img width="1536" height="601" alt="image" src="https://github.com/user-attachments/assets/8b9443b6-9f37-418d-9212-3f4fd4356961" />

## Installation

First, add the Nette marketplace to Claude Code (and enable auto-update):

```
/plugin marketplace add nette/agent-plugins
```

Then install the plugin:

```
/plugin install nette@nette
```

For automatic validation of PHP, Latte, NEON, JSON and JavaScript files after each edit:

```
/plugin install nette-lint@nette
```

Optionally, Nette Framework contributors can also install:

```
/plugin install nette-dev@nette
```

For automatic PHP code style fixing:

```
/plugin install php-fixer@nette
/install-php-fixer
```

## Plugins

### `nette` – For Application Developers

Best practices and conventions for building applications with Nette Framework — a broad set of skills covering all major areas of Nette development.

| Skill | Description |
|-------|-------------|
| **nette-architecture** | Application architecture, presenters, modules, directory structure |
| **nette-configuration** | DI container, services.neon, autowiring |
| **nette-database** | Database conventions, entities, Selection API, queries |
| **nette-forms** | Form controls, validation, rendering, create/edit patterns |
| **nette-schema** | Data validation and normalization with Expect class |
| **nette-tester** | Nette Tester usage, test structure, assertions |
| **nette-utils** | Utility classes: Arrays, Strings, Image, Finder, DateTime, Json, Validators |
| **frontend-development** | Vite, ESLint, Tailwind, Nette Assets integration |
| **latte-templates** | Latte templating system, layouts, filters, template classes |
| **neon-format** | NEON data format syntax, mappings, sequences, entities |
| **tracy-debugging** | Debugging PHP errors via Tracy: BlueScreen, Tracy Bar, dump, console output |

### `nette-lint` – Automatic Validation

Validates files after every edit and reports errors straight back to Claude:

| Hook | Checks |
|------|--------|
| **lint-php** | PHP syntax via `php -l` after every `.php`/`.phpt` edit |
| **lint-latte** | Latte templates via the project's `latte-lint` |
| **lint-neon** | NEON syntax via the project's `neon-lint` |
| **lint-json** | JSON/JSONC syntax via the bundled `seld/jsonlint` (reports the exact line; comments and trailing commas are tolerated in `.jsonc`, `tsconfig.json`, `.vscode/*.json`) |
| **lint-js** | ESLint `--fix` on `.js/.ts/.mjs/.mts` (only if the project has an ESLint config) |

Project tools and configs (`latte-lint`, `vendor/bin/neon-lint`, ESLint config) are searched upwards from the edited file, so monorepos where the session runs above the app directory work too. The search stops one level above the nearest `.git` root.

### `nette-dev` – For Framework Contributors

Coding standards and conventions for contributing to the Nette Framework itself.

| Skill | Description |
|-------|-------------|
| **php-coding-standards** | PHP formatting, naming conventions, code style |
| **php-doc** | phpDoc documentation best practices |
| **commit-messages** | Commit message conventions for Nette repositories |
| **phpstan-analysis** | PHPStan error resolution, baselines, type tests, common Nette patterns |

### `php-fixer` – Automatic PHP Style Fixing

Optional plugin that automatically fixes PHP code style after each edit using [nette/coding-standard](https://github.com/nette/coding-standard).

## Configuration

Project-level behavior of these plugins is configured through a `.nette-claude.json` file in your project, read exclusively by the plugins' own hooks. It is looked up upwards from the edited file (like `.gitignore`), so in a monorepo each package can have its own.

### Excluding files from hooks

The validation and fixing hooks run automatically after every edit. To exclude certain paths (typically `fixtures` folders with intentionally broken templates, NEON or PHP), give a hook an `exclude` list. Each top-level key is a hook name:

```json
{
	"fix-php-style": {
		"exclude": ["fixtures*"]
	},
	"lint-latte": {
		"exclude": ["tests/**/broken"]
	}
}
```

Available hooks: `lint-php` (`.php/.phpt`), `lint-latte` (`.latte`), `lint-neon` (`.neon`), `lint-json` (`.json/.jsonc`), `lint-js` (`.js/.ts`), `fix-php-style` (`.php/.phpt`).

`lint-json` treats `.jsonc`, `tsconfig*.json`, `jsconfig*.json`, `devcontainer.json` and anything under `.vscode/` as JSONC (comments and trailing commas allowed). To mark additional files as JSONC, add a `jsonc` list of the same gitignore-like patterns:

```json
{
	"lint-json": {
		"jsonc": ["config/*.json"]
	}
}
```

Pattern semantics (gitignore-like), resolved relative to the directory containing `.nette-claude.json`:

- a pattern **without** a slash (`fixtures`, `fixtures*`) matches a path segment of that name at **any depth**;
- a pattern **with** a slash (`tests/**/broken`) is a glob **anchored to the config directory**;
- `*` matches within a single segment, `**` spans directories;
- matching a directory excludes its contents too.

## Usage

Skills are automatically activated based on conversation context. For example:

- Ask about "presenter structure" → activates `nette-architecture`
- Ask about "form validation" → activates `nette-forms`
- Ask about "Latte templates" → activates `latte-templates`
- Etc..

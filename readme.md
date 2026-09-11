# Nette Plugins for Claude Code and Codex

Plugins for [Claude Code](https://claude.com/product/claude-code) – the AI-powered coding assistant by Anthropic. These plugins give Claude deep knowledge of the Nette Framework ecosystem, including best practices, coding conventions, and automatic file validation.

The same skills are also available in OpenAI Codex, with dedicated plugin manifests and skill UI metadata. Automatic validation and fixing hooks currently target Claude Code; their compatibility with Codex has not been verified.

<img width="1536" height="601" alt="image" src="https://github.com/user-attachments/assets/8b9443b6-9f37-418d-9212-3f4fd4356961" />

## Installation

### Claude Code

First, add the Nette marketplace to Claude Code (and enable auto-update):

```
/plugin marketplace add nette/claude-code
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

### Codex

From a local checkout of this repository, register the marketplace:

```bash
codex plugin marketplace add /absolute/path/to/claude-code
```

The marketplace name comes from the catalog's `name` field and is `nette`, regardless of the checkout directory or Git branch. To list and install plugins from the CLI:

```bash
codex plugin list --marketplace nette --available --json
codex plugin add nette@nette --json
```

Use `codex plugin marketplace list --json` to check registered marketplace names. Filtering by an unregistered name returns an empty plugin list.

Open the plugin directory in the Codex app, select the `nette` marketplace, and install `nette` for application development or `nette-dev` for framework contributions. Start a new conversation after installation.

Codex can reuse the existing `.claude-plugin/marketplace.json`; no second catalog is needed. Each plugin has a `.codex-plugin/plugin.json` manifest, and each skill has `agents/openai.yaml` with its display name and short description. Both assistants share the original `SKILL.md` files and references.

For example, ask Codex to use `$nette-forms` to build a form or `$phpstan-analysis` to investigate a PHPStan error. The `php-fixer` plugin also provides `$install-php-fixer`, which remains explicitly invoked only.

The install skill preserves Claude Code's `disable-model-invocation: true` and declares Codex's `policy.allow_implicit_invocation: false` in `agents/openai.yaml`. The bundled Codex plugin validator rejects the former flag, so `php-fixer` still requires an installation check in the target Codex client. The `nette`, `nette-dev`, and `nette-lint` manifests pass that validator.

The `nette-lint` plugin contains only hooks, with no skills. Its hooks and the automatic fixer use Claude Code's `Edit|Write` events and input format. Installing metadata does not adapt those events to Codex tools; do not assume validation or formatting runs automatically in Codex. Run the relevant project tools explicitly when needed.

See the [OpenAI plugin documentation](https://developers.openai.com/plugins/build/plugins) for supported clients, marketplace setup, and hook requirements.

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

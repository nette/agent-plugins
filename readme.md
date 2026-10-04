# Nette Plugins for Claude Code and Codex

Plugins for [Claude Code](https://claude.com/product/claude-code) and [Codex](https://developers.openai.com/codex). They provide knowledge of the Nette Framework ecosystem, including best practices, coding conventions, and automatic file validation. Both clients use the same skills, hooks, and project configuration.

<img width="1536" height="601" alt="image" src="https://github.com/user-attachments/assets/8b9443b6-9f37-418d-9212-3f4fd4356961" />

## Installation

### Claude Code

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
/php-fixer:install-php-fixer
```

The skills come with the relevant pages of the Nette documentation, which Claude reads when it needs details. The plugin lives outside your project, so Claude Code asks for permission the first time it reads one of its files. To skip the prompt, allow reading the plugin directory in your settings:

```json
{
	"permissions": {
		"allow": ["Read(~/.claude/plugins/cache/nette/**)"]
	}
}
```

### Codex

Add the marketplace and install the application-development skills:

```sh
codex plugin marketplace add nette/agent-plugins
codex plugin add nette@nette
```

Install any of the optional plugins you need:

```sh
codex plugin add nette-lint@nette
codex plugin add nette-dev@nette
codex plugin add php-fixer@nette
```

Start a new session after installation. For `php-fixer`, explicitly select the `install-php-fixer` skill to install DressCode if it is not available in your project or globally.

For automatic validation and fixing, open `/hooks` in Codex CLI and review and trust the installed hook definitions. Installing a plugin alone does not trust its hooks. Changed definitions require another review. See [Codex hook setup](https://learn.chatgpt.com/docs/hooks).

The existing `.claude-plugin/marketplace.json` and plugin manifests are supported by both clients; no separate Codex catalog is needed. See [plugin packaging compatibility](https://developers.openai.com/plugins/build/plugins).

### Requirements and edit coverage

Skills need no PHP installation. Hooks require PHP 8.0+ on `PATH`, plus the relevant project tools listed below. DressCode requires PHP 8.4+.

Hooks run after Claude Code `Edit`/`Write` and Codex `apply_patch` calls. Codex patches may change multiple files: each matching file is checked, renamed files are checked at their destination, and deleted files are skipped. Changes made through shell commands or other tools do not trigger these hooks; run the appropriate project checks explicitly for those edits.

Local marketplace installation was verified with Codex CLI 0.156.1 on Windows. Older Codex versions and hosted environments are not covered by that verification; hook scripts and their tools must exist in the client's execution environment.

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
| **nette-command-line** | Command-line scripts with nette/command-line 2.0: parameters, commands, help, colored output |
| **nette-php-generator** | Generating PHP code with nette/php-generator: classes, namespaces, files, printers |
| **frontend-development** | Vite, ESLint, Tailwind, Nette Assets integration |
| **latte-templates** | Latte templating system, layouts, filters, template classes |
| **neon-format** | NEON data format syntax, mappings, sequences, entities |
| **tracy-debugging** | Debugging PHP errors via Tracy: BlueScreen, Tracy Bar, dump, console output |

### `nette-lint` – Automatic Validation

Validates files after supported edits and reports errors straight back to the assistant:

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

Optional plugin that automatically fixes PHP code style after supported edits using [DressCode](https://github.com/dg/dresscode): by the project's `dresscode.neon` when there is one, by the Nette coding standard otherwise.

| Skill | Description |
|-------|-------------|
| **php-auto-fixer** | Editing PHP under the fixing hook, and what its reports mean |
| **dresscode** | Running and configuring DressCode: checks and fixes, risky fixes, suppressions, baseline, CI, upgrading to newer PHP and library versions |
| **install-php-fixer** | Installs DressCode; run it explicitly |
| **migrate-to-dresscode** | Moves a project from `nette/coding-standard` of version 1 to DressCode |

Upgrading from version 1, which used `nette/coding-standard`: the fixer keeps formatting with it until DressCode is installed, and the agent offers the migration (the `migrate-to-dresscode` skill). It installs DressCode, into the project when the project uses PHPStan, globally otherwise, and creates `dresscode.neon`.

## Configuration

Project-level behavior of these plugins is configured through a `.nette-claude.json` file in your project, read exclusively by the plugins' own hooks. The same file applies in both Claude Code and Codex; its name is retained for compatibility. It is looked up upwards from the edited file (like `.gitignore`), so in a monorepo each package can have its own.

### Excluding files from hooks

To exclude certain paths (typically `fixtures` folders with intentionally broken templates, NEON or PHP), give a hook an `exclude` list. Each top-level key is a hook name:

```json
{
	"lint-neon": {
		"exclude": ["fixtures*"]
	},
	"lint-latte": {
		"exclude": ["tests/**/broken"]
	}
}
```

Available hooks: `lint-php` (`.php/.phpt`), `lint-latte` (`.latte`), `lint-neon` (`.neon`), `lint-json` (`.json/.jsonc`), `lint-js` (`.js/.ts`).

The PHP fixer of `php-fixer` is not configured here: it follows the project's `dresscode.neon`, including its `excludePaths`, and uses the Nette coding standard when the project has none.

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

## Privacy

The plugins collect no data. They run locally and have no servers, telemetry or analytics. See the [privacy policy](https://ai.nette.org/en/privacy-policy).

## License

MIT, see [license.md](license.md).

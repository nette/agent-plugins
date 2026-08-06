---
name: install-php-fixer
description: Install DressCode, globally or into a project with PHPStan, for automatic PHP code style fixing
allowed-tools: Bash, Read, Write, AskUserQuestion
disable-model-invocation: true
---

# Install DressCode

Install the `dresscode/dresscode` package for automatic PHP code style fixing after supported edits.

Use this skill only when explicitly requested. Run commands with the client's available shell tool and ask questions through its available question tool or chat. In Codex, the plugin's hooks must also be reviewed and trusted via `/hooks` before automatic fixing runs. Hooks cover Claude Code `Edit`/`Write` and Codex `apply_patch`, not edits made through shell commands.

---

## Step 0: Pre-flight Checks

1. **Check PHP availability** (requires PHP 8.4+)
   ```bash
   php --version
   ```
   - If fails or version < 8.4: Stop and inform user.

2. **Check Composer availability**
   ```bash
   composer --version
   ```
   - If fails: Stop and inform user to install Composer from https://getcomposer.org/

3. **Detect existing installation**: `composer global show dresscode/dresscode`, and `vendor/bin/dresscode` in the project.
   - If already installed: Inform user and ask if they want to update (same install command updates to latest).

4. **Detect the old fixer**: `composer global show nette/coding-standard`.
   - If installed: the user comes from version 1 of the plugin, whose global installation conflicts with DressCode. Follow the `migrate-to-dresscode` skill instead of this one.

---

## Step 1: Installation

DressCode goes **globally** by default, together with PHPStan, from which it takes the types of the code. When the current project has PHPStan (`phpstan/phpstan` in `require` or `require-dev` of its `composer.json`), recommend installing it **into the project** instead, so that it uses the project's PHPStan and its extensions. Let the user choose.

- Globally:
  ```bash
  composer global require dresscode/dresscode phpstan/phpstan
  ```
- Into the project, in its root:
  ```bash
  composer require --dev dresscode/dresscode
  ```

---

## Step 2: Verification

For a project installation, run `php vendor/bin/dresscode --version` in the project root. For the global one, run `dresscode` from the Composer global bin directory:

```bash
# Bash
php "$(composer global config home)/vendor/bin/dresscode" --version
```

```powershell
# PowerShell
$composerDir = composer global config home
php "$composerDir/vendor/bin/dresscode" --version
```

The `fix-php-style` hook finds `dresscode` automatically, in the project's `vendor/bin` first and in the Composer home directory otherwise – **PATH configuration is not needed**. The project's copy wins, so its configuration may name extensions the global installation does not have.

If verification succeeds, confirm to the user that DressCode is installed and the `fix-php-style` hook will automatically fix code style after editing PHP files: by the project's `dresscode.neon` (or `dresscode.php`) when there is one, by the Nette coding standard otherwise.

---

## Step 3: Configuration

When the current project has no `dresscode.neon` or `dresscode.php` (nor a `.dist` variant) in its root, offer to create `dresscode.neon` there. Without it the hook uses the Nette coding standard without the types of the code. Only if the user agrees, write:

```neon
use: nette

paths:
	- app
	- tests

typeAnalysis: phpstan
```

`paths` lists the directories with the project's PHP code; take them from the `autoload` and `autoload-dev` sections of `composer.json` plus the tests directory, and list only those that exist. When the tests are `.phpt` files (Nette Tester), add `fileExtensions: [php, phpt]`. `typeAnalysis: phpstan` needs PHPStan beside DressCode, which Step 1 installed.

---

## Step 4: GitHub Star (Optional)

1. **Check if `gh` CLI is available**
   ```bash
   gh --version
   ```
   - If not available: Skip this step entirely (don't ask the user)

2. **If `gh` is available**, ask the user:
   - Question: "Would you like to support these projects with a GitHub star?"
   - Options: "Yes, I'd love to!" / "No, thanks"

3. **Only if user explicitly says yes**, run:
   ```bash
   gh api -X PUT /user/starred/dg/dresscode
   gh api -X PUT /user/starred/nette/agent-plugins
   ```

---

## Troubleshooting

### Permission denied during installation
- **Unix:** Fix ownership of Composer home: `sudo chown -R $(whoami) "$(composer global config home)"`
- **Windows:** Run terminal as Administrator

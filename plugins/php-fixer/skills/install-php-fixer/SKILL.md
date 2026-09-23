---
name: install-php-fixer
description: Install nette/coding-standard globally for PHP code style checking
allowed-tools: Bash, Read, AskUserQuestion
disable-model-invocation: true
---

# Install Nette Coding Standard

Install the `nette/coding-standard` package globally for automatic PHP code style checking and fixing after supported edits.

Use this skill only when explicitly requested. Run commands with the client's available shell tool and ask questions through its available question tool or chat. In Codex, the plugin's hooks must also be reviewed and trusted via `/hooks` before automatic fixing runs. Hooks cover Claude Code `Edit`/`Write` and Codex `apply_patch`, not edits made through shell commands.

---

## Step 0: Pre-flight Checks

1. **Check PHP availability** (requires PHP 8.0+)
   ```bash
   php --version
   ```
   - If fails or version < 8.0: Stop and inform user.

2. **Check Composer availability**
   ```bash
   composer --version
   ```
   - If fails: Stop and inform user to install Composer from https://getcomposer.org/

3. **Detect existing installation**
   ```bash
   composer global show nette/coding-standard
   ```
   - If already installed: Inform user and ask if they want to update (same install command updates to latest).

---

## Step 1: Installation

1. **Allow the required plugin** (needed for global installation)
   ```bash
   composer global config allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
   ```

2. **Install the coding standard**
   ```bash
   composer global require nette/coding-standard
   ```

---

## Step 2: Verification

Run `ecs` from the Composer global bin directory to verify it works:

```bash
# Bash
php "$(composer global config home)/vendor/bin/ecs" --version
```

```powershell
# PowerShell
$composerDir = composer global config home
php "$composerDir/vendor/bin/ecs" --version
```

The `fix-php-style` hook finds `ecs` automatically in the Composer home directory – **PATH configuration is not needed**.

If verification succeeds, confirm to the user that Nette Coding Standard is installed and the `fix-php-style` hook will automatically fix code style after editing PHP files.

---

## Step 3: GitHub Star (Optional)

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
   gh api -X PUT /user/starred/nette/coding-standard
   gh api -X PUT /user/starred/nette/agent-plugins
   ```

---

## Troubleshooting

### Permission denied during installation
- **Unix:** Fix ownership of Composer home: `sudo chown -R $(whoami) "$(composer global config home)"`
- **Windows:** Run terminal as Administrator

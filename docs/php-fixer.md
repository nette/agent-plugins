# php-fixer: How the Hook Runs DressCode

The `php-fixer` plugin runs [DressCode](https://github.com/dg/dresscode) (`dresscode/dresscode`, source in `W:\libs\Dress-Code` on the maintainer's machine) after every Claude Code `Edit`/`Write` and Codex `apply_patch` of a `.php` or `.phpt` file. The hook is `plugins/php-fixer/hooks/fix-php-style.php`, its tests `tests/fix-php-style*.phpt`. Version 2.0.0 replaced the 1.x plugin, which ran `ecs` of nette/coding-standard 3, and keeps that one running for users who have not migrated yet (see Migration from 1.x).

This page records how the hook works and why, so that a change keeps the decisions below or changes them knowingly. The DressCode side keeps its own note on this consumer in `docs/local/findings/php-fixer-hook.md` of its repository; keep the two in step.


## Decisions

**DressCode is configured by `dresscode.neon` alone.** Which standard, sets, decisions, exclusions, target PHP version and baseline apply is the business of the project's DressCode configuration, the same one its CI runs. The hook reads nothing from `.nette-claude.json`, in the 1.x fallback neither, not even when a project still has a `fix-php-style` section there and no `dresscode.neon`: such a project gets the Nette standard and its old exclusions no longer apply. A second place for the same knowledge would only drift from the first, and the section was added shortly before 2.0.0, so nobody is believed to use it. Nothing converts it, and the migration does not mention it. `.nette-claude.json` remains for the lint hooks of `nette-lint`.

**Without a configuration, the Nette coding standard.** DressCode itself refuses to run without a configuration or a preset, so the hook adds `--use nette` exactly when it found no configuration file, never otherwise: a project with a configuration gets no standard laid over it.

**A project-local DressCode and a global one are both supported, the local one wins.** The hook takes `vendor/bin/dresscode` found upwards from the edited file (`findUpwards()`, which stops one level above the `.git` root), otherwise `vendor/bin/dresscode` in the global Composer home (`composer global require dresscode/dresscode`: `COMPOSER_HOME`, on Windows `%APPDATA%/Composer`, elsewhere the XDG or `~/.composer` directory). PATH is never consulted. The local copy wins because only its autoloader knows the extensions (rule packages) its configuration names. The skills install globally by default and into the project when it has PHPStan. `typeAnalysis: phpstan` takes the types from the `phpstan/phpstan` of the DressCode process, so a global DressCode gets a global PHPStan beside it, and every `dresscode.neon` the skills write has `typeAnalysis: phpstan`. A global PHPStan reads the classes of the project and of its `vendor` statically and runs none of its code, so it sees them although the global process does not have the project's autoloader; tested with the decisions `upgrading.declarations.deprecatedMember` and `upgrading.declarations.deprecatedClass`. For a project with PHPStan of its own the skills recommend DressCode in the project, so that the PHPStan of the project, its version and its extensions apply; a global DressCode over a project whose `phpstan.neon` includes extensions from its `vendor` would likely not find their classes (not verified). The types cost: one run over one file took about 410 ms with them and 150 ms without (a rough measurement). When neither DressCode nor the old `ecs` exists, the hook exits 0 silently: the plugin is optional and the `install-php-fixer` skill installs DressCode.

**The fixer never rewrites a file silently.** Claude must learn every rewrite, or its next `Edit` built on the old content fails or, worse, reintroduces what the fixer removed (an unused `use`). Every rewrite is reported, also when DressCode left violations as well.


## How a Run Goes

1. The file must have the `.php`/`.phpt` extension and exist.
2. The binary is chosen as described above; without DressCode the run goes the 1.x way (see Migration from 1.x), without that too it ends with exit 0.
3. `php -l` guard: a file with a syntax error is skipped silently. `lint-php` of `nette-lint` reports it, and hooks run in parallel in no fixed order, so the fixer cannot rely on it having run.
4. The configuration is searched the way DressCode's `Config\Loader::find()` does: upwards **to the filesystem root**, not just to `.git`, for `dresscode.neon`, `dresscode.php` and both `.dist` variants. The hook only needs the directory; which file wins and the conflict of two formats are left to DressCode.
5. The working directory is the configuration's directory; without one the nearest `composer.json`, then `.git`, then the file's own directory. DressCode searches for its configuration from the working directory and takes it as the root when there is none, so the hook changes into the right directory and passes the **absolute** path of the file.
6. The command: `PHP_BINARY -c <the hook's php.ini> <bin> fix <file> --format bare --no-color --skip-excluded --set correctness.debugOutput=keep [--use nette]`, with `-n` instead of `-c` when the hook itself runs without an ini. The same PHP and ini as the hook, because the bare binary would load the default `php.ini` (on the maintainer's machine one with Xdebug).
   - `--format bare`: a clean run prints nothing, a rewritten file prints `<path>  rewritten`, violations left to the user follow without headers and totals.
   - `--skip-excluded`: a file named on the command line still passes through `excludePaths`; without it the hook would rewrite `fixtures*` the project excludes. DressCode's default exclusions (`vendor`, `node_modules`, `temp`, `tmp`, `log`, `.*`) apply even without a configuration, so a file under `.github/` or `.claude/` is never formatted.
   - `correctness.debugOutput=keep`: the decision, where a configuration sets it to `commentedOut` (the set `cleanup` does), comments out `dump()`, `var_dump()` and their kin, which would destroy debugging calls in the middle of work. `--set` lays the value over the configuration of the project for this run only.
7. The result by DressCode's exit code (`0` clean, `1` violations left, `2` a file failed, `3` a mistake of the command line or of the configuration):
   - `0`: exit 0; when there is something to say, JSON on stdout with `hookSpecificOutput.additionalContext` holding "DressCode reformatted <file> after your edit. Read it again…" and any warnings the run printed (for example a decision the target PHP version is too old for).
   - anything else: exit 2 with the message on stderr, which the client shows Claude as an error: "could not fix everything … the rest is up to you" for `1`, "DressCode failed on" otherwise, the output without the `rewritten` marker, and the reformat sentence when the file was rewritten too.

The `rewritten` marker is told from warnings by the regular expression `~  rewritten$~m`; warnings go to the output in every format, `bare` included, and an earlier version that took any output as a rewrite reported "reformatted" on every edit of such a project. One run takes roughly 200-250 ms.


## Migration from 1.x

Users of 1.x have `nette/coding-standard` installed globally and no DressCode. An upgrade of the plugin must not take their formatting away silently, so until DressCode is installed the hook keeps working as 1.x did and offers the migration:

- **Only the global `ecs` exists, the project has no DressCode configuration:** `fixWithCodingStandard()` runs `ecs fix <file> --config-file hooks/ncs.php` as 1.x did, only without its exclusions in `.nette-claude.json` (see above). `ecs` is run directly, without PHP in front; on Windows cmd finds `ecs.bat` by PATHEXT.
- **Only the global `ecs` exists, the project has a DressCode configuration:** nothing is formatted, because `nette/coding-standard` would fight the project's own configuration. The notice is given.
- **The notice** (`noticeMigration()`) tells Claude that the plugin now formats with DressCode, which is not installed, and to offer the `migrate-to-dresscode` skill with the user's consent. It goes along with the result, as `additionalContext` or on stderr with an error. It is given once a session: a marker file `nette-php-fixer-<md5 of session_id>` in the system temp directory, and then it says so ("This notice comes once a session."), because without that sentence an agent told the user the notice would come after every edit. A client that sends no `session_id` gets it every time, without the sentence. For a Codex patch with several files `runHookFiles()` hands the `session_id` to each child process.

The `migrate-to-dresscode` skill, which Claude may run on its own once the user agrees (unlike `install-php-fixer`, a manual one for a fresh installation), installs DressCode into the project when it has PHPStan and globally with PHPStan otherwise, writes `dresscode.neon` by hand (`use: nette`, `paths`, `fileExtensions` for `.phpt` tests, `typeAnalysis: phpstan`; not `dresscode init`, whose measured output is longer and less predictable), keeps an existing configuration, shows the impact with `dresscode check` without fixing the project, and finally offers to remove the global `nette/coding-standard`. `paths` is required: without it `dresscode check` ends with "No paths given and none configured". A risky fix is made only with the user's consent for one decision (`--fix-risky=<path>`); `fix --ask-risky` needs an interactive terminal the agent does not have. `install-php-fixer` writes the same `dresscode.neon` for the current project when the user agrees, so both ways end in the same state.

The global `nette/coding-standard` 3.5 requires `nette/command-line ^1.9.2`, which conflicts with DressCode, so a global `composer require` of DressCode beside it fails (seen in the agent test). A new release of `nette/coding-standard` that allows `nette/command-line` 2 removes the conflict, but the version locked in the user's global installation must be updated first: `require` updates only the new package and its dependencies. The skill therefore runs `composer global update nette/coding-standard` before the installation, only when `composer global show nette/coding-standard` finds it: over a global Composer without `composer.json` the update fails with exit 1, over one without the package it does nothing. Until that release is out, the agent meets the conflict and offers to remove `nette/coding-standard` first.

## The `dresscode` Skill

The hook covers the file just edited; the `dresscode` skill covers DressCode run on purpose: `check` and `fix` over more files, the configuration, risky fixes, suppressions, the baseline, CI, and upgrades to newer PHP and library versions. It lives in this plugin because the plugin is what brings DressCode to an agent, and its description says it is not needed for ordinary edits, so it does not load beside `php-auto-fixer` at every PHP edit.

It bundles no manual. The manual of DressCode is written in Czech and follows the tool's own versions, so the skill teaches the model to ask the installed tool (`explain`, `config`, `catalogue`, `--help`), which answers for exactly that version and that project, and keeps only what the tool cannot say about itself: the model of decisions, standards, sets and target versions, the pitfalls of the configuration, and what the model must not decide alone (a fix over the whole project, risky fixes, suppressing, the baseline, `require.php`, installing packages). The longer material is in its `references/`: `configuration.md` and `upgrading.md`.

Unlike the hook, the skill names DressCode's commands, switches, configuration keys, decisions, presets and messages; after a change of any of those, check it as well.


## What the Hook Relies On in DressCode

Check these after every change of DressCode's command line; `tests/fix-php-style.phpt` runs the real global DressCode and catches most of them:

- the `fix` command, the options `--format bare`, `--no-color`, `--skip-excluded`, `--set path=value`, `--use`
- the exit codes and the `  rewritten` marker of the `bare` format
- the decision `correctness.debugOutput` with its value `keep`, and the preset name `nette` (the configuration names decisions; the rule names of earlier DressCode versions are unknown now)
- the configuration file names and the upward search of `Config\Loader`
- `bin` in DressCode's `composer.json`, without which `composer global require` creates no binary


## Testing

`php vendor/bin/tester tests -s`. The tests of real runs need a global DressCode and are skipped without it (`Environment::skip`). The global copy is installed from GitHub, not from the local checkout, so a DressCode change shows up in them only after it is pushed and `composer global update dresscode/dresscode phpsyntax/phpsyntax` runs. `tests/fix-php-style-patch.phpt` uses a local stand-in for `vendor/bin/dresscode` and checks the plumbing (Codex patches, the working directory, the arguments), not DressCode's rules. `tests/fix-php-style-legacy.phpt` covers the 1.x fallback with a stand-in for the global `ecs` (a `COMPOSER_HOME` of its own); the maintainer's global `nette/coding-standard` is a development version over DressCode, so the real version 3 cannot be checked on that machine.


## Known Consequences

- A repository without `dresscode.neon` is formatted by the Nette standard even when it follows other conventions. This repository has none either.
- `use` added in one edit and its first use in a later one: the fixer removes the import in between. The hook reports the rewrite, and the `php-auto-fixer` skill teaches the editing order.
- A `.git` in the home directory makes the home directory the working directory for a stray file without `composer.json` above it; inside projects this does not happen.

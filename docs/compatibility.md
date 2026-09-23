# Claude Code and Codex compatibility

The four plugins share `.claude-plugin/marketplace.json`, their `.claude-plugin/plugin.json` manifests, skills, and hook scripts. Codex's Claude-compatible loader is used intentionally. There is no generated second catalog or separate copy of the skills.

## Hook contract

Both clients discover `hooks/hooks.json`. The `Edit|Write` matcher covers Claude Code edits and Codex's `apply_patch` alias. Codex expands `${CLAUDE_PLUGIN_ROOT}` for compatibility; quoted script paths also work when plugin directories contain spaces.

`hook-input.php` adapts these two input shapes:

```json
{"tool_input":{"file_path":"/project/src/File.php"}}
```

```json
{"tool_name":"apply_patch","cwd":"/project","tool_input":{"command":"*** Begin Patch\n*** Update File: src/File.php\n@@\n-old\n+new\n*** End Patch"}}
```

The adapter extracts add/update destinations, including `Move to`, and skips deletions, missing files and unrelated extensions. Relative patch paths are based on the event's `cwd`. Paths are not canonicalized through symlinks, so configuration lookup follows the edited path.

Claude Code edits and patches containing one relevant file run directly. For multiple relevant files, the adapter invokes the same single-file hook using the current PHP executable and ini file (or `-n`), preserving independent tool/config discovery for each file. It combines all errors and `additionalContext` notices. If any file fails, the hook exits with code 2 and includes successful rewrite notices on stderr too, so they are not lost.

Each hook plugin must contain its own copy of `hook-input.php` and `hook-config.php`: installed plugins do not share a sibling directory. Keep the copies in sync.

## Verification

Verified on Windows with PHP 8.5.4, Codex CLI 0.156.1 and Claude Code 2.1.281:

- Codex added the existing local `.claude-plugin` marketplace and installed all four plugins without `.codex-plugin` manifests, using an isolated `CODEX_HOME`.
- Codex app-server `skills/list` loaded all 24 bundled skills without errors. `hooks/list` loaded all six command hooks without errors or warnings and expanded their plugin-root paths. Hooks were correctly marked as untrusted before review.
- All six expanded commands returned by `hooks/list` were executed directly with a two-file patch payload. The PHP fixer reformatted both files with the globally installed ECS. This checks the installed scripts and command quoting, not the client's trust or model loop.
- Claude Code `plugin validate` accepted the marketplace, all four plugins, and the changed skill directory.
- The optional Python skill validator could not run because the local `python3` wrapper points to a missing `python` executable; client loading and Claude's skill validation were used instead.
- PHP regression tests cover both input formats, multi-file patches, rename destinations, deletion, irrelevant extensions, paths with spaces, relative and absolute paths, duplicate paths, exclusions, PHP ini propagation, JSONC, and aggregated errors/rewrite notices.
- The real `fix-php-style.php` is exercised with a stand-in for the global ECS installation (`COMPOSER_HOME`); the stand-in tests do not claim to validate the coding standard's rules.

Run the regression suite:

```sh
php vendor/bin/tester tests -s
```

For a client installation check, use a disposable Codex configuration directory and a local copy of the repository, then run:

```sh
codex plugin marketplace add /path/to/agent-plugins
codex plugin list --marketplace nette --available --json
codex plugin add nette@nette
codex plugin add nette-lint@nette
codex plugin add nette-dev@nette
codex plugin add php-fixer@nette
```

Before an interactive editing check, review the installed hooks with `/hooks`. Try a patch changing two PHP files, including one syntax error, then a rename and a style-only change. Confirm the assistant receives every diagnostic.

## Limits

- Client discovery and installation checks do not constitute a full model-driven editing session. The adapter's event handling and subprocess feedback are covered by automated tests.
- Shell-based writes and arbitrary MCP edits do not match these hooks. Run project checks explicitly after those edits. Do not infer changed files by parsing arbitrary shell commands or lint unrelated pre-existing working-tree changes.
- Codex requires trust of the current hook definitions; installation alone does not enable execution. Administrative settings may also disable hooks.
- Older Codex releases, Linux/macOS client execution, and hosted environments were not verified here. Hosted installation alone does not provision PHP or project tools.
- Hooks can run concurrently. Each hook must remain independent; the fixer still checks PHP syntax itself before running ECS.
- `.nette-claude.json` remains the shared configuration filename for backward compatibility.

References: [OpenAI plugin packaging](https://developers.openai.com/plugins/build/plugins), [Codex hook input, output and trust](https://learn.chatgpt.com/docs/hooks).

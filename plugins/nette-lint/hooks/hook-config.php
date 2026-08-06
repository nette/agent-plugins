<?php

/**
 * Helper for this plugin's PostToolUse hooks: reads the project's
 * .nette-claude.json and decides whether a given hook should skip a file,
 * and locates project tools/configs upwards from the edited file.
 *
 * Plugins install independently (each into its own cache directory), so this
 * file is duplicated in every plugin's hooks/ folder - keep the copies in sync.
 */


/**
 * Walks from the edited file's directory upwards and returns the first directory
 * where $check succeeds. Handles monorepos where the session runs above the app
 * directory. Stops one level above the nearest .git root (or at the filesystem
 * root), so unrelated tools/configs further up are never picked up.
 */
function findUpwards(string $filePath, callable $check): ?string
{
	$dir = str_replace('\\', '/', dirname($filePath));
	$aboveGitRoot = false;
	while (true) {
		if ($check($dir)) {
			return $dir;
		}
		if ($aboveGitRoot) {
			return null;
		}
		$aboveGitRoot = file_exists($dir . '/.git'); // directory or file (worktree, submodule)
		$parent = dirname($dir);
		if ($parent === $dir) {
			return null;
		}
		$dir = $parent;
	}
}


/**
 * Searches for .nette-claude.json upwards from the edited file (like .gitignore)
 * and returns the given hook's section together with the config directory, as
 * [section, configDir]. Returns [[], null] when there is no config for the hook.
 */
function hookConfig(string $filePath, string $hookName): array
{
	$dir = str_replace('\\', '/', dirname($filePath));
	while (!is_file($dir . '/.nette-claude.json')) {
		$parent = dirname($dir);
		if ($parent === $dir) {
			return [[], null];
		}
		$dir = $parent;
	}

	// tolerate a malformed config
	$config = json_decode((string) @file_get_contents($dir . '/.nette-claude.json'), true);
	$section = is_array($config) ? ($config[$hookName] ?? null) : null;
	return [is_array($section) ? $section : [], $dir];
}


/**
 * Decides whether the path is excluded from the given hook.
 */
function isExcluded(string $filePath, string $hookName): bool
{
	[$section, $base] = hookConfig($filePath, $hookName);
	$patterns = $section['exclude'] ?? null;
	if ($base === null || !is_array($patterns)) {
		return false;
	}

	// path relative to the config directory
	$path = str_replace('\\', '/', $filePath);
	$rel = ltrim(substr($path, strlen($base)), '/');

	foreach ($patterns as $pattern) {
		if (is_string($pattern) && matchesPattern($pattern, $rel)) {
			return true;
		}
	}
	return false;
}


/**
 * Gitignore-like matching of a pattern against a relative path.
 */
function matchesPattern(string $pattern, string $path): bool
{
	$pattern = str_replace('\\', '/', $pattern);
	$anchored = str_starts_with($pattern, '/'); // leading slash = anchored to config dir
	$pattern = trim($pattern, '/');
	if ($pattern === '') {
		return false;
	}

	// slashless pattern = matches any single path segment (wildcards stay within it)
	if (!$anchored && !str_contains($pattern, '/')) {
		$regex = strtr(preg_quote($pattern, '~'), [
			'\*' => '[^/]*',
			'\?' => '[^/]',
		]);
		foreach (explode('/', $path) as $segment) {
			if (preg_match('~^' . $regex . '$~', $segment)) {
				return true;
			}
		}
		return false;
	}

	// pattern with a slash (or leading-slash anchored) = glob anchored to the config directory;
	// trailing (?:/.*)? => matching a directory excludes its contents too
	$regex = strtr(preg_quote($pattern, '~'), [
		'\*\*/' => '(?:.*/)?',
		'\*\*' => '.*',
		'\*' => '[^/]*',
		'\?' => '[^/]',
	]);
	return (bool) preg_match('~^' . $regex . '(?:/.*)?$~', $path);
}

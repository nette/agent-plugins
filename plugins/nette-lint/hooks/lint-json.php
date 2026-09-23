<?php

/**
 * PostToolUse hook: Validate JSON files after editing.
 *
 * PHP's native json_decode() is a poor linter: it reports only "Syntax error"
 * without a line number, and it rejects the comments / trailing commas that
 * JSONC files (tsconfig.json, jsconfig.json, .vscode/*.json, *.jsonc) legitimately
 * contain, so it would flag false positives right after install.
 *
 * Instead we use seld/jsonlint (bundled in jsonlint/ - the pure-PHP parser
 * Composer uses), which reports "Parse error on line N" with a visual caret and
 * natively accepts comments via ALLOW_COMMENTS. For JSONC we additionally strip
 * trailing commas (which even ALLOW_COMMENTS rejects), preserving byte offsets so
 * the reported line stays accurate.
 */

use Seld\JsonLint\JsonParser;

require __DIR__ . '/jsonlint/ParsingException.php';
require __DIR__ . '/jsonlint/DuplicateKeyException.php';
require __DIR__ . '/jsonlint/Undefined.php';
require __DIR__ . '/jsonlint/Lexer.php';
require __DIR__ . '/jsonlint/JsonParser.php';

require __DIR__ . '/hook-input.php';
$filePath = readHookFile(__FILE__, ['json', 'jsonc']);

// Skip if not a JSON/JSONC file
if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), ['json', 'jsonc'], true) || !file_exists($filePath)) {
	exit(0);
}

// Skip paths excluded in the project's .nette-claude.json
require __DIR__ . '/hook-config.php';
if (isExcluded($filePath, 'lint-json')) {
	exit(0);
}

$content = (string) file_get_contents($filePath);
$flags = 0;

// JSONC files may contain comments (handled by ALLOW_COMMENTS) and trailing
// commas (rejected even then, so strip them first)
if (isJsonc($filePath)) {
	$content = stripTrailingCommas($content);
	$flags = JsonParser::ALLOW_COMMENTS;
}

$error = (new JsonParser)->lint($content, $flags);
if ($error === null) {
	exit(0);
}

fwrite(STDERR, "JSON syntax error in $filePath:\n");
fwrite(STDERR, $error->getMessage() . "\n");
exit(2);


/**
 * Decides whether a file should be treated as JSONC (comments + trailing commas
 * allowed): the .jsonc extension, known tooling files, anything under .vscode/,
 * plus custom glob patterns from .nette-claude.json ("lint-json" -> "jsonc").
 */
function isJsonc(string $filePath): bool
{
	$path = str_replace('\\', '/', $filePath);
	$base = basename($path);

	if (pathinfo($base, PATHINFO_EXTENSION) === 'jsonc'
		|| str_contains($path, '/.vscode/')
		|| $base === 'devcontainer.json'
		|| preg_match('~^(tsconfig|jsconfig)(\..+)?\.json$~', $base)
	) {
		return true;
	}

	foreach (jsoncPatterns($filePath) as [$rel, $pattern]) {
		if (matchesPattern($pattern, $rel)) {
			return true;
		}
	}
	return false;
}


/**
 * Reads the "jsonc" glob list from the nearest .nette-claude.json (looked up
 * upwards like .gitignore) and returns [relativePath, pattern] pairs.
 */
function jsoncPatterns(string $filePath): array
{
	$dir = str_replace('\\', '/', dirname($filePath));
	while (true) {
		if (is_file($dir . '/.nette-claude.json')) {
			break;
		}
		$parent = dirname($dir);
		if ($parent === $dir) {
			return [];
		}
		$dir = $parent;
	}

	$config = json_decode((string) @file_get_contents($dir . '/.nette-claude.json'), true);
	$section = is_array($config) ? ($config['lint-json'] ?? null) : null;
	$patterns = is_array($section) ? ($section['jsonc'] ?? null) : null;
	if (!is_array($patterns)) {
		return [];
	}

	$rel = ltrim(substr(str_replace('\\', '/', $filePath), strlen($dir)), '/');
	$out = [];
	foreach ($patterns as $pattern) {
		if (is_string($pattern)) {
			$out[] = [$rel, $pattern];
		}
	}
	return $out;
}


/**
 * Removes trailing commas (a comma right before a closing } or ]) by blanking
 * the comma to a space, so byte offsets - and therefore reported line numbers -
 * stay intact. Commas inside strings are left structurally harmless.
 */
function stripTrailingCommas(string $s): string
{
	return preg_replace('~,(\s*[}\]])~', ' $1', $s);
}

<?php

/**
 * PostToolUse hook: Run ESLint auto-fix after editing JavaScript/TypeScript files
 * Only runs if project has ESLint configured (config searched upwards from the edited file)
 */

require __DIR__ . '/hook-input.php';
$filePath = readHookFile(__FILE__, ['js', 'ts', 'mjs', 'mts']);

// Skip if not a JS/TS file
if (!preg_match('~\.(js|ts|mjs|mts)$~', $filePath) || !file_exists($filePath)) {
	exit(0);
}

// Skip paths excluded in the project's .nette-claude.json
require __DIR__ . '/hook-config.php';
if (isExcluded($filePath, 'lint-js')) {
	exit(0);
}

// Find ESLint config upwards from the edited file, otherwise skip
$dir = findUpwards($filePath, fn($dir) => glob($dir . '/eslint.config.*')
	|| file_exists($dir . '/.eslintrc.js')
	|| file_exists($dir . '/.eslintrc.json'));
if ($dir === null) {
	exit(0);
}

// Run ESLint fix directly on the file, from the config directory
// (flat config and local node_modules resolve relative to cwd)
chdir($dir);
exec('npx eslint --fix ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

if ($exitCode === 0) {
	exit(0);
} else {
	fwrite(STDERR, "ESLint errors in $filePath:\n");
	fwrite(STDERR, implode("\n", $output) . "\n");
	exit(2);
}

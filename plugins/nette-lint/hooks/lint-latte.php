<?php

/**
 * PostToolUse hook: Validate Latte templates after editing
 * Only runs if project has custom latte-lint script (searched upwards from the edited file)
 */

require __DIR__ . '/hook-input.php';
$filePath = readHookFile(__FILE__, ['latte']);

// Skip if not a Latte file
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'latte' || !file_exists($filePath)) {
	exit(0);
}

// Skip paths excluded in the project's .nette-claude.json
require __DIR__ . '/hook-config.php';
if (isExcluded($filePath, 'lint-latte')) {
	exit(0);
}

// Find project's custom latte-lint upwards from the edited file, otherwise skip.
// On Windows a .bat wrapper wins; the plain script is a PHP file run via PHP_BINARY.
$dir = findUpwards($filePath, fn($dir) => is_file($dir . '/latte-lint')
	|| (PHP_OS_FAMILY === 'Windows' && is_file($dir . '/latte-lint.bat')));
if ($dir === null) {
	exit(0);
}
if (PHP_OS_FAMILY === 'Windows') {
	$command = is_file($dir . '/latte-lint.bat')
		? escapeshellarg($dir . '/latte-lint.bat')
		: escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/latte-lint');
} else {
	$command = escapeshellarg($dir . '/latte-lint');
}

// Run latte-lint
exec($command . ' ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

if ($exitCode === 0) {
	exit(0);
} else {
	fwrite(STDERR, "Latte template error in $filePath:\n");
	fwrite(STDERR, implode("\n", $output) . "\n");
	exit(2);
}

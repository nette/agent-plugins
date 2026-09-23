<?php

/**
 * PostToolUse hook: Fix PHP coding standards after editing PHP files
 * Silently skips if nette/coding-standard is not installed
 */


function getComposerHomePath(): ?string
{
	if (PHP_OS_FAMILY === 'Windows') {
		$dirs = [
			getenv('COMPOSER_HOME') ?: null,
			getenv('APPDATA') ? getenv('APPDATA') . '/Composer' : null,
		];
	} else {
		$home = getenv('HOME');
		$xdgConfig = getenv('XDG_CONFIG_HOME') ?: ($home ? $home . '/.config' : null);
		$dirs = [
			getenv('COMPOSER_HOME') ?: null,
			$xdgConfig ? $xdgConfig . '/composer' : null,
			$home ? $home . '/.composer' : null,
		];
	}

	foreach ($dirs as $dir) {
		if ($dir && is_dir($dir)) {
			return $dir;
		}
	}
	return null;
}


// Find ecs in composer global bin directories
$composerHome = getComposerHomePath();
$ecs = $composerHome . '/vendor/bin/ecs';
if (!$composerHome || !file_exists($ecs)) {
	exit(0);
}

// Read hook input
require __DIR__ . '/hook-input.php';
$filePath = readHookFile(__FILE__, ['php', 'phpt']);

// Skip if not a PHP file
if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), ['php', 'phpt'], true) || !file_exists($filePath)) {
	exit(0);
}

// Skip paths excluded in the project's .nette-claude.json
require __DIR__ . '/hook-config.php';
if (isExcluded($filePath, 'fix-php-style')) {
	exit(0);
}

// Don't run the fixer on invalid PHP (lint-php reports syntax errors); avoids ECS errors
exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($filePath) . ' 2>&1', $lint, $lintExit);
if ($lintExit !== 0) {
	exit(0);
}

// Fix coding standard issues automatically
exec(
	escapeshellarg($ecs)
	. ' fix ' . escapeshellarg($filePath)
	. ' --config-file ' . escapeshellarg(__DIR__ . '/ncs.php')
	. ' 2>&1',
	$output,
	$exitCode,
);

if ($exitCode === 0) {
	exit(0);
} else {
	fwrite(STDERR, "Could not fix all coding standard issues in $filePath:\n");
	fwrite(STDERR, implode("\n", $output) . "\n");
	exit(2);
}

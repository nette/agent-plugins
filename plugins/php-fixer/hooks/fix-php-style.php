<?php declare(strict_types=1);


/**
 * PostToolUse hook: Fix PHP coding standards after editing PHP files with DressCode
 * Falls back to nette/coding-standard of plugin 1.x, silently skips if neither is installed
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


/**
 * The nearest directory with a DressCode configuration, searched the way DressCode itself searches:
 * upwards to the root of the filesystem, not just to the repository. Which of the files applies when
 * several are there is DressCode's business.
 */
function findDressCodeConfigDir(string $filePath): ?string
{
	$dir = str_replace('\\', '/', dirname($filePath));
	while (true) {
		foreach (['dresscode.neon', 'dresscode.php', 'dresscode.neon.dist', 'dresscode.php.dist'] as $name) {
			if (is_file("$dir/$name")) {
				return $dir;
			}
		}
		$parent = dirname($dir);
		if ($parent === $dir) {
			return null;
		}
		$dir = $parent;
	}
}


/**
 * Formats the file with nette/coding-standard the way plugin 1.x did, for a user who has not moved to DressCode yet.
 */
function fixWithCodingStandard(string $filePath, string $ecs): never
{
	$notice = noticeMigration("The retired nette/coding-standard ran on $filePath this time.");
	exec(
		escapeshellarg($ecs)
		. ' fix ' . escapeshellarg($filePath)
		. ' --config-file ' . escapeshellarg(__DIR__ . '/ncs.php')
		. ' 2>&1',
		$output,
		$exitCode,
	);
	finish($exitCode === 0 ? null : "Could not fix all coding standard issues in $filePath:\n" . implode("\n", $output), [$notice]);
}


/**
 * Tells the agent that DressCode is missing and how to get it, once a session; every time when the client
 * sends no session id.
 */
function noticeMigration(string $situation): ?string
{
	$session = readHookInput()['session_id'] ?? null;
	$once = is_string($session) && $session !== '';
	if ($once) {
		$mark = sys_get_temp_dir() . '/nette-php-fixer-' . md5($session);
		if (file_exists($mark)) {
			return null;
		}
		@touch($mark); // @ - a notice told again is better than a failed hook
	}

	return "$situation The php-fixer plugin now formats PHP with DressCode, which is not installed."
		. ' Tell the user and, if they agree, migrate with the `migrate-to-dresscode` skill of php-fixer.'
		. ($once ? ' This notice comes once a session.' : '');
}


/**
 * Hands the result to the agent: an error fails the hook with exit 2, notices go along either way.
 * @param  list<?string>  $notices
 */
function finish(?string $error, array $notices = []): never
{
	$notices = array_filter($notices);
	if ($error !== null) {
		fwrite(STDERR, implode("\n", [$error, ...$notices]) . "\n");
		exit(2);
	} elseif ($notices) {
		echo json_encode([
			'hookSpecificOutput' => [
				'hookEventName' => 'PostToolUse',
				'additionalContext' => implode("\n", $notices),
			],
		]);
	}
	exit(0);
}


// Read hook input
require __DIR__ . '/hook-input.php';
$filePath = readHookFile(__FILE__, ['php', 'phpt']);

// Skip if not a PHP file
if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), ['php', 'phpt'], true) || !file_exists($filePath)) {
	exit(0);
}

require __DIR__ . '/hook-config.php';

// The project's own DressCode first (its autoloader knows the extensions its configuration names),
// otherwise the one installed globally with Composer, otherwise the ecs of plugin 1.x
$projectDir = findUpwards($filePath, fn($dir) => is_file("$dir/vendor/bin/dresscode"));
$dresscode = $projectDir !== null
	? "$projectDir/vendor/bin/dresscode"
	: getComposerHomePath() . '/vendor/bin/dresscode';
$ecs = getComposerHomePath() . '/vendor/bin/ecs';
if (!is_file($dresscode) && !is_file($ecs)) {
	exit(0);
}

// Don't run the fixer on invalid PHP (lint-php reports syntax errors)
exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($filePath) . ' 2>&1', $lint, $lintExit);
if ($lintExit !== 0) {
	exit(0);
}

// DressCode looks for its configuration upwards from the working directory, so the run starts where the
// configuration is; a project without one gets the Nette coding standard and runs from its root
$configDir = findDressCodeConfigDir($filePath);

if (!is_file($dresscode) && $configDir === null) {
	fixWithCodingStandard($filePath, $ecs);
} elseif (!is_file($dresscode)) { // nette/coding-standard would fight the project's own configuration
	finish(null, [noticeMigration("$filePath was not formatted: its project is configured for DressCode.")]);
}

$cwd = $configDir
	?? findUpwards($filePath, fn($dir) => is_file("$dir/composer.json"))
	?? findUpwards($filePath, fn($dir) => file_exists("$dir/.git"))
	?? dirname($filePath);

// The start of every run compiles all the rules, so the compiled code is kept on disk between runs
$opcacheDir = sys_get_temp_dir() . '/dresscode-opcache';
$opcache = extension_loaded('Zend OPcache') && (is_dir($opcacheDir) || @mkdir($opcacheDir, 0777, true)) // @ - the run goes on without it
	? ' -d opcache.enable_cli=1 -d opcache.file_cache=' . escapeshellarg($opcacheDir) . ' -d opcache.file_cache_only=1'
	: '';

// The same PHP with the same ini as this hook; the binary alone would load the default one
$ini = php_ini_loaded_file();
$command = escapeshellarg(PHP_BINARY)
	. ($ini === false ? (php_ini_scanned_files() === false ? ' -n' : '') : ' -c ' . escapeshellarg($ini))
	. $opcache
	. ' ' . escapeshellarg($dresscode)
	. ' fix ' . escapeshellarg($filePath)
	. ' --format bare --no-color'
	. ' --skip-excluded' // what the project's configuration leaves out stays as it was written
	. ' --set mistakes.debugOutput=keep' // dump() and its kin stay while editing
	. ($configDir === null ? ' --use nette' : '');

chdir($cwd);
exec($command . ' 2>&1', $output, $exitCode);
$text = implode("\n", $output);

// Exit codes of DressCode: 0 clean, 1 something is left to the user, 2 the file failed, 3 a mistake of
// the command line or of the configuration. A clean run says nothing at all, a rewritten file is marked
// as such, which the agent has to know even when something is left to it, and what else a clean run says
// is a warning (a decision the target PHP is too old for, a file that did not settle)
$rewritten = preg_match('~  rewritten$~m', $text)
	? "DressCode reformatted $filePath after your edit. Read it again before editing it further."
	: null;

if ($exitCode === 0) {
	$warnings = trim((string) preg_replace('~^.*  rewritten$~m', '', $text));
	finish(null, [$rewritten, $warnings === '' ? null : "DressCode warns about $filePath:\n$warnings"]);
}

finish(
	($exitCode === 1
		? "DressCode could not fix everything in $filePath, the rest is up to you:"
		: "DressCode failed on $filePath:")
	. "\n" . preg_replace('~  rewritten$~m', '', $text),
	[$rewritten],
);

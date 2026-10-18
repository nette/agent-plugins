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
		], JSON_INVALID_UTF8_SUBSTITUTE);
	}
	exit(0);
}


/**
 * Runs the command and returns its exit code, stdout and stderr, read separately; null when it could not
 * start, false when it did not finish in time and was stopped.
 * @param  list<string>  $command
 * @return array{int, string, string}|false|null
 */
function runCommand(array $command, string $cwd, int $timeout): array|false|null
{
	// File-backed output avoids pipe deadlocks for a large report
	$stdout = tmpfile();
	$stderr = tmpfile();
	$process = $stdout && $stderr
		? proc_open($command, [0 => ['pipe', 'r'], 1 => $stdout, 2 => $stderr], $pipes, $cwd)
		: false;
	if (!is_resource($process)) {
		return null;
	}
	fclose($pipes[0]);

	$deadline = microtime(true) + $timeout;
	while (($status = proc_get_status($process))['running']) {
		if (microtime(true) > $deadline) {
			proc_terminate($process);
			proc_close($process);
			return false;
		}
		usleep(5000);
	}
	proc_close($process);
	rewind($stdout);
	rewind($stderr);
	return [$status['exitcode'], (string) stream_get_contents($stdout), (string) stream_get_contents($stderr)];
}


/**
 * The lines of the file the edit wrote, as the ranges of DressCode's `--lines`: from the hunks Claude Code sends
 * with the result of the tool, or from the hunks of a Codex patch found in the file. Null where nothing proves them,
 * a new file being all the agent's, and then DressCode fixes and lists everything; never from searching the edited text.
 * @return ?list<string>
 */
function findEditedLines(array $input, string $filePath): ?array
{
	$hunks = $input['tool_response']['structuredPatch'] ?? null;
	$patch = $input['tool_input']['command'] ?? null;
	$lines = match (true) {
		is_array($hunks) => $hunks ? findHunkLines($hunks) : null,
		($input['tool_name'] ?? null) === 'apply_patch' && is_string($patch)
			=> findPatchLines($patch, (string) @file_get_contents($filePath)), // @ - an unreadable file gets no range
		default => null,
	};
	return $lines ? formatRanges($lines) : null;
}


/**
 * The lines of the edited file the hunks of Claude Code wrote; null for a hunk of another shape.
 * @return ?list<int>
 */
function findHunkLines(array $hunks): ?array
{
	$lines = [];
	foreach ($hunks as $hunk) {
		if (!is_int($hunk['newStart'] ?? null) || !is_array($hunk['lines'] ?? null)) {
			return null;
		}
		$texts = array_values(array_map(fn($text) => is_string($text) ? $text : '', $hunk['lines']));
		array_push($lines, ...findChangedLines($texts, $hunk['newStart']));
	}
	return $lines;
}


/**
 * The lines of the edited file the hunks of a Codex patch wrote, each hunk found by its lines after the edit,
 * its context and what it added, at exactly one place of the file; null where a hunk is not found so or the patch
 * adds the file.
 * @return ?list<int>
 */
function findPatchLines(string $patch, string $code): ?array
{
	$file = preg_split('~\r\n|\r|\n~', $code);
	$hunks = [];
	foreach (explode("\n", str_replace("\r\n", "\n", $patch)) as $text) {
		if (str_starts_with($text, '*** Add File: ')) {
			return null;
		} elseif (str_starts_with($text, '@@')) {
			$hunks[] = [];
		} elseif ($hunks && !str_starts_with($text, '*** ') && in_array($text[0] ?? ' ', [' ', '+', '-'], true)) {
			$hunks[array_key_last($hunks)][] = $text === '' ? ' ' : $text;
		}
	}

	$lines = [];
	foreach ($hunks as $hunk) {
		$after = array_values(array_map(fn($text) => substr($text, 1), array_filter($hunk, fn($text) => $text[0] !== '-')));
		$places = [];
		for ($i = 0; $after && $i + count($after) <= count($file); $i++) {
			if (array_slice($file, $i, count($after)) === $after) {
				$places[] = $i + 1;
			}
		}
		if (count($places) !== 1) {
			return null;
		}
		array_push($lines, ...findChangedLines($hunk, $places[0]));
	}
	return $lines;
}


/**
 * The lines of the edited file a hunk starting there writes: each line it adds, and for a run of changes that only
 * takes lines away the line now standing in their place.
 * @param  list<string>  $hunk  lines of a unified diff, marked by a space, `+` or `-`
 * @return list<int>
 */
function findChangedLines(array $hunk, int $start): array
{
	$lines = [];
	$line = $start;
	$removedAt = null; // the line a run of changes started on, false once it added one
	foreach ([...$hunk, ' '] as $text) {
		$mark = $text[0] ?? ' ';
		if ($mark === '-') {
			$removedAt ??= $line;
			continue;
		} elseif ($mark === '+') {
			$lines[] = $line;
			$removedAt = false;
		} elseif ($removedAt !== null) {
			if ($removedAt !== false) {
				$lines[] = $removedAt;
			}
			$removedAt = null;
		}
		$line++;
	}
	return $lines;
}


/**
 * The lines as ranges `20-21`, a range of one line as `20`.
 * @param  list<int>  $lines
 * @return list<string>
 */
function formatRanges(array $lines): array
{
	$lines = array_values(array_unique(array_filter($lines, fn($line) => $line > 0)));
	sort($lines);
	$ranges = [];
	foreach ($lines as $line) {
		$last = array_key_last($ranges);
		if ($last !== null && $ranges[$last][1] === $line - 1) {
			$ranges[$last][1] = $line;
		} else {
			$ranges[] = [$line, $line];
		}
	}
	return array_map(fn($range) => $range[0] === $range[1] ? "$range[0]" : "$range[0]-$range[1]", $ranges);
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
	? ['-d', 'opcache.enable_cli=1', '-d', "opcache.file_cache=$opcacheDir", '-d', 'opcache.file_cache_only=1']
	: [];

// The file as it was before the edit, whose own style DressCode keeps where it differs from the configuration;
// a new file has none, and Codex does not send it
$original = readHookInput()['tool_response']['originalFile'] ?? null;
$before = is_string($original) ? tempnam(sys_get_temp_dir(), 'php-fixer-before-') : false;
if ($before !== false && file_put_contents($before, $original) === false) {
	$before = false;
}

// The same PHP with the same ini as this hook; the binary alone would load the default one
$ini = php_ini_loaded_file();
$command = [
	PHP_BINARY,
	...($ini === false ? (php_ini_scanned_files() === false ? ['-n'] : []) : ['-c', $ini]),
	...$opcache,
	$dresscode, 'fix', $filePath, '--format', 'agent', '--no-color',
	'--skip-excluded', // what the project's configuration leaves out stays as it was written
	'--set', 'mistakes.debugOutput=keep', // dump() and its kin stay while editing
	...($configDir === null ? ['--use', 'nette'] : []),
	// what is left is listed for the lines the edit wrote, the rest counted
	...array_merge(...array_map(fn($range) => ['--lines', $range], findEditedLines(readHookInput(), $filePath) ?? [])),
];

$timeout = 50;
$result = runCommand([...$command, ...($before === false ? [] : ['--before', $before])], $cwd, $timeout);
if ($before !== false && is_array($result) && $result[0] === 3 && str_contains($result[2], '--before')) {
	$result = runCommand($command, $cwd, $timeout); // a DressCode older than the option
}

if ($before !== false) {
	@unlink($before); // @ - a file left in the temporary directory does no harm
}
if ($result === null) {
	finish("DressCode could not be started, so $filePath was not checked.");
} elseif ($result === false) {
	finish("DressCode did not finish on $filePath within $timeout s and was stopped. Read the file again before editing it further.");
}

// DressCode writes the whole message for the agent on stdout, nothing when the file is clean; exit 1 means
// something is left to the agent, 2 the file failed and 3 a mistake of the command line or of the
// configuration, which only stderr tells
[$exitCode, $stdout, $stderr] = $result;
if ($exitCode === 0) {
	finish(null, [rtrim($stdout) ?: null]);
}
finish(rtrim($stdout) ?: rtrim($stderr) ?: "DressCode failed on $filePath with exit code $exitCode.");

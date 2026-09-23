<?php declare(strict_types=1);

/**
 * Input adapter shared by Claude Code and Codex hooks.
 * Plugins install independently: keep both copies of this file identical.
 */

/**
 * Returns a single edited file, or runs this hook for each matching file in a Codex patch.
 * Claude Code and single-file patches take the direct path without a subprocess.
 */
function readHookFile(string $script, array $extensions): string
{
	$input = json_decode(file_get_contents('php://stdin'), true);
	if (!is_array($input)) {
		return '';
	}
	$file = $input['tool_input']['file_path'] ?? null;
	if (is_string($file)) {
		return $file;
	}
	if (($input['tool_name'] ?? null) !== 'apply_patch'
		|| !is_string($input['tool_input']['command'] ?? null)
	) {
		return '';
	}

	$cwd = $input['cwd'] ?? getcwd();
	if (!is_string($cwd) || !is_dir($cwd)) {
		fwrite(STDERR, "Invalid hook working directory.\n");
		exit(2);
	}
	$files = [];
	foreach (patchFiles($input['tool_input']['command']) as $file) {
		$file = str_replace('\\', '/', $file);
		if (!str_starts_with($file, '/') && !preg_match('~^[A-Za-z]:/~', $file)) {
			$file = "$cwd/$file";
		}
		// Do not resolve symlinks: configuration belongs to the edited path.
		if (in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions, true) && is_file($file)) {
			$files[] = $file;
		}
	}
	$files = array_values(array_unique($files));
	if (count($files) < 2) {
		return $files[0] ?? '';
	}

	runHookFiles($script, $files, $cwd);
	exit(0);
}


/** Returns the surviving paths from an apply_patch command, including rename destinations. */
function patchFiles(string $patch): array
{
	$paths = [];
	$path = null;
	foreach (explode("\n", str_replace("\r\n", "\n", $patch)) as $line) {
		// Hunk lines start with a space, + or -, so content cannot masquerade as headers.
		if (preg_match('~^\*\*\* (Add|Update|Delete) File: (.+)$~D', $line, $match)) {
			if ($path !== null) {
				$paths[] = $path;
			}
			$path = $match[1] === 'Delete' ? null : $match[2];
		} elseif ($path !== null && str_starts_with($line, '*** Move to: ')) {
			$path = substr($line, strlen('*** Move to: '));
		}
	}
	if ($path !== null) {
		$paths[] = $path;
	}
	return $paths;
}


/** Runs the existing single-file hook and combines feedback without losing later failures. */
function runHookFiles(string $script, array $files, string $cwd): void
{
	$command = [PHP_BINARY];
	$ini = php_ini_loaded_file();
	if ($ini !== false) {
		array_push($command, '-c', $ini);
	} elseif (php_ini_scanned_files() === false) {
		$command[] = '-n';
	}
	$command[] = $script;
	$context = [];
	$errors = [];
	foreach ($files as $file) {
		// File-backed output avoids pipe deadlocks for large linter reports.
		$stdout = tmpfile();
		$stderr = tmpfile();
		if ($stdout === false || $stderr === false) {
			fwrite(STDERR, "Cannot create temporary hook output files.\n");
			exit(2);
		}
		$process = proc_open($command, [0 => ['pipe', 'r'], 1 => $stdout, 2 => $stderr], $pipes, $cwd);
		if (!is_resource($process)) {
			$errors[] = "Could not start hook for $file.";
			fclose($stdout);
			fclose($stderr);
			continue;
		}
		fwrite($pipes[0], json_encode(['tool_input' => ['file_path' => $file]], JSON_INVALID_UTF8_SUBSTITUTE));
		fclose($pipes[0]);
		$exitCode = proc_close($process);
		rewind($stdout);
		rewind($stderr);
		$output = stream_get_contents($stdout);
		$error = stream_get_contents($stderr);
		fclose($stdout);
		fclose($stderr);
		$result = json_decode($output, true);
		$additional = $result['hookSpecificOutput']['additionalContext'] ?? null;
		if (is_string($additional) && $additional !== '') {
			$context[] = $additional;
		}
		if ($exitCode !== 0) {
			$errors[] = trim($error) ?: "Hook failed on $file (exit $exitCode).\n$output";
		}
	}
	if ($errors) {
		// On exit 2 the client reads stderr, so include successful rewrite notices there too.
		fwrite(STDERR, implode("\n", array_merge($errors, $context)) . "\n");
		exit(2);
	}
	if ($context) {
		echo json_encode(['hookSpecificOutput' => [
			'hookEventName' => 'PostToolUse',
			'additionalContext' => implode("\n", $context),
		]], JSON_INVALID_UTF8_SUBSTITUTE);
	}
}

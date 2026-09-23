<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();


function runHookScript(string $scriptPath, array $input, array $phpOptions = []): array
{
	$process = proc_open(
		[PHP_BINARY, ...$phpOptions, $scriptPath],
		[
			0 => ['pipe', 'r'], // stdin
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr
		],
		$pipes,
	);

	fwrite($pipes[0], json_encode($input));
	fclose($pipes[0]);

	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[2]);

	$exitCode = proc_close($process);

	return [
		'exitCode' => $exitCode,
		'stdout' => $stdout,
		'stderr' => $stderr,
	];
}

<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';

$script = __DIR__ . '/../plugins/php-fixer/hooks/fix-php-style.php';
$root = str_replace('\\', '/', __DIR__ . '/temp/fix-php-style-legacy');
@mkdir($root, recursive: true);
Helpers::purge($root);

// a global Composer home with nothing but the ecs of nette/coding-standard, which the hook runs without php
$home = "$root/composer-home";
mkdir("$home/vendor/bin", recursive: true);
file_put_contents("$home/vendor/bin/ecs", <<<'PHP'
	#!/usr/bin/env php
	<?php
	file_put_contents(__DIR__ . '/calls.jsonl', json_encode($argv) . "\n", FILE_APPEND);
	if (basename($argv[2]) === 'failure.php') {
		echo "Remaining style violation\n";
		exit(1);
	}
	file_put_contents($argv[2], "<?php\n// fixed by ecs\n");
	PHP);
chmod("$home/vendor/bin/ecs", 0o755);
file_put_contents("$home/vendor/bin/ecs.bat", "@php \"%~dp0ecs\" %*\r\n");
putenv("COMPOSER_HOME=$home");

// the session markers of the hook go to the purged directory, not to the system temp
mkdir("$root/tmp");
foreach (['TMP', 'TEMP', 'TMPDIR'] as $name) {
	putenv("$name=$root/tmp");
}


/** A project directory: its own repository, so that no tool above it is found, with the files given. */
function project(string $root, string $name, array $files): string
{
	$dir = "$root/$name";
	foreach ($files + ['.git/HEAD' => '', 'composer.json' => '{}', 'src/a.php' => '<?php echo 1;'] as $path => $content) {
		@mkdir(dirname("$dir/$path"), recursive: true);
		file_put_contents("$dir/$path", $content);
	}
	return $dir;
}


function runOn(string $script, string $file, ?string $session = null): array
{
	return runHookScript($script, ['session_id' => $session, 'tool_input' => ['file_path' => $file]]);
}


function takeEcsCalls(string $home): array
{
	$file = "$home/vendor/bin/calls.jsonl";
	$calls = is_file($file) ? array_map(fn($line) => json_decode($line, true), file($file)) : [];
	@unlink($file);
	return $calls;
}


test('without DressCode the file is formatted by nette/coding-standard and the migration is offered once a session', function () use ($script, $root, $home) {
	$dir = project($root, 'legacy', []);
	$session = uniqid('test-', true);

	$result = runOn($script, "$dir/src/a.php", $session);
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('<?php' . "\n// fixed by ecs\n", file_get_contents("$dir/src/a.php"));
	Assert::match('The retired nette/coding-standard ran on %a%a.php this time. The php-fixer plugin now formats PHP with DressCode, which is not installed. %a%`migrate-to-dresscode`%a% This notice comes once a session.', json_decode($result['stdout'], true)['hookSpecificOutput']['additionalContext']);
	[$call] = takeEcsCalls($home);
	Assert::same(['fix', "$dir/src/a.php", '--config-file'], array_slice($call, 1, 3));
	Assert::match('%a%/hooks/ncs.php', str_replace('\\', '/', $call[4]));

	// the same session hears it once
	$result = runOn($script, "$dir/src/a.php", $session);
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::count(1, takeEcsCalls($home));
	Assert::count(1, glob("$root/tmp/nette-php-fixer-*"));

	// a client sending no session id hears it every time
	$result = runOn($script, "$dir/src/a.php");
	Assert::contains('migrate-to-dresscode', $result['stdout']);
	Assert::notContains('once a session', $result['stdout']);
	takeEcsCalls($home);
});


test('what nette/coding-standard cannot fix goes to Claude as an error with the notice', function () use ($script, $root, $home) {
	$dir = project($root, 'legacy-failure', ['src/failure.php' => '<?php echo 1;']);
	$result = runOn($script, "$dir/src/failure.php", uniqid('test-', true));
	Assert::same(2, $result['exitCode']);
	Assert::match("Could not fix all coding standard issues in %a%failure.php:\nRemaining style violation\nThe retired nette/coding-standard %a%", $result['stderr']);
	takeEcsCalls($home);
});


test('a project configured for DressCode is not formatted by nette/coding-standard', function () use ($script, $root, $home) {
	$dir = project($root, 'legacy-configured', ['dresscode.neon' => "use: nette\n"]);
	$result = runOn($script, "$dir/src/a.php", uniqid('test-', true));
	Assert::same(0, $result['exitCode']);
	Assert::match('%a%a.php was not formatted: its project is configured for DressCode. %a%migrate-to-dresscode%a%', $result['stdout']);
	Assert::same('<?php echo 1;', file_get_contents("$dir/src/a.php"));
	Assert::same([], takeEcsCalls($home));
});


test('DressCode wins over nette/coding-standard and says nothing about a migration', function () use ($script, $root, $home) {
	$dir = project($root, 'both', [
		'vendor/bin/dresscode' => "<?php\nfile_put_contents(\$argv[2], \"<?php\\n// fixed by dresscode\\n\");\necho \"DressCode changed `\$argv[2]`.\\n\";\n",
	]);
	$result = runOn($script, "$dir/src/a.php", uniqid('test-', true));
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::contains('// fixed by dresscode', file_get_contents("$dir/src/a.php"));
	Assert::notContains('migrate-to-dresscode', $result['stdout']);
	Assert::same([], takeEcsCalls($home));
});

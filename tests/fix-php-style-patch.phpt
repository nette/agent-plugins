<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';

$root = str_replace('\\', '/', __DIR__ . '/temp/fixer patch');
@mkdir($root, recursive: true);
Helpers::purge($root);
mkdir("$root/vendor/bin", recursive: true);
mkdir("$root/.git");
file_put_contents("$root/dresscode.neon", "use: nette\n");
file_put_contents("$root/vendor/bin/dresscode", <<<'PHP'
<?php
// A local Composer tool stand-in: exercise the real hook, independently of a global installation.
$file = $argv[2];
file_put_contents(__DIR__ . '/calls.jsonl', json_encode([$argv, getcwd()]) . "\n", FILE_APPEND);
switch (basename($file)) {
	case 'failure.php':
		echo "Left in `$file`:\n- line 1: a remaining style violation\n";
		fwrite(STDERR, "noise outside the report\n");
		exit(1);
	case 'config.php':
		fwrite(STDERR, "Error: Key `nope` is unknown.\n");
		exit(3);
	case 'silent.php':
		exit(2);
	case 'binary.php':
		echo "DressCode changed `$file`: \xFF\n";
		exit(0);
}
file_put_contents($file, "<?php\n// rewritten\n");
fwrite(STDERR, "noise outside the report\n");
echo "DressCode changed `$file`; the changes below are already in the file:\n\n```diff\n+// rewritten\n```\n";
PHP);
foreach (['one.php', 'two.php', 'failure.php', 'config.php', 'silent.php', 'binary.php', 'excluded.php'] as $file) {
	file_put_contents("$root/$file", '<?php echo 1;');
}
$script = __DIR__ . '/../plugins/php-fixer/hooks/fix-php-style.php';

function fixerPatch(string $root, array $files): array
{
	return [
		'tool_name' => 'apply_patch',
		'cwd' => $root,
		'tool_input' => ['command' => "*** Begin Patch\n"
			. implode("\n", array_map(fn($file) => "*** Update File: $file", $files))
			. "\n*** End Patch"],
	];
}

function runOn(string $script, string $file): array
{
	return runHookScript($script, ['tool_input' => ['file_path' => $file]]);
}

test('real fixer hook runs the project tool and aggregates what it says', function () use ($root, $script) {
	$result = runHookScript($script, fixerPatch($root, ['one.php', 'two.php']));
	Assert::same(0, $result['exitCode'], $result['stderr']);
	$context = json_decode($result['stdout'], true)['hookSpecificOutput']['additionalContext'];
	foreach (['one.php', 'two.php'] as $file) {
		Assert::contains("DressCode changed `$root/$file`", $context);
		Assert::contains('// rewritten', file_get_contents("$root/$file"));
	}
	Assert::notContains('noise', $context);
	foreach (file("$root/vendor/bin/calls.jsonl") as $line) {
		[$args, $cwd] = json_decode($line, true);
		Assert::same($root, str_replace('\\', '/', $cwd));
		Assert::same(['--format', 'agent', '--no-color', '--skip-excluded', '--set', 'mistakes.debugOutput=keep'], array_slice($args, 3));
	}
});

test('mixed failure and rewrite are both reported', function () use ($root, $script) {
	$result = runHookScript($script, fixerPatch($root, ['failure.php', 'two.php']));
	Assert::same(2, $result['exitCode']);
	Assert::contains("Left in `$root/failure.php`:\n- line 1: a remaining style violation", $result['stderr']);
	Assert::contains("DressCode changed `$root/two.php`", $result['stderr']);
});

test('the fix-php-style section of .nette-claude.json excludes nothing', function () use ($root, $script) {
	file_put_contents("$root/.nette-claude.json", '{"fix-php-style":{"exclude":["excluded.php"]}}');
	$result = runOn($script, "$root/excluded.php");
	unlink("$root/.nette-claude.json");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::contains('// rewritten', file_get_contents("$root/excluded.php"));
});

test('what DressCode writes on stdout reaches Claude unchanged, stderr not', function () use ($root, $script) {
	$result = runOn($script, "$root/one.php");
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
	Assert::same(['hookSpecificOutput' => [
		'hookEventName' => 'PostToolUse',
		'additionalContext' => "DressCode changed `$root/one.php`; the changes below are already in the file:\n\n```diff\n+// rewritten\n```",
	]], json_decode($result['stdout'], true));

	$result = runOn($script, "$root/failure.php");
	Assert::same(2, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::same("Left in `$root/failure.php`:\n- line 1: a remaining style violation\n", $result['stderr']);
});

test('stderr is passed on when DressCode failed and said nothing on stdout', function () use ($root, $script) {
	$result = runOn($script, "$root/config.php");
	Assert::same(2, $result['exitCode']);
	Assert::same("Error: Key `nope` is unknown.\n", $result['stderr']);

	$result = runOn($script, "$root/silent.php");
	Assert::same(2, $result['exitCode']);
	Assert::same("DressCode failed on $root/silent.php with exit code 2.\n", $result['stderr']);
});

test('invalid UTF-8 of the output does not lose the message', function () use ($root, $script) {
	$result = runOn($script, "$root/binary.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same("DressCode changed `$root/binary.php`: \u{FFFD}", json_decode($result['stdout'], true)['hookSpecificOutput']['additionalContext']);
});

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
if (basename($file) === 'failure.php') {
	echo "Remaining style violation in $file\n";
	exit(1);
}
file_put_contents($file, "<?php\n// rewritten\n");
echo "$file  rewritten\n";
PHP);
foreach (['one.php', 'two.php', 'failure.php', 'excluded.php'] as $file) {
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

test('real fixer hook runs the project tool and aggregates rewrite notices', function () use ($root, $script) {
	$result = runHookScript($script, fixerPatch($root, ['one.php', 'two.php']));
	Assert::same(0, $result['exitCode'], $result['stderr']);
	$context = json_decode($result['stdout'], true)['hookSpecificOutput']['additionalContext'];
	foreach (['one.php', 'two.php'] as $file) {
		Assert::contains("DressCode reformatted $root/$file", $context);
		Assert::contains('// rewritten', file_get_contents("$root/$file"));
	}
	foreach (file("$root/vendor/bin/calls.jsonl") as $line) {
		[$args, $cwd] = json_decode($line, true);
		Assert::same($root, str_replace('\\', '/', $cwd));
		Assert::contains('--skip-excluded', $args);
		Assert::contains('mistakes.debugOutput=keep', $args);
		Assert::notContains('--use', $args);
	}
});

test('mixed failure and rewrite are both reported', function () use ($root, $script) {
	$result = runHookScript($script, fixerPatch($root, ['failure.php', 'two.php']));
	Assert::same(2, $result['exitCode']);
	Assert::contains('DressCode could not fix everything', $result['stderr']);
	Assert::contains('Remaining style violation', $result['stderr']);
	Assert::contains("DressCode reformatted $root/two.php", $result['stderr']);
});


test('the fix-php-style section of .nette-claude.json excludes nothing', function () use ($root, $script) {
	file_put_contents("$root/.nette-claude.json", '{"fix-php-style":{"exclude":["excluded.php"]}}');
	$result = runHookScript($script, ['tool_input' => ['file_path' => "$root/excluded.php"]]);
	unlink("$root/.nette-claude.json");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::contains('// rewritten', file_get_contents("$root/excluded.php"));
});

test('Claude Code single-file feedback keeps its original shape', function () use ($root, $script) {
	$result = runHookScript($script, ['tool_input' => ['file_path' => "$root/one.php"]]);
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
	Assert::same(['hookSpecificOutput' => [
		'hookEventName' => 'PostToolUse',
		'additionalContext' => "DressCode reformatted $root/one.php after your edit. Read it again before editing it further.",
	]], json_decode($result['stdout'], true));
});

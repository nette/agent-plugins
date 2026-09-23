<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';

$root = str_replace('\\', '/', __DIR__ . '/temp/fixer patch');
@mkdir($root, recursive: true);
Helpers::purge($root);
mkdir("$root/composer/vendor/bin", recursive: true);
mkdir("$root/.git");
file_put_contents("$root/composer/vendor/bin/ecs", <<<'PHP'
#!/usr/bin/env php
<?php
// A global ECS stand-in: exercise the real hook, independently of a global installation.
$file = $argv[2];
file_put_contents(__DIR__ . '/calls.jsonl', json_encode([$argv, getcwd()]) . "\n", FILE_APPEND);
if (basename($file) === 'failure.php') {
	echo "Remaining style violation in $file\n";
	exit(1);
}
file_put_contents($file, "<?php\n// fixed\n");
PHP);
chmod("$root/composer/vendor/bin/ecs", 0755);
file_put_contents("$root/composer/vendor/bin/ecs.bat", "@php \"%~dp0ecs\" %*\r\n");
putenv("COMPOSER_HOME=$root/composer");
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

test('real fixer hook runs ECS on every file of the patch', function () use ($root, $script) {
	$result = runHookScript($script, fixerPatch($root, ['one.php', 'two.php']));
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('', $result['stdout']);
	foreach (['one.php', 'two.php'] as $file) {
		Assert::contains('// fixed', file_get_contents("$root/$file"));
	}
	$files = [];
	foreach (file("$root/composer/vendor/bin/calls.jsonl") as $line) {
		[$args] = json_decode($line, true);
		Assert::same('fix', $args[1]);
		Assert::contains('--config-file', $args);
		$files[] = str_replace('\\', '/', $args[2]);
	}
	Assert::same(["$root/one.php", "$root/two.php"], $files);
});

test('failure is reported and excluded files are untouched', function () use ($root, $script) {
	file_put_contents("$root/two.php", '<?php echo 1;');
	file_put_contents("$root/.nette-claude.json", '{"fix-php-style":{"exclude":["excluded.php"]}}');
	$result = runHookScript($script, fixerPatch($root, ['failure.php', 'two.php', 'excluded.php']));
	Assert::same(2, $result['exitCode']);
	Assert::contains("Could not fix all coding standard issues in $root/failure.php", $result['stderr']);
	Assert::contains('Remaining style violation', $result['stderr']);
	Assert::contains('// fixed', file_get_contents("$root/two.php"));
	Assert::same('<?php echo 1;', file_get_contents("$root/excluded.php"));
});

test('Claude Code single-file edit keeps its original shape', function () use ($root, $script) {
	$result = runHookScript($script, ['tool_input' => ['file_path' => "$root/one.php"]]);
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
	Assert::same('', $result['stdout']);
});

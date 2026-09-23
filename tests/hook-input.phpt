<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../plugins/nette-lint/hooks/hook-input.php';

$root = __DIR__ . '/temp/hook input';
@mkdir($root, recursive: true);
Helpers::purge($root);
$root = str_replace('\\', '/', $root);
$lint = __DIR__ . '/../plugins/nette-lint/hooks/lint-php.php';
file_put_contents("$root/valid.php", '<?php echo 1;');
file_put_contents("$root/broken one.php", '<?php function ( {');
file_put_contents("$root/broken two.phpt", '<?php function ( {');
register_shutdown_function(static fn() => unlink("$root/broken two.phpt"));
file_put_contents("$root/deleted.php", '<?php function ( {'); // a delete must be ignored even if a file exists
file_put_contents("$root/ignored.txt", '<?php function ( {');

function patchEvent(string $cwd, string $patch): array
{
	return [
		'hook_event_name' => 'PostToolUse',
		'tool_name' => 'apply_patch',
		'cwd' => $cwd,
		'tool_input' => ['command' => "*** Begin Patch\n$patch\n*** End Patch"],
	];
}

test('both independently installed plugins have the same adapter', function () {
	Assert::same(
		file_get_contents(__DIR__ . '/../plugins/nette-lint/hooks/hook-input.php'),
		file_get_contents(__DIR__ . '/../plugins/php-fixer/hooks/hook-input.php'),
	);
});

test('extracts destinations, skips deletions and does not mistake hunk text for headers', function () {
	Assert::same(['new.php', 'renamed.php', 'last.json'], patchFiles(implode("\r\n", [
		'*** Begin Patch',
		'*** Add File: new.php',
		'+*** Update File: fake.php',
		' *** Add File: also-fake.php',
		'*** Update File: old.php',
		'*** Move to: renamed.php',
		'@@',
		'-*** Move to: fake-rename.php',
		'*** Delete File: removed.php',
		'*** Add File: last.json',
		'+{}',
		'*** End Patch',
	])));
});

test('validates relative single-file patches from the event cwd', function () use ($root, $lint) {
	$result = runHookScript($lint, patchEvent($root, '*** Update File: broken one.php'));
	Assert::same(2, $result['exitCode']);
	Assert::contains('broken one.php', $result['stderr']);
	Assert::same(0, runHookScript($lint, patchEvent($root, '*** Add File: valid.php'))['exitCode']);
});

test('reports every matching file, skips other extensions, deletions and missing paths', function () use ($root, $lint) {
	$result = runHookScript($lint, patchEvent($root, implode("\n", [
		'*** Update File: broken one.php',
		'*** Add File: valid.php',
		'*** Update File: missing.php',
		'*** Update File: ignored.txt',
		'*** Delete File: deleted.php',
		'*** Update File: old.php',
		'*** Move to: broken two.phpt',
	])));
	Assert::same(2, $result['exitCode']);
	Assert::contains('broken one.php', $result['stderr']);
	Assert::contains('broken two.phpt', $result['stderr']);
	Assert::notContains('deleted.php', $result['stderr']);
	Assert::notContains('ignored.txt', $result['stderr']);
	Assert::notContains('missing.php', $result['stderr']);
});

test('accepts absolute paths and empty patches, ignores unrelated tools', function () use ($root, $lint) {
	Assert::same(2, runHookScript($lint, patchEvent($root, "*** Update File: $root/broken one.php"))['exitCode']);
	Assert::same(0, runHookScript($lint, patchEvent($root, ''))['exitCode']);
	$event = patchEvent($root, '*** Update File: broken one.php');
	$event['tool_name'] = 'Bash';
	Assert::same(0, runHookScript($lint, $event)['exitCode']);
});

test('uses the existing exclusion configuration for Codex patches', function () use ($root, $lint) {
	file_put_contents("$root/.nette-claude.json", '{"lint-php":{"exclude":["broken*"]}}');
	try {
		$result = runHookScript($lint, patchEvent($root, "*** Update File: broken one.php\n*** Update File: broken two.phpt"));
		Assert::same(['exitCode' => 0, 'stdout' => '', 'stderr' => ''], $result);
	} finally {
		unlink("$root/.nette-claude.json");
	}
});

// A stand-in hook tests aggregation and process setup without requiring external linters.
$helper = var_export(__DIR__ . '/../plugins/nette-lint/hooks/hook-input.php', true);
$mock = "$root/mock.php";
file_put_contents($mock, "<?php\nrequire $helper;\n" . <<<'PHP'
$file = readHookFile(__FILE__, ['php']);
if ($file === '') {
	exit(0);
}
file_put_contents(__DIR__ . '/calls.jsonl', json_encode([basename($file), ini_get('precision')]) . "\n", FILE_APPEND);
if (basename($file) === 'fail.php') {
	fwrite(STDERR, "Mock failure on $file\n");
	exit(2);
}
echo json_encode(['hookSpecificOutput' => ['hookEventName' => 'PostToolUse', 'additionalContext' => "Reformatted $file"]]);
PHP);
foreach (['first.php', 'second.php', 'fail.php'] as $file) {
	file_put_contents("$root/$file", '<?php');
}

test('aggregates rewrite notices and runs duplicate paths only once', function () use ($root, $mock) {
	$result = runHookScript($mock, patchEvent($root, "*** Update File: first.php\n*** Update File: second.php\n*** Update File: first.php"));
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
	$context = json_decode($result['stdout'], true)['hookSpecificOutput']['additionalContext'];
	Assert::same("Reformatted $root/first.php\nReformatted $root/second.php", $context);
	Assert::count(2, file("$root/calls.jsonl"));
});

test('retains rewrite feedback even when another file fails and continues after errors', function () use ($root, $mock) {
	$result = runHookScript($mock, patchEvent($root, "*** Update File: fail.php\n*** Update File: second.php"));
	Assert::same(2, $result['exitCode']);
	Assert::contains('Mock failure', $result['stderr']);
	Assert::contains('Reformatted', $result['stderr']);
	Assert::contains('second.php', $result['stderr']);
});

test('preserves the explicit PHP ini in child processes', function () use ($root, $mock) {
	file_put_contents("$root/php.ini", "precision=9\n");
	unlink("$root/calls.jsonl");
	$result = runHookScript($mock, patchEvent($root, "*** Update File: first.php\n*** Update File: second.php"), ['-c', "$root/php.ini"]);
	Assert::same(0, $result['exitCode'], $result['stderr']);
	foreach (file("$root/calls.jsonl") as $line) {
		Assert::same('9', json_decode($line, true)[1]);
	}
});

test('all JSON files in a patch use the existing JSON validator', function () use ($root) {
	file_put_contents("$root/bad.json", '{broken');
	file_put_contents("$root/tsconfig.json", '{"x": 1,}');
	$result = runHookScript(__DIR__ . '/../plugins/nette-lint/hooks/lint-json.php', patchEvent($root, "*** Update File: bad.json\n*** Update File: tsconfig.json"));
	Assert::same(2, $result['exitCode']);
	Assert::contains('bad.json', $result['stderr']);
	Assert::notContains('tsconfig.json', $result['stderr']);
});

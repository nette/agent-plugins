<?php declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;
use Tester\Helpers;

require __DIR__ . '/bootstrap.php';

$scriptPath = __DIR__ . '/../plugins/php-fixer/hooks/fix-php-style.php';
$fixturesDir = __DIR__ . '/fixtures';
$root = __DIR__ . '/temp/fix-php-style';
@mkdir($root, recursive: true);
Helpers::purge($root);

$dirty = "<?php\nnamespace X;\nclass A { function f(\$a){ return \$a == null ? 1: 2; } }\n";
$dirtyNette = "<?php\ndeclare(strict_types=1);\nnamespace X;\nclass A { function f(\$a){ return \$a === null ? 1: 2; } }\n"; // nothing left to the user


/**
 * The global DressCode the hook falls back to, found the way the hook finds it; the runs below need one.
 */
function findGlobalDressCode(): ?string
{
	$home = getenv('HOME');
	$dirs = PHP_OS_FAMILY === 'Windows'
		? [getenv('COMPOSER_HOME'), getenv('APPDATA') ? getenv('APPDATA') . '/Composer' : null]
		: [getenv('COMPOSER_HOME'), (getenv('XDG_CONFIG_HOME') ?: ($home ? "$home/.config" : null)) . '/composer', $home ? "$home/.composer" : null];
	foreach (array_filter($dirs) as $dir) {
		if (is_file("$dir/vendor/bin/dresscode")) {
			return "$dir/vendor/bin/dresscode";
		}
	}
	return null;
}


/** A project directory: its own repository, so that the hook runs from there, with the files given. */
function project(string $root, string $name, array $files): string
{
	$dir = "$root/$name";
	foreach ($files + ['.git/HEAD' => ''] as $path => $content) {
		@mkdir(dirname("$dir/$path"), recursive: true);
		file_put_contents("$dir/$path", $content);
	}
	return $dir;
}


function runOn(string $scriptPath, string $file): array
{
	return runHookScript($scriptPath, ['tool_input' => ['file_path' => $file]]);
}


function skipWithoutDressCode(): void
{
	if (findGlobalDressCode() === null) {
		Environment::skip('DressCode is not installed globally.');
	}
}


test('skips non-php files', function () use ($scriptPath, $fixturesDir) {
	$result = runOn($scriptPath, $fixturesDir . '/test.latte');
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::same('', $result['stderr']);
});


test('skips when file does not exist', function () use ($scriptPath, $fixturesDir) {
	$result = runOn($scriptPath, $fixturesDir . '/nonexistent.php');
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
});


test('handles empty input gracefully', function () use ($scriptPath) {
	$result = runHookScript($scriptPath, []);
	Assert::same(0, $result['exitCode']);
});


test('handles missing tool_input gracefully', function () use ($scriptPath) {
	$result = runHookScript($scriptPath, ['cwd' => '/tmp']);
	Assert::same(0, $result['exitCode']);
});


test('skips silently when DressCode is installed neither in the project nor globally', function () use ($scriptPath, $root, $dirty) {
	$dir = project($root, 'none', ['src/a.php' => $dirty]);

	// the hook must not find the global one through the environment
	$env = ['APPDATA' => getenv('APPDATA'), 'COMPOSER_HOME' => getenv('COMPOSER_HOME'), 'HOME' => getenv('HOME'), 'XDG_CONFIG_HOME' => getenv('XDG_CONFIG_HOME')];
	putenv('APPDATA=');
	putenv('COMPOSER_HOME=');
	putenv('HOME=/nonexistent');
	putenv('XDG_CONFIG_HOME=');
	try {
		$result = runOn($scriptPath, "$dir/src/a.php");
	} finally {
		foreach ($env as $name => $value) {
			putenv($value === false ? $name : "$name=$value");
		}
	}

	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::same('', $result['stderr']);
	Assert::same($dirty, file_get_contents("$dir/src/a.php"));
});


test('a project without a configuration is fixed by the Nette coding standard, and Claude is told the file changed', function () use ($scriptPath, $root, $dirtyNette) {
	skipWithoutDressCode();
	$dir = project($root, 'plain', ['composer.json' => '{"require": {"php": "^8.3"}}', 'src/a.php' => $dirtyNette]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('', $result['stderr']);
	$output = json_decode($result['stdout'], associative: true);
	Assert::same('PostToolUse', $output['hookSpecificOutput']['hookEventName'] ?? null);
	Assert::match('DressCode reformatted %a%a.php after your edit.%a%', $output['hookSpecificOutput']['additionalContext'] ?? '');
	Assert::match("<?php declare(strict_types=1);\n%A%\tpublic function f(\$a)\n%A%", file_get_contents("$dir/src/a.php")); // tabs, the declare on the tag line

	// the second run has nothing to say
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stdout']);
});


test('the configuration of the project wins over the Nette coding standard', function () use ($scriptPath, $root, $dirty) {
	skipWithoutDressCode();
	$dir = project($root, 'configured', [
		'dresscode.neon' => "use: perCs\nexcludePaths: [fixtures]\n",
		'src/a.php' => $dirty,
		'fixtures/b.php' => $dirty,
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::match('%A%reformatted%A%', $result['stdout']);
	Assert::match("<?php\n\nnamespace X;\n%A%    public function f(\$a)\n%A%", file_get_contents("$dir/src/a.php")); // four spaces, no declare

	// what the configuration excludes stays as it was written
	$result = runOn($scriptPath, "$dir/fixtures/b.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('', $result['stdout']);
	Assert::same($dirty, file_get_contents("$dir/fixtures/b.php"));
});


test('what DressCode cannot fix goes to Claude as an error', function () use ($scriptPath, $root) {
	skipWithoutDressCode();
	$dir = project($root, 'unfixable', [
		'composer.json' => '{"require": {"php": "^8.3"}}',
		'src/a.php' => "<?php declare(strict_types=1);\n\nfunction my_func(\$x): void\n{\n\tglobal \$y;\n\tdump(\$x);\n}\n",
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::match("DressCode could not fix everything in %a%a.php, the rest is up to you:\n%A%naming.function\n%A%mistakes.globalStatement\n", $result['stderr']);
	Assert::contains("\tdump(\$x);", file_get_contents("$dir/src/a.php")); // the call stays while editing
});


test('a file both rewritten and left with violations is reported as rewritten too', function () use ($scriptPath, $root, $dirty) {
	skipWithoutDressCode();
	$dir = project($root, 'rewritten-unfixable', ['composer.json' => '{"require": {"php": "^8.3"}}', 'src/a.php' => $dirty]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::match("DressCode could not fix everything in %a%a.php, the rest is up to you:\n%A%file.strictTypes.declaration\n%A%DressCode reformatted %a%a.php after your edit.%a%", $result['stderr']);
	Assert::notContains('  rewritten', $result['stderr']);
});


test('a warning of the configuration is passed on and is not a rewrite', function () use ($scriptPath, $root) {
	skipWithoutDressCode();
	$dir = project($root, 'warning', [
		'composer.json' => '{"require": {"php": "^8.0"}}',
		'dresscode.neon' => "use: perCs\nclasses: {instantiationWrapping: forbidden}\n",
		'src/a.php' => "<?php\n\n\$a = 1;\n",
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	$context = json_decode($result['stdout'], associative: true)['hookSpecificOutput']['additionalContext'] ?? '';
	Assert::match("DressCode warns about %a%a.php:\nWarning: Decision `classes.instantiationWrapping` needs PHP >=8.4%a%", $context);
	Assert::notContains('reformatted', $context);
});


test('a broken configuration goes to Claude as a failure', function () use ($scriptPath, $root, $dirty) {
	skipWithoutDressCode();
	$dir = project($root, 'broken', ['dresscode.neon' => "nope: true\n", 'src/a.php' => $dirty]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::match("DressCode failed on %a%a.php:\nError: Key `nope` is unknown.\n", $result['stderr']);
	Assert::same($dirty, file_get_contents("$dir/src/a.php"));
});


test("the project's own DressCode is preferred to the global one", function () use ($scriptPath, $root, $dirtyNette) {
	skipWithoutDressCode();
	$dir = project($root, 'own', [
		'src/a.php' => $dirtyNette,
		// a stand-in for the bin proxy Composer writes, leaving a mark that it ran
		'vendor/bin/dresscode' => "<?php\nfile_put_contents(__DIR__ . '/ran', '1');\nrequire " . var_export(findGlobalDressCode(), true) . ";\n",
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::true(is_file("$dir/vendor/bin/ran"));
	Assert::match('%A%reformatted%A%', $result['stdout']);
});

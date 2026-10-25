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
 * The DressCode the runs below need: the binary DRESSCODE_BIN names (a working tree of DressCode),
 * otherwise the global one, found the way the hook finds it.
 */
function findDressCode(): ?string
{
	if ($bin = getenv('DRESSCODE_BIN')) {
		return is_file($bin) ? $bin : null;
	}
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


// the hook finds that DressCode as the global one, in a Composer home of the test's own
$dressCode = findDressCode();
if ($dressCode !== null) {
	mkdir("$root/composer-home/vendor/bin", recursive: true);
	file_put_contents("$root/composer-home/vendor/bin/dresscode", "<?php\nrequire " . var_export($dressCode, true) . ";\n");
	putenv("COMPOSER_HOME=$root/composer-home");
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


function skipWithoutDressCode(?string $dressCode): void
{
	if ($dressCode === null) {
		Environment::skip('DressCode is not installed globally and DRESSCODE_BIN names none.');
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
	$dir = project($root, 'none', ['composer.json' => '{}', 'src/a.php' => $dirty]);

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


test('a file of no PHP project is left as it was written, a repository not being one', function () use ($scriptPath, $root, $dirty, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'scratch', ['a.php' => $dirty]);
	$result = runOn($scriptPath, "$dir/a.php");
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::same('', $result['stderr']);
	Assert::same($dirty, file_get_contents("$dir/a.php"));
});


test('a project without a configuration is fixed by the Nette coding standard, and Claude gets the changes', function () use ($scriptPath, $root, $dirtyNette, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'plain', ['composer.json' => '{"require": {"php": "^8.3"}}', 'src/a.php' => $dirtyNette]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('', $result['stderr']);
	$output = json_decode($result['stdout'], associative: true);
	Assert::same('PostToolUse', $output['hookSpecificOutput']['hookEventName'] ?? null);
	Assert::match("DressCode changed `%a%a.php`; the changes below are already in the file:\n\n```diff\n%A%\n```%A?%", $output['hookSpecificOutput']['additionalContext'] ?? '');
	Assert::match("<?php declare(strict_types=1);\n%A%\tpublic function f(\$a)\n%A%", file_get_contents("$dir/src/a.php")); // tabs, the declare on the tag line

	// the second run has nothing to say
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::same('', $result['stderr']);
});


test('the configuration of the project wins over the Nette coding standard', function () use ($scriptPath, $root, $dirty, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'configured', [
		'dresscode.neon' => "use: perCs\nexcludePaths: [fixtures]\n",
		'src/a.php' => $dirty,
		'fixtures/b.php' => $dirty,
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::contains('DressCode changed', $result['stdout']);
	Assert::match("<?php\n\nnamespace X;\n%A%    public function f(\$a)\n%A%", file_get_contents("$dir/src/a.php")); // four spaces, no declare

	// what the configuration excludes stays as it was written
	$result = runOn($scriptPath, "$dir/fixtures/b.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('', $result['stdout']);
	Assert::same($dirty, file_get_contents("$dir/fixtures/b.php"));
});


test('what DressCode cannot fix goes to Claude as an error, in its own words', function () use ($scriptPath, $root, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'unfixable', [
		'composer.json' => '{"require": {"php": "^8.3"}}',
		'src/a.php' => "<?php declare(strict_types=1);\n\nfunction my_func(\$x): void\n{\n\tglobal \$y;\n\tdump(\$x);\n}\n",
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::same('', $result['stdout']);
	Assert::match("Left in `%a%a.php`, at the lines of the file as it is now:\n%A%(`naming.function`)%A%(`mistakes.globalStatement`)%A%", $result['stderr']);
	Assert::notContains('DressCode changed', $result['stderr']);
	Assert::contains("\tdump(\$x);", file_get_contents("$dir/src/a.php")); // the call stays while editing
});


test('what is left is listed for the lines the edit wrote, the rest counted', function () use ($scriptPath, $root, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'ranged', [
		'composer.json' => '{"require": {"php": "^8.3"}}',
		'src/a.php' => "<?php declare(strict_types=1);\n\nfunction my_func(\$x): void\n{\n\tglobal \$y;\n}\n",
	]);
	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => "$dir/src/a.php"],
		'tool_response' => ['structuredPatch' => [
			['oldStart' => 4, 'oldLines' => 2, 'newStart' => 4, 'newLines' => 3, 'lines' => [' {', '+	global $y;', ' }']],
		]],
	]);
	Assert::same(2, $result['exitCode']);
	Assert::match("Left in `%a%a.php`, at the lines of the file as it is now:\n- line 5: %a%(`mistakes.globalStatement`)%A%\n\n1 further violation is outside the edited lines; it is not listed here.\n%A?%", $result['stderr']);
	Assert::notContains('naming.function', $result['stderr']);
});


test('what the file broke before the edit stays as the file writes it', function () use ($scriptPath, $root, $dressCode) {
	skipWithoutDressCode($dressCode);
	$original = "<?php declare(strict_types=1);\n\nfunction run(): void\n{\n    echo 1;\n}\n";
	$edited = "<?php declare(strict_types=1);\n\nfunction run(): void\n{\n    echo 1;\n    echo 2;\n}\n";
	$dir = project($root, 'before', ['composer.json' => '{"require": {"php": "^8.3"}}', 'src/a.php' => $edited]);
	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => "$dir/src/a.php"],
		'tool_response' => [
			'originalFile' => $original,
			'structuredPatch' => [
				['oldStart' => 5, 'oldLines' => 1, 'newStart' => 5, 'newLines' => 2, 'lines' => ['     echo 1;', '+    echo 2;']],
			],
		],
	]);
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same($edited, file_get_contents("$dir/src/a.php")); // the indentation by spaces stays
	Assert::contains('Before the edit', $result['stdout']);
	Assert::contains('`indentation.unit`', $result['stdout']);
});


test('a file both rewritten and left with violations is reported as rewritten too', function () use ($scriptPath, $root, $dirty, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'rewritten-unfixable', ['composer.json' => '{"require": {"php": "^8.3"}}', 'src/a.php' => $dirty]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::match("DressCode changed `%a%a.php`; %A%\nLeft in `%a%a.php`, %A%(`file.strictTypes.declaration`)%A%", $result['stderr']);
});


test('a file just created gets the declaration of strict types the standard requires', function () use ($scriptPath, $root, $dressCode) {
	skipWithoutDressCode($dressCode);
	$code = "<?php\nnamespace X;\n\nfunction f(\$a)\n{\n\treturn strlen(\$a);\n}\n";
	$dir = project($root, 'created', ['composer.json' => '{"require": {"php": "^8.3"}}', 'src/a.php' => $code]);
	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => "$dir/src/a.php"],
		'tool_response' => ['type' => 'create', 'originalFile' => null],
	]);
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::match("<?php declare(strict_types=1);\n%A%", file_get_contents("$dir/src/a.php"));

	// an edit of a file that existed leaves the declaration to Claude
	file_put_contents("$dir/src/a.php", $code);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::contains('`file.strictTypes.declaration`', $result['stderr']);
});


test('a warning of the configuration is not passed on', function () use ($scriptPath, $root, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'warning', [
		'composer.json' => '{"require": {"php": "^8.0"}}',
		'dresscode.neon' => "use: perCs\nclasses: {instantiationWrapping: forbidden}\n",
		'src/a.php' => "<?php\n\n\$a = 1;\n",
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::same('', $result['stdout']);
	Assert::same('', $result['stderr']);
});


test('a broken configuration goes to Claude as what DressCode says on stderr', function () use ($scriptPath, $root, $dirty, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'broken', ['dresscode.neon' => "nope: true\n", 'src/a.php' => $dirty]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(2, $result['exitCode']);
	Assert::match("Error: Key `nope` is unknown.%A?%\n", $result['stderr']);
	Assert::same($dirty, file_get_contents("$dir/src/a.php"));
});


test("the project's own DressCode is preferred to the global one", function () use ($scriptPath, $root, $dirtyNette, $dressCode) {
	skipWithoutDressCode($dressCode);
	$dir = project($root, 'own', [
		// without it the file belongs to no PHP project
		'composer.json' => '{}',
		'src/a.php' => $dirtyNette,
		// a stand-in for the bin proxy Composer writes, leaving a mark that it ran
		'vendor/bin/dresscode' => "<?php\nfile_put_contents(__DIR__ . '/ran', '1');\nrequire " . var_export($dressCode, true) . ";\n",
	]);
	$result = runOn($scriptPath, "$dir/src/a.php");
	Assert::same(0, $result['exitCode'], $result['stderr']);
	Assert::true(is_file("$dir/vendor/bin/ran"));
	Assert::contains('DressCode changed', $result['stdout']);
});

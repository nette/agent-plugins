<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../plugins/nette-lint/hooks/hook-config.php';


function createTempStructure(array $files): string
{
	$root = sys_get_temp_dir() . '/nette-claude-' . uniqid();
	foreach ($files as $path => $content) {
		$full = $root . '/' . $path;
		@mkdir(dirname($full), 0777, true);
		file_put_contents($full, $content);
	}
	return $root;
}


test('bare name matches any path segment', function () {
	Assert::true(matchesPattern('fixtures', 'fixtures/bad.neon'));
	Assert::true(matchesPattern('fixtures', 'app/tests/fixtures/x.php'));
	Assert::false(matchesPattern('fixtures', 'app/fixturesX/x.php'));
	Assert::false(matchesPattern('fixtures', 'app/model/x.php'));
});


test('slashless wildcard matches a segment at any depth', function () {
	Assert::true(matchesPattern('fixtures*', 'Tracy/tests/Tracy/fixtures/x.php'));
	Assert::true(matchesPattern('fixtures*', 'Utils/tests/Utils/fixtures.finder2/x.php'));
	Assert::true(matchesPattern('fixtures*', 'fixtures/x.php'));
	Assert::false(matchesPattern('fixtures*', 'app/myfixtures/x.php'));
	Assert::false(matchesPattern('fixtures*', 'app/model/x.php'));
});


test('glob with ** spans directories', function () {
	Assert::true(matchesPattern('tests/**/broken', 'tests/a/b/broken/file.latte'));
	Assert::true(matchesPattern('tests/**/broken', 'tests/broken'));
	Assert::false(matchesPattern('tests/**/broken', 'app/tests/x/broken'));
});


test('single * stays within one segment', function () {
	Assert::true(matchesPattern('tests/*.php', 'tests/foo.php'));
	Assert::false(matchesPattern('tests/*.php', 'tests/sub/foo.php'));
});


test('matching a directory excludes its contents', function () {
	Assert::true(matchesPattern('tests/temp/**', 'tests/temp/x/y.php'));
	Assert::true(matchesPattern('tests/temp', 'tests/temp/x/y.php'));
});


test('leading slash anchors to the config directory', function () {
	// unanchored: matches at any depth
	Assert::true(matchesPattern('build', 'app/build/x.js'));
	// anchored: only at the root of the config dir
	Assert::true(matchesPattern('/build', 'build/x.js'));
	Assert::false(matchesPattern('/build', 'app/build/x.js'));
});


test('isExcluded finds config upwards and honours the hook name', function () {
	$root = createTempStructure([
		'.nette-claude.json' => json_encode([
			'lint-neon' => ['exclude' => ['fixtures']],
			'lint-php' => ['exclude' => ['tests/temp/**']],
		]),
		'app/fixtures/bad.neon' => 'x',
		'app/config/app.neon' => 'x',
		'tests/temp/foo.php' => '<?php',
		'src/Model.php' => '<?php',
	]);

	// lint-neon: fixtures excluded anywhere
	Assert::true(isExcluded($root . '/app/fixtures/bad.neon', 'lint-neon'));
	Assert::false(isExcluded($root . '/app/config/app.neon', 'lint-neon'));

	// hook isolation: the fixtures rule does not apply to lint-php
	Assert::false(isExcluded($root . '/app/fixtures/whatever.php', 'lint-php'));

	// lint-php: tests/temp/** relative to the config directory
	Assert::true(isExcluded($root . '/tests/temp/foo.php', 'lint-php'));
	Assert::false(isExcluded($root . '/src/Model.php', 'lint-php'));

	// hook without any config section -> not excluded
	Assert::false(isExcluded($root . '/app/fixtures/bad.neon', 'lint-latte'));
});


test('isExcluded returns false when no config exists', function () {
	$root = createTempStructure([
		'src/Model.php' => '<?php',
	]);
	Assert::false(isExcluded($root . '/src/Model.php', 'lint-php'));
});


test('malformed config is tolerated without warnings', function () {
	// invalid JSON, non-array section and non-array exclude must all yield false, no warnings
	foreach (['{ this is not json', '[]', '{"lint-php": "nope"}', '{"lint-php": {"exclude": "nope"}}', '{"lint-php": {"exclude": [["x"], 123]}}', ''] as $i => $json) {
		$root = createTempStructure([
			'.nette-claude.json' => $json,
			'src/x.php' => '<?php',
		]);
		Assert::false(isExcluded($root . '/src/x.php', 'lint-php'), "case $i");
	}
});


test('nearest config wins and patterns are relative to it', function () {
	$root = createTempStructure([
		'.nette-claude.json' => json_encode(['lint-neon' => ['exclude' => ['top']]]),
		'pkg/.nette-claude.json' => json_encode(['lint-neon' => ['exclude' => ['inner']]]),
		'pkg/inner/a.neon' => 'x',
		'pkg/top/b.neon' => 'x',
	]);

	Assert::true(isExcluded($root . '/pkg/inner/a.neon', 'lint-neon'));
	Assert::false(isExcluded($root . '/pkg/top/b.neon', 'lint-neon'));
});

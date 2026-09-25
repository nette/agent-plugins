# Nette Command-Line

Contents:

- [Defining and Parsing Arguments](#defining-and-parsing-arguments)
  - [Flags, Options, and Arguments](#flags-options-and-arguments)
  - [Default Values](#default-values)
  - [Restricting Values](#restricting-values)
  - [Repeatable Parameters](#repeatable-parameters)
  - [Transforming Values](#transforming-values)
  - [Validated Values](#validated-values)
- [Commands](#commands)
- [Printing Help](#printing-help)
  - [Handling –help and –version](#handlinghelp-andversion)
- [Error Handling](#error-handling)
- [Colorful Output](#colorful-output)
  - [Status Lines](#status-lines)
  - [Measuring Text](#measuring-text)
- [Upgrading from 1.x](#upgrading-from-1x)


A lightweight library for building command-line applications in PHP. It parses flags, options, positional arguments and commands, generates help that always matches what your script accepts, and helps you produce colorful terminal output.

Installation:

```shell
composer require nette/command-line
```

It requires PHP version 8.2 and supports PHP up to 8.5.

## Defining and Parsing Arguments

Every CLI script needs to handle arguments like `--verbose`, `-o output.txt`, or plain file names. The library splits this job into four classes, each of which does one thing:

- [Nette\CommandLine\Command](https://api.nette.org/commandline/master/Nette/CommandLine/Command.html) describes what your script accepts,
- [Nette\CommandLine\Parser](https://api.nette.org/commandline/master/Nette/CommandLine/Parser.html) reads the command line according to that description,
- [Nette\CommandLine\HelpRenderer](https://api.nette.org/commandline/master/Nette/CommandLine/HelpRenderer.html) draws the help from it,
- [Nette\CommandLine\Console](https://api.nette.org/commandline/master/Nette/CommandLine/Console.html) writes to the terminal.

Let's define a small script that converts files:

```php
use Nette\CommandLine\Command;
use Nette\CommandLine\Parser;

$command = new Command('convert');
$command->addFlag('--verbose', 'Enable verbose mode', alias: '-v');
$command->addOption('--output', 'Output file', alias: '-o');
$command->addArgument('input', 'File to convert');

$args = (new Parser)->parse($command);
```

Each `add*()` method takes the name, the description, and then everything else about the parameter as a named argument. It returns the parameter it created, whose settings you can read back as properties, such as `$option->alias`. A parameter cannot be changed afterwards, so it is checked the moment it is added. To reach it again later, call `$command->getParameter('--output')`.

The `parse()` method returns a [Nette\CommandLine\ParseResult](https://api.nette.org/commandline/master/Nette/CommandLine/ParseResult.html) object, which you read just like an array. The keys are the names exactly as you defined them, including the dashes:

```php
$args['--verbose'];  // true, or null if not used
$args['--output'];   // 'file.txt', or null if not used
$args['input'];      // 'photo.png'
```

Unlike an array, asking for a name you never defined throws an exception, so a typo can't quietly pass for a value that just wasn't given. `isset()` behaves as it does on an array, returning `false` both for an unknown name and for a `null` value. When you need all the values at once, call `toArray()`.

By default, `parse()` reads `$_SERVER['argv']`. You can also pass the tokens yourself, without the script name, which is handy for testing:

```php
$args = (new Parser)->parse($command, ['--verbose', '-o', 'out.txt', 'photo.png']);
```

The `isEmpty()` method tells you that the user ran the script with nothing after it, and `wasProvided('--output')` tells you whether one parameter was really given, so a value equal to the default can be told from the default itself.

### Flags, Options, and Arguments

There are three types of command-line inputs.

**Flags** are named inputs without values, like `--verbose` or `-v`. They parse as `true` when present and `null` when absent:

```php
$command->addFlag('--verbose', alias: '-v');
// --verbose  → true
// -v         → true
// (not used) → null
```

With `negatable: true`, a flag with a long name can also be turned off. Together with a default value you get a flag that is on unless the user says otherwise:

```php
$command->addFlag('--color', negatable: true, default: true);
// --color     → true
// --no-color  → false
// (not used)  → true
```

**Options** accept values, like `--output file.txt`. The value can be separated by a space or by `=`:

```php
$command->addOption('--output', alias: '-o');
// --output file.txt    → 'file.txt'
// --output=file.txt    → 'file.txt'
// -o file.txt          → 'file.txt'
// --output             → throws an exception (value required)
// (not used)           → null
```

The option itself is always optional, but once it is used, its value is required. Pass `valueOptional: true` to allow the option without a value; it then parses as `true`. Such a value is attached with `=`, so the next token isn't taken for it and can't be swallowed:

```php
$command->addOption('--format', valueOptional: true);
// --format=json  → 'json'
// --format       → true
// --format json  → true, and json is an argument
// (not used)     → null
```

With `enum` the next token is taken after all, but only when it is one of the allowed values. Both spellings of `--sort time` then work, and nothing outside the list can be swallowed:

```php
$command->addOption('--sort', valueOptional: true, enum: ['name', 'time']);
// --sort=time  → 'time'
// --sort time  → 'time'
// --sort       → true
// --sort src   → true, and src is an argument
```

Such a value is taken even where it would otherwise be a command or an argument. That's why the definition refuses a command named like one of the values, and an argument equal to one of them has to be written after `--`, as in `--sort -- time`.

When the same option is used several times, the last value wins, unless it is repeatable. The earlier values are neither checked nor converted.

**Arguments** are positional values without dashes. They are required unless you pass `optional: true`:

```php
$command->addArgument('input');
// file.txt    → 'file.txt'
// (not used)  → throws an exception

$command->addArgument('output', optional: true);
// (not used)  → null
```

Arguments can appear anywhere on the command line, before or after the options. Everything after a lone `--` is treated as an argument even if it starts with a dash, and a lone `-` is always a value (by convention it means stdin or stdout). A negative number such as `-5` or `-.5` is a value too, so you pass it to an option or an argument as usual:

```php
// script.php -- --weird   → input = '--weird'
// script.php -            → input = '-'
// script.php -5           → input = '-5'
// script.php --offset -5  → --offset = '-5'
```

The one exception is a command that accepts an option named like a number, such as `-1` in `ls -1`. There every token like `-5` is taken for an option, never for a value, just as in Python's argparse. A negative value is then given to an option with `=`, as in `--offset=-5`, and to an argument after `--`.

Several single-letter flags can be written as one token: `-va` means `-v -a`, and the last letter may even take a value, so `-vao out.txt` means `-v -a -o out.txt`.

A flag has no value, so `addFlag()` offers none of the settings of a value. `enum` and `normalizer` belong to `addOption()` and `addArgument()`, while `valueOptional` and `valueName` belong to `addOption()` alone.

### Default Values

`default` says what an option or an argument is worth when it is **not** on the command line. It is independent of `valueOptional`, which says what happens when the option **is** there without a value:

```php
$command->addOption('--format', default: 'json');
// --format xml  → 'xml'
// --format      → throws an exception (value required)
// (not used)    → 'json'

$command->addOption('--format', valueOptional: true, default: 'json');
// --format=xml  → 'xml'
// --format      → true
// (not used)    → 'json'
```

The default value is returned exactly as you gave it, so it isn't normalized or checked against the allowed values. A required argument with a default value would make no sense, so the parser refuses it; mark such an argument optional.

### Restricting Values

`enum` limits the accepted values to a list:

```php
$command->addOption('--format', enum: ['json', 'xml', 'csv']);
// --format yaml  → throws "Option --format: expects json, xml or csv, 'yaml' given."
```

The values are non-empty strings, so write numbers as strings too, such as `['1', '2']`. Instead of a list you can give it a backed enum, and the value then parses directly as the matching case:

```php
enum Format: string
{
	case Json = 'json';
	case Xml = 'xml';
}

$command->addOption('--format', enum: Format::class, default: Format::Json);
// --format xml  → Format::Xml
// (not used)    → Format::Json
```

### Repeatable Parameters

Pass `repeatable: true` to collect all the values into an array:

```php
$command->addOption('--include', alias: '-I', repeatable: true);
// -I src -I lib  → ['src', 'lib']
// (not used)     → []

$command->addArgument('files', optional: true, repeatable: true);
// a.txt b.txt    → ['a.txt', 'b.txt']
```

A repeatable argument takes all the remaining values, so it has to be the last one.

### Transforming Values

A normalizer converts the value read from the command line. The ready-made ones live in [Nette\CommandLine\Normalizers](https://api.nette.org/commandline/master/Nette/CommandLine/Normalizers.html): `int()` and `float()` convert a number and refuse anything else, optionally within a range, and `realPath()` resolves a path. Your own normalizer is any closure:

```php
use Nette\CommandLine\Normalizers;

$command->addOption('--count', normalizer: Normalizers::int(min: 1));
// --count 42   → 42
// --count abc  → throws "Option --count: expects an integer, 'abc' given."
// --count 0    → throws "Option --count: expects at least 1, 0 given."
```

It runs only on values that really come from the command line, never on the default value, nor on the `true` of an option used without its optional value. When the value is wrong, the normalizer simply throws an exception. The parser turns it into a `ParseException` naming the parameter, so the user sees a sentence instead of a stack trace. A range that makes no sense, such as `min` greater than `max`, is a mistake of the program rather than of the user, so `int()` and `float()` refuse it as soon as you create the normalizer.

`realPath()` resolves the value to an absolute path and refuses a path that doesn't exist:

```php
$command->addOption('--config', normalizer: Normalizers::realPath());
// --config app.ini      → '/full/path/to/app.ini'
// --config missing.ini  → throws "Option --config: file path 'missing.ini' not found."
```

To change the path before it is resolved, call the ready-made normalizer from your own:

```php
$realPath = Normalizers::realPath();
$command->addOption('--config', normalizer: fn($v) => $realPath("config/$v"));
```

### Validated Values

When you need thoroughly validated values, hand the result to [Nette Schema](../schema/index.md) as a second step; the library itself stays dependency-free. The parser returns strings as they were written and `null` for what was not used, so leave the `null` values out to let the defaults of the schema apply:

```php
use Nette\Schema\Expect;
use Nette\Schema\Processor;

$options = (new Processor)->process(Expect::structure([
	'--jobs' => Expect::string()->pattern('[1-9][0-9]*')->castTo('int')->default(8),
	'paths' => Expect::listOf('string'),
])->otherItems(), array_filter($args->toArray(), fn($value) => $value !== null));
```

## Commands

Tools like `git` or `composer` have commands: `git commit`, `composer install`. Each command has its own options and arguments. You add one with `addCommand()`, which returns a new `Command` that you configure just like the program itself:

```php
$cli = new Command('dresscode');
$cli->addFlag('--help', 'Print this help', alias: '-h', standalone: true);
$cli->addOption('--config', 'Configuration file', alias: '-c', normalizer: Normalizers::realPath());

$check = $cli->addCommand('check', 'Report violations');
$check->addArgument('paths', 'Files or directories', optional: true, repeatable: true);
$check->addFlag('--generate-baseline', 'Write the violations into the baseline');

$explain = $cli->addCommand('explain', 'What a rule is for');
$explain->addArgument('rule', 'Name of the rule');

$args = (new Parser)->parse($cli);
```

The command is picked by the first positional word on the command line, and `$args->command` tells you which one it was. When the user names no command, it is the program itself, unless you create it with `commandRequired: true`, which refuses such a line. A standalone `--help` answers for any command, so handle it first; then the most natural way to dispatch is `match`:

```php
if ($args['--help']) {
	(new HelpRenderer)->render($args->command);
	exit(0);
}

exit(match ($args->command) {
	$check => $app->check($args['paths'], $args['--generate-baseline']),
	$explain => $app->explain($args['rule']),
	$cli => $app->usage(),
});
```

Because there is no `default` arm, `match` throws `UnhandledMatchError` if you forget to handle a command.

A command accepts its own options plus those of all the commands above it. `--config` therefore works both in `dresscode -c x.neon check` and in `dresscode check -c x.neon`. An option belonging to a different command is refused with a message saying where it belongs: `Option --generate-baseline belongs to command 'check'.` If several commands define it, the message names them all. A hidden option is never revealed this way, nor offered in a “Did you mean” suggestion; the message just calls it unknown.

Commands can be nested as deep as you like, since `git remote add` is simply a command of a command. A command with subcommands can't take arguments, and commands side by side may use the same option names.

To test a single command, pass it to the parser together with the whole command line. The parser checks that the line really runs the command you gave it:

```php
$args = (new Parser)->parse($check, ['check', 'src', '--generate-baseline']);
```

## Printing Help

The help is generated from the definitions, so it always describes what the script really accepts. `HelpRenderer` draws it for the program or for any of its commands, with descriptions aligned and wrapped to the width of the terminal. `render()` writes it out, in color where the terminal supports it:

```php
use Nette\CommandLine\HelpRenderer;

$command = new Command('convert', 'Converts files between formats.');
$command->addFlag('--verbose', 'Enable verbose mode', alias: '-v');
$command->addOption('--output', 'Output file', alias: '-o', valueName: 'file');
$command->addArgument('input', 'Input file');

(new HelpRenderer)->render($command);
```

```
Usage: convert [options] <input>

Converts files between formats.

Options:
  -v, --verbose        Enable verbose mode
  -o, --output <file>  Output file

Arguments:
  <input>              Input file
```

The usage opens the help. It is generated unless you pass your own as `usage:` to `new Command()` or `addCommand()`, and the description of the command follows it. `addSection()` and `addText()` add a heading and a text in the order you call them; the text is printed exactly as you wrote it, neither wrapped nor colored. If you add no heading yourself, the help puts `Options:`, `Arguments:` and `Commands:` in front of every run of one kind. The help of a command lists its arguments, its own options, and under `Global options:` the ones it inherits.

A few settings shape the help only:

- `hidden: true` keeps a parameter out of the help, while it still works,
- `valueName: 'file'` names the value, as in `--output <file>`; for an option with an `enum` it takes the place of the list of allowed values, so a long list can go into the description instead,
- `defaultDescription: 'current directory'` describes the default value in words, and a blank string leaves it out, so a password or a token given as the default never shows up in the help.

Without `defaultDescription`, the help shows the default value after the description as `(default: json)`, as long as there is something to show: a boolean, an array or an empty string is left out.

`renderToString()` returns the help instead of writing it, as plain text when the renderer has no console, which suits a file or a test. A width of your own replaces the width of the terminal: `(new HelpRenderer(width: 100))->renderToString($command)`. To send the help to the error output, give the renderer a console over that stream, as the error handling below does.

### Handling –help and –version

When your script has required arguments, running `script.php --help` would fail with a missing argument. Mark such a flag as standalone, and it answers on its own:

```php
$command = new Command('convert');
$command->addFlag('--help', alias: '-h', standalone: true);
$command->addFlag('--version', standalone: true);
$command->addArgument('input');  // required

$args = (new Parser)->parse($command);

if ($args['--help']) {
	(new HelpRenderer)->render($args->command);
	exit;
}
```

When a standalone flag is used, the parser returns it as `true`, the other flags as they were given, and everything else with its default value, without validating, converting or requiring anything. You can therefore print the help before reading any configuration. Flags need no conversion, so with `--help --no-color` your script can print the help without colors, whereas an option with a value or an argument keeps its default. A setting that has to work together with `--help` should therefore be a flag. With commands, `dresscode check --help` selects `check` first, so `$args->command` is exactly the command the user wants help for. The line still has to be valid, though: an unknown option is refused as usual.

## Error Handling

For invalid user input, the parser throws [Nette\CommandLine\ParseException](https://api.nette.org/commandline/master/Nette/CommandLine/ParseException.html). Its `command` property holds the command in which the input went wrong, so you can show the right help:

```php
use Nette\CommandLine\ParseException;

try {
	$args = (new Parser)->parse($cli);
} catch (ParseException $e) {
	$stderr = new Console(STDERR);
	$stderr->writeLine("Error: {$e->getMessage()}", 'red');
	$stderr->writeLine();
	(new HelpRenderer($stderr))->render($e->command);
	exit(2);
}
```

The `reason` property says what was wrong, as a case of the `ParseFailure` enum, such as `ParseFailure::UnknownOption` or `ParseFailure::MissingArgument`. The `parameter` property is the parameter the error concerns, when there is one, and `token` is the piece of the command line it is about, exactly as the user wrote it: the name of an option without its value, a command, the first unexpected argument or a bad value, and `null` when something is missing. From these three your application can word the messages itself. The token comes straight from the user, so escape any control characters in it before printing it.

Common error messages:

|  |  |
| --- | --- |
| `Option --output requires a value.` | option used without its required value |
| `Option --verbose does not accept a value.` | a value given to a flag |
| `Unknown option --verbos. Did you mean --verbose?` | unknown option |
| `Missing required argument <file>.` | required argument not provided |
| `Unexpected arguments 'b', 'c'.` | extra positional arguments |
| `Option --format: expects json or xml, 'yaml' given.` | value not among the allowed ones |
| `Unknown command 'chekc'. Did you mean 'check'?` | unknown command |
| `Missing command.` | a command is required, but none was given |
| `Option --generate-baseline belongs to command 'check'.` | option of another command |

Mistakes in the definition itself, such as an option name without a dash, a name used twice or a required argument after an optional one, are programming errors and throw `InvalidArgumentException`.

## Colorful Output

The `Console` class writes to one stream and colors the text when that stream takes it:

```php
$console = new Console;
$console->writeLine('Error!', 'red');
$console->writeLine('White text on blue background', 'white/blue');
$console->writeLine($console->color('gray', 'Config  ') . $file);  // a line of several colors you compose yourself
```

The console writes to `STDOUT` unless you give it another stream. An application that writes to both wants one console for each, so that a redirected output doesn't decide the colors of the other:

```php
$out = new Console(STDOUT);
$err = new Console(STDERR);
```

The stream has to be open for writing, otherwise the constructor throws. When the reader of a pipe stops early, as `head` does in `php app.php | head -1`, the rest of the output is quietly dropped instead of raising a notice on every write.

The color is given as `'foreground'` or `'foreground/background'`. The names `black`, `gray`, `silver`, `white`, `navy`, `blue`, `green`, `lime`, `teal`, `aqua`, `maroon`, `red`, `purple`, `fuchsia`, `olive` and `yellow` are drawn by the terminal in the colors of its theme. A color that should look the same in every theme is written in hex, `'#87D7FF'` or the short `'#8DF'`, or as a number 0–255 of the extended palette, such as `'117'`. An unknown color throws, even when colors are off. A `null` color means no color at all, so `color($ok ? 'green' : null, $text)` needs no branch.

How many colors the stream takes is its [Nette\CommandLine\ColorDepth](https://api.nette.org/commandline/master/Nette/CommandLine/ColorDepth.html): `None`, `Ansi16` (the 16 colors of the terminal), `Ansi256` (the extended palette) or `TrueColor` (any color). A terminal gets `Ansi256`, or `TrueColor` when the `COLORTERM` environment variable says `truecolor` or `24bit`. [NO_COLOR](https://no-color.org) turns colors off, and [FORCE_COLOR](https://force-color.org) turns them on even when the output goes to a file or a pipe; its value `2` or `3` asks for at least `Ansi256` or `TrueColor`. A color is drawn as faithfully as the depth allows: `'#D7AF5F'` exactly with `TrueColor`, as the nearest color of the palette with `Ansi256`, and with `Ansi16` as the named color closest in hue. Your own `--no-color` option wins over all of that, whether you set it in the constructor or later:

```php
use Nette\CommandLine\ColorDepth;

$console = new Console(STDOUT, colorDepth: ColorDepth::None);  // or $console->setColorDepth(ColorDepth::None);
$console->hasColors();      // whether it colors
$console->getColorDepth();  // ColorDepth::None
$console->isTerminal();     // whether someone is watching: progress bars, prompts
$console->getWidth();       // width of the terminal in columns, 80 when it isn't one
```

`hasColors()` and `isTerminal()` answer two different questions. Gate colors on the first, and features that only make sense in front of a person, such as a progress bar or a prompt, on the second: a user may turn colors off and still sit at a real terminal.

`getWidth()` asks the terminal only once and remembers the answer, so after the window is resized, only a new console measures it again. The `COLUMNS` environment variable takes precedence when it is set, which comes in handy for trying out a narrow terminal.

A link is composed just like a color. `link()` makes the text clickable in the terminal, and in a file, a pipe or without colors it returns the text followed by the URL in parentheses, so a log keeps where the link led:

```php
$console->writeLine('See ' . $console->link('https://nette.org'));
$console->writeLine($console->link('https://nette.org', 'the manual'));  // 'the manual (https://nette.org)' outside a terminal
```

`link()` refuses a URL containing control characters, because they could end the link early and send the terminal commands of their own. Characters beyond ASCII are fine: inside the link they are percent-encoded, while the visible text keeps them readable.

With colors off, `color()` and `link()` add nothing, so text you compose comes out plain by itself. Everything else `write()` passes through untouched, so the content of a file, JSON or XML is never quietly rewritten. When you print a text from elsewhere that carries colors of its own, such as the output of a subprocess, drop them yourself with `Ansi::strip()`.

### Status Lines

`setStatus()` draws lines in place, a progress bar or a panel of what is running right now. The next output erases them, so you never clear anything by hand, and the next `setStatus()` draws them again below it:

```php
foreach ($files as $i => $file) {
	$console->setStatus(sprintf('%d/%d  %s', $i + 1, count($files), $file));
	$console->writeLine(check($file));  // erases the status and writes above it
}

$console->clearStatus();
```

A line that is too long is cut to the width of the terminal, because a wrapped one could not be redrawn. A newline inside an item starts a line of its own and tabs are expanded, so the status always knows how many lines it takes. Where the stream isn't a terminal, `setStatus()` does nothing at all, so a redirected output holds only the real lines.

The cursor stays hidden while the status is shown. The status lives as long as its console, so keep the console around for as long as the status should stay. The cursor comes back when the console is released and when the script ends, even after `exit()`, an uncaught exception or a fatal error. Only Ctrl+C ends PHP immediately, so your application has to handle that one itself.

A status is erased only by the console that drew it. So if you keep one console for the output and another for the errors, draw the status on the console that also carries the errors; otherwise a warning printed meanwhile lands on top of it.

### Measuring Text

Escape sequences take no space on the screen, and a Chinese character or an emoji takes two columns, so `strlen()` is the wrong ruler for anything that has to line up. [Nette\CommandLine\Ansi](https://api.nette.org/commandline/master/Nette/CommandLine/Ansi.html) measures and cuts text the way the terminal shows it:

```php
use Nette\CommandLine\Ansi;

Ansi::measure($console->color('red', 'error'));  // 5, the escape sequences don't count
Ansi::pad($console->color('red', 'error'), 10);  // padded to 10 columns, not to 10 bytes
Ansi::truncate($path, 30, keepEnd: true);        // cuts the front, so the file name stays
Ansi::strip($text);                              // the text without any escape sequence
```

Widths follow the Unicode tables, so emoji and flags are measured correctly as well. Control characters, a tab included, take no column, since `Ansi` measures printable text. And `strip()` removes only escape sequences, not other control characters, so it won't make untrusted text safe to print.

## Upgrading from 1.x

Version 2.0 is a new API with no compatibility layer: in 1.x a single `Parser` held the definition, parsed the line and printed the help text it had been given, while in 2.0 a `Command` is the definition, `Parser` reads a line against it and `HelpRenderer` draws the help from it.

The [Nette plugin for Claude Code and Codex](https://doc.nette.org/ai/command-line/agent-plugins) includes a guide by which your coding agent rewrites 1.x code to 2.0 faithfully and reports every place where 2.0 behaves differently.

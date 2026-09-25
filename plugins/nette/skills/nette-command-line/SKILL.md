---
name: nette-command-line
description: Invoke before writing or reviewing a PHP command-line script or tool that uses nette/command-line 2.0. Covers Command with addFlag(), addOption(), addArgument() and addCommand(), the Parser and its ParseResult, standalone --help and --version, negatable flags, optional values, defaults, enum, repeatable parameters, Normalizers, commands and subcommands dispatched with match, HelpRenderer, ParseException and ParseFailure, and the Console (colors and ColorDepth, NO_COLOR and FORCE_COLOR, links, setStatus() progress lines) with Ansi for measuring text. Also trigger for bin/ scripts, CLI argument parsing, "print the help", colored terminal output or progress bars in PHP. Also trigger when upgrading from 1.x, for code using new Parser($help), addFromHelp(), addSwitch(), parseOnly() or Parser::normalizeRealPath(), and for errors such as "Call to undefined method Parser::addFromHelp()" or "Too few arguments to function Parser::parse()".
---

## Nette Command-Line

```shell
composer require nette/command-line
```

Version 2.0, PHP 8.2 to 8.5, no dependencies. Four classes, each doing one thing: `Command` describes what the script accepts, `Parser` reads a command line against it, `HelpRenderer` draws the help from it, `Console` writes to the terminal.

**Code written for 1.x does not run on 2.0**, and some of it fails silently (`new Parser($help)` drops its arguments). To upgrade, follow [the upgrade guide](references/upgrading.md) step by step.

### Defining

```php
use Nette\CommandLine\Command;
use Nette\CommandLine\Console;
use Nette\CommandLine\HelpRenderer;
use Nette\CommandLine\Normalizers;
use Nette\CommandLine\ParseException;
use Nette\CommandLine\Parser;

$command = new Command('convert', 'Converts files between formats.');
$command->addFlag('--help', 'Print this help', alias: '-h', standalone: true);
$command->addFlag('--verbose', 'Verbose output', alias: '-v');
$command->addOption('--output', 'Output file', alias: '-o', valueName: 'file');
$command->addOption('--jobs', 'Parallel jobs', normalizer: Normalizers::int(min: 1), default: 4);
$command->addArgument('input', 'File to convert');

$args = (new Parser)->parse($command);   // $_SERVER['argv'], or pass the tokens without the script name
```

- The second positional argument of `add*()` is the **description**; everything else is named. `addOption('--output', '-o')` makes `-o` the description, not the alias.
- There are no setters: a parameter is checked when added and cannot be changed. Definition mistakes (a name without a dash, a duplicate, a required argument with a `default`, an argument after a repeatable one) throw `InvalidArgumentException` at once.
- `addFlag()` takes neither `enum` nor `normalizer`; those belong to options and arguments.

### Reading the ParseResult

`parse()` returns a read-only `ParseResult` read like an array, **keyed by the names as defined, dashes included**: `$args['--output']`, `$args['input']`. A name that was never defined throws `InvalidArgumentException`, so a typo does not pass as a missing value; `isset()` is `false` for both an unknown name and `null`. Anything needing a real array (`count()`, `array_*()`, `json_encode()`, a parameter typed `array`) needs `$args->toArray()`.

| Definition | Absent | Given bare | Given with a value |
|---|---|---|---|
| flag | `null` | `true` | refused |
| `negatable: true, default: true` | `true` | `true`, `--no-x` gives `false` | refused |
| option | `null` or `default` | throws, value required | the string |
| `valueOptional: true` | `null` or `default` | `true` | only as `--x=value` |
| argument | throws unless `optional: true` | – | the string |

- An optional value is attached with `=`. `--format xml` is the option without a value followed by the argument `xml` – or, in a program with commands, `Unknown command 'xml'`. With `enum: [...]` the next token is taken when it is one of the allowed values.
- `default` is what the parameter is worth when **absent** and is returned as given, never normalized nor checked against `enum`.
- `enum: Format::class` (a backed enum) parses straight into the case.
- `repeatable: true` collects an array, `[]` when absent. Without it the last value wins.
- `wasProvided('--x')` tells a value equal to the default from the default itself; `isEmpty()` means nothing followed the script name.
- A normalizer (`Normalizers::int()`, `float()`, `realPath()`, or any closure) runs only on values from the line. Its exception reaches you as a `ParseException` naming the parameter.

### Standalone Flags and Commands

A `standalone: true` flag answers on its own: the parser returns it as `true`, the other flags as given and every other parameter at its default, checking or converting nothing and without requiring a missing argument or command. An unknown option is still refused.

```php
$cli = new Command('tool');
$cli->addFlag('--help', alias: '-h', standalone: true);
$check = $cli->addCommand('check', 'Report violations');
$check->addArgument('paths', optional: true, repeatable: true);

$result = (new Parser)->parse($cli);
if ($result['--help']) {
	(new HelpRenderer)->render($result->command);   // the help of the command the user asked about
	exit(0);
}

exit(match ($result->command) {
	$check => $app->check($result['paths']),
	$cli => $app->usage(),
});
```

`$result->command` is the `Command` object selected by the first positional word, the program itself when there is none (`new Command(..., commandRequired: true)` refuses that). A command accepts its own options and those of the commands above it. `match` without a `default` arm throws when a command is left unhandled.

### Errors and Help

```php
try {
	$result = (new Parser)->parse($cli);
} catch (ParseException $e) {
	$stderr = new Console(STDERR);
	$stderr->writeLine("Error: {$e->getMessage()}", 'red');
	(new HelpRenderer($stderr))->render($e->command);
	exit(2);
}
```

`$e->reason` is a `ParseFailure` case (`UnknownOption`, `MissingArgument`, ...), `$e->parameter` the parameter concerned and `$e->token` the part of the line as given. The help is generated from the definition; `addSection()` and `addText()` add headings and paragraphs, `hidden: true`, `valueName:` and `defaultDescription:` shape it. `renderToString()` returns it, `new HelpRenderer(width: 100)` fixes the width.

### Console

- `$console->color('red', $text)`: the **color comes first**. An unknown color name throws even with colors off; `null` means no color. Colors are `'fg'` or `'fg/bg'` from `black`, `gray`, `silver`, `white`, `navy`, `blue`, `green`, `lime`, `teal`, `aqua`, `maroon`, `red`, `purple`, `fuchsia`, `olive`, `yellow`, drawn in the colors of the terminal theme; a color independent of the theme is `'#87D7FF'`, `'#8DF'` or a palette number such as `'117'`.
- One console per stream: `new Console(STDOUT)` and `new Console(STDERR)` decide colors each for itself. How many is a `ColorDepth` (`None`, `Ansi16`, `Ansi256`, `TrueColor`), detected from the stream, `NO_COLOR`, `FORCE_COLOR` and `COLORTERM`; `new Console(STDOUT, colorDepth: ColorDepth::None)` or `setColorDepth(ColorDepth::None)` (a `--no-color` option) wins over all of them. There is no `useColors()`.
- Gate colors on `hasColors()`, and progress bars or prompts on `isTerminal()` – colors may be off at a real terminal.
- `$console->link($url, $text)` makes a clickable link; in a pipe or without colors the URL follows the text in parentheses.
- `setStatus($lines)` draws one line or a list of them in place; the next output erases them and the next `setStatus()` redraws them below; `clearStatus()` at the end. It does nothing when the stream is not a terminal.
- Measure with `Ansi::measure()`, `Ansi::pad()`, `Ansi::truncate($s, $width, keepEnd: true)`, never `strlen()`; `Ansi::strip()` removes foreign escape sequences.

### Documentation

The full Nette documentation for this topic is bundled with the plugin. Read a page when you need details beyond this skill:

- [Command-Line](../../docs/command-line/index.md) – parameters, commands, help, errors, the console

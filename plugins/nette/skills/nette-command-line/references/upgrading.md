# Upgrading nette/command-line from 1.x to 2.0

2.0 has no compatibility layer. The 1.x `Parser` held the definition, parsed the line and
printed the help text it was given; in 2.0 a `Command` is the definition, `Parser` reads a
line against it and `HelpRenderer` draws the help from it.

The goal is a **faithful rewrite**: the same result keys and the same values for every command
line the old code accepted. Where 2.0 cannot behave the same, do not improvise; rewrite as
close as possible and put the place in the report at the end. Do not add 2.0 features the old
code did not need (such as commands), except where a rule below says so.


## Workflow

1. Find every use: `Nette\CommandLine\`, `addFromHelp`, `parseOnly`, `normalizeRealPath`,
   `Parser::`, `->help()`, `Console`. Search, do not wait for errors: `new Parser($help)` still
   runs on 2.0 and silently drops its arguments.
2. **Record the 1.x behavior before touching the dependency.** Collect command lines from the
   tests, the README, shell scripts and CI configuration that call the tool, and add every
   option absent, bare and with a value. Run them through the old parser and save the outcome:

   ```php
   $record = [];
   foreach ($lines as $argv) {
   	try {
   		$outcome = $parser->parse($argv);
   	} catch (\Throwable $e) {
   		$outcome = $e->getMessage();
   	}
   	$record[] = [$argv, $outcome];
   }
   file_put_contents('cli-1x.json', json_encode($record, JSON_PRETTY_PRINT));
   ```

3. For each parser, build the complete 1.x definition, as a table of parameters with name,
   alias, kind, value (none, required, optional), default, repeatable, enum, normalizer,
   realpath and description. Collect it from the help text, the attribute array and the fluent
   calls, in the order they ran, by the rules below.
4. Write the 2.0 definition from the table, then the parsing, the help and the error handling.
5. Check every read of the result (see "The result").
6. Set `"nette/command-line": "^2.0"` in `composer.json`.
7. **Replay the recorded lines** through the new definition, with
   `(new Parser)->parse($command, $argv)->toArray()` in place of the old call, and compare.
   Every difference must be one of "What 2.0 does differently", a dead fallback or an argument
   order 2.0 refuses. Otherwise fix the rewrite, or stop and report the difference when it
   cannot be matched. Delete the record afterwards.
8. Run the project's tests and static analysis. Change the expectation of a test only for a
   difference already explained in step 7, and name it in the report.
9. Write the report.


## Reading a 1.x help text

`new Parser($help, $array)` and `addFromHelp($help, $array)` read the text like this:

- **Only a line indented by spaces or tabs and starting with a dash defines something.** Every
  other line is prose that only `help()` printed.
- The definition ends at the first **two spaces**; the rest of the line is the description.
  A `(default: x)` clause in the description gives the default, **always the string** `x`
  (`(default: 8)` is `'8'`).
- The definition is one or two flags, such as `-o, --output`. **The name is the last flag, the
  alias the first.** The name is the key in the result, so `--param, -p` has the key `-p`.
- After a flag, separated by a space or `=`, may come a value: `<x>` is required, `[x]` is
  optional, a bare word `x` is required. `...` right after it makes it repeatable, and `a|b|c`
  inside the brackets is an enum.
- **A `(default: x)` clause makes the value optional**, even with `<x>`.
- The text never defines positional arguments.


## The attribute array

The second argument is keyed by name. **A key given in the array wins over the text**, even
when its value is `null`. A name only in the array is defined too, and a name without a dash
is a positional argument. **Positional arguments are read in the order they were defined**,
across the arrays and the fluent `addArgument()` calls; within one array it is the order of
its keys.

| Key | Old constant | Meaning |
|---|---|---|
| `argument` | `Parser::Argument`, `ARGUMENT` | `false` makes a switch, even over `<x>` in the text |
| `optional` | `Parser::Optional`, `OPTIONAL` | optional value, or optional argument |
| `repeatable` | `Parser::Repeatable`, `REPEATABLE` | values collected into a list |
| `enum` | `Parser::Enum`, `ENUM` | allowed values |
| `realpath` | `Parser::RealPath`, `REALPATH` | resolved path, applied **after** the normalizer |
| `normalizer` | `Parser::Normalizer` | closure converting the value |
| `default` | `Parser::Default`, `VALUE` | default; makes the value or the argument optional |

For an option, the value is none when `argument` is `false`, optional when `optional` is true
or a default is set, required otherwise. An argument is optional when `optional` is true or a
default is set.


## Fluent 1.x calls

- `addSwitch($name, $alias, repeatable:)`
- `addOption($name, $alias, optionalValue:, fallback:, enum:, repeatable:, normalizer:)`
- `addArgument($name, optional:, fallback:, enum:, repeatable:, normalizer:)`

**The second positional argument changed its meaning.** In 1.x it was the alias, in 2.0 it is
the description, so `addOption('--output', '-o')` would silently show `-o` as the description.
Always pass the alias as `alias:`. The named arguments `enum:`, `repeatable:`, `normalizer:`
and `optional:` keep their names, `optionalValue:` becomes `valueOptional:` and `fallback:`
becomes `default:`.

**Defining a name again replaced the earlier definition.** 2.0 throws, so keep only the last one.

**A fallback was dead in two cases**, and 2.0 would bring it to life:
- on an option whose value is required, absent gave `null`, never the fallback;
- on an argument that is not optional, absent threw "Missing required argument".

Leave such a fallback out and report it.


## From the table to 2.0

```php
use Nette\CommandLine\Command;
use Nette\CommandLine\Normalizers;

$command = new Command('convert'); // the name shows in the generated usage
$command->addFlag('--verbose', 'Show detailed output', alias: '-v');
$command->addOption(
	'--format',
	'Output format',
	alias: '-f',
	valueOptional: true,
	enum: ['json', 'xml'],
	default: 'json',
);
$command->addArgument('input', optional: true, repeatable: true);
```

Everything after the description is a named argument of `add*()`; there are no setters.

| 1.x | 2.0 |
|---|---|
| switch | `addFlag($name, $description)` |
| option with a value | `addOption($name, $description)` |
| positional argument | `addArgument($name, $description)` |
| alias | `alias: $alias`, on flags and options only |
| optional value | `valueOptional: true` |
| optional argument | `optional: true` |
| default, fallback | `default: $value`, only where 1.x really used it |
| repeatable | `repeatable: true` |
| enum | `enum: [...]` |
| normalizer | `normalizer: $closure` |
| realpath, `Parser::normalizeRealPath(...)` | `normalizer: Normalizers::realPath()` |
| realpath together with a normalizer | `$realPath = Normalizers::realPath();` then `normalizer: fn($v) => $realPath($normalizer($v))` |

In 2.0 the default value and the optional value are independent, and an optional value is taken
attached with `=`, or as the next token when it is one of the `enum` values, while 1.x took any
next token that did not start with a dash. Carry `valueOptional: true` over
where the old value was written `[x]`, and report that `--x value` has to become `--x=value`.
Where 1.x made the value optional only through `<x> (default: x)`, keep the value required
and set the default: `--jobs 8` keeps working, and only a bare `--jobs`, which was `true`,
now throws; report it.

Add the parameters in the order they appear in the old help text, because 2.0 draws the help
in the order of the calls. Positional arguments keep their 1.x order.

**2.0 refuses two orders of arguments that 1.x accepted**, with an `InvalidArgumentException`
from `addArgument()`. Rewrite them and report both:
- An optional argument before a required one. 1.x filled the arguments in order, so the
  optional one was in effect required. Define it as required; only the message of a missing
  argument changes.
- An argument after a repeatable one. It never got a value, because the repeatable argument
  took all the rest. Leave it out: if it was optional, replace its reads with its default; if
  it was required, 1.x always threw, so tell the author the code never worked.


## Parsing, help and errors

| 1.x | 2.0 |
|---|---|
| `$args = $parser->parse()` | `$args = (new Parser)->parse($command)` |
| `$parser->parse($argv)` | `(new Parser)->parse($command, $argv)` |
| `$parser->parseOnly(['--help', '--version'])` | `standalone: true` on those flags, then one `parse()` |
| `$parser->help()` | `(new HelpRenderer)->render($command)` |
| `$parser->isEmpty()` | `$args->isEmpty()`, available only after `parse()` |
| `catch (\Exception $e)` | `catch (ParseException $e)` when the `try` holds only the parsing, otherwise keep `\Exception`; `$e->command` is the command whose help fits |

A standalone flag answers without checking or converting the rest: `--help` is `true`, the other
flags are as given and every other parameter is its default, even when a required argument is
missing.

**`parseOnly()` of an option with a value has no counterpart.** It returned the raw value
without validation. Restructure the code so the value is read from the full `parse()`, and
report it.

**`isEmpty()` before parsing** must move. When the definition has a required argument, `parse()`
of an empty line throws, so check `count($_SERVER['argv'] ?? []) < 2` before parsing instead.

`HelpRenderer` and `Console` are in `Nette\CommandLine`, and so are `ParseException` and
`Normalizers`. `render()` writes the help to the console of the renderer, or to a new one on
STDOUT; `renderToString()` returns it, plain when the renderer has no console. The parameter classes
`Flag`, `Option` and `Argument` are in `Nette\CommandLine\Parameters`, which matters for type
hints and for `$command->getParameter('--config', Option::class)`.


## The help text

2.0 never prints the old text; it generates the help from the definition. Carry the text over:

- The description of each definition line becomes the second argument of `add*()`. Join lines
  that visibly continue it. **Drop the `(default: x)` clause**, since the help adds it by
  itself for a scalar default.
- When the clause was prose rather than a value, such as `(default: current directory)`, 1.x
  still returned that string. Keep `default: 'current directory'` and report it.
- A `Usage:` block becomes `new Command($name, usage: $lines)`. Drop it when the generated usage says the
  same. The usage always opens the help, so a banner the old text had above it is printed by
  the code before the help, or follows as `addText()`.
- A heading line such as `Options:` becomes `->addSection('Options')`. Once there is one
  section, the automatic headings are off, so carry over all the headings.
- Other prose becomes `->addText($text)` at its place, printed as it is. A usage line the code
  echoed before `help()` belongs to the definition too.
- A parameter the old text did not list gets no description. Use `hidden: true` only when
  the old code clearly kept it out of the help on purpose.

The new help is not byte for byte the old text.


## The result

`parse()` returns a `ParseResult`, not an array. `$args['--x']`, `isset()`, `??`, `empty()` and
`foreach` work as before. **Reading a name that is not defined throws**, where 1.x gave a
warning and `null`.

Everything that needs a real array needs `$args->toArray()`: `count()`, `array_*()` functions,
`is_array()`, `json_encode()`, `extract()`, `+`, and passing it to a parameter typed `array`.


## What 2.0 does differently

Put each of these in the report where it applies. Rewrite nothing to emulate them unless the
code visibly depends on the old behavior.

- **An optional value is taken attached with `=`, or as the next token when the option has an
  `enum` and the token is one of its values.** 1.x took any next token that did not start with a
  dash, so `-f file.txt` no longer swallows the file, and `-f xml` is the option without its value
  followed by the argument `xml` unless `xml` is in the enum. Give such an option its `enum` and
  both spellings keep working; an enum value may not be the name of a command below the option,
  which the definition refuses.
- **A normalizer is not called for an option used without its optional value.** The value is
  `true`. 1.x called the normalizer with `true`, and a realpath normalizer crashed on it. When
  a normalizer handled `true`, move that logic after `parse()`.
- **An exception thrown by a normalizer arrives wrapped** in a `ParseException` whose message
  names the parameter, with the original as `getPrevious()`. A `catch` of the normalizer's own
  exception class around the parsing no longer catches it; catch `ParseException` and inspect
  `getPrevious()` instead.
- **A normalizer returning `null` keeps `null`.** 1.x replaced it with the fallback, or threw
  "Missing required argument" for a required argument.
- **Only the last value of a parameter that is not repeatable is checked and converted.** 1.x
  checked every occurrence, so `--m=bad --m=ok` threw.
- **Messages changed.** "Option --x has not argument." is "Option --x does not accept a value.";
  "Option --x requires argument." is "Option --x requires a value."; "Unexpected parameter a."
  is "Unexpected argument 'a'."; control characters from the line are escaped;
  an exception from a normalizer is prefixed with the parameter, as in "Option --x: message";
  a missing path is "Option --x: file path 'y' not found."; a value outside the enum is
  "Option --x: expects a or b, 'c' given." instead of "Value of option --x must be a, or b.",
  and for an argument "Argument <input>: expects a, 'c' given." instead of "Value of option
  input must be a."; an unknown option may add "Did you mean ...?".
- **Input 1.x refused is accepted:** `--` ends the options, a lone `-` and a negative number
  such as `-1` are values, `-abc`
  means `-a -b -c`, and an empty string is a value.
- **A standalone flag still requires a valid line.** `parseOnly()` ignored everything else,
  while `--help --nonsense` now throws "Unknown option --nonsense.".
- **Names are checked.** An option name is one or two dashes and a letter, digit or underscore,
  with no `=`, whitespace or control character; an argument name starts with a letter, digit or
  underscore and holds no whitespace or control character. A name or an alias used twice throws.
- **`Console::color()` takes the text as its second argument and throws on an unknown color
  name**, where 1.x emitted a warning when colors were on; `color($color)` without a text is
  gone. Bright colors are the bright codes 90 to 97 instead of bold, so they may look slightly
  different. `NO_COLOR` set to an empty string no longer turns colors off, and `FORCE_COLOR=0`
  turns them off even on a terminal. **The static `detectColors()` and `detectTerminal()` became
  `hasColors()` and `isTerminal()` of a console over one stream**. **Whether a console colors is a
  `ColorDepth`, not a bool**: `useColors()` and `useColors(true)` become
  `setColorDepth(ColorDepth::Ansi256)` (or the depth the application wants), `useColors(false)`
  becomes `setColorDepth(ColorDepth::None)`, or `new Console(colorDepth: ColorDepth::None)` from
  the start.
- **The help looks different**: generated from the definition, aligned, wrapped and colored
  through a `Console`.

## Example

Before:

```php
$parser = new Parser;
$parser
	->addFromHelp('
		-h, --help           Show this help
		-f, --format [type]  Output format (default: json)
		-o, --output <file>  Output file
	', [
		'--format' => [Parser::Enum => ['json', 'xml', 'csv']],
	])
	->addArgument('input', normalizer: Parser::normalizeRealPath(...));

if ($parser->isEmpty() || $parser->parseOnly(['--help'])['--help']) {
	echo "Usage: convert [options] <input>\n\n";
	$parser->help();
	exit;
}

try {
	$args = $parser->parse();
} catch (\Exception $e) {
	fwrite(STDERR, "Error: {$e->getMessage()}\n");
	exit(1);
}
```

After:

```php
use Nette\CommandLine\Command;
use Nette\CommandLine\HelpRenderer;
use Nette\CommandLine\Normalizers;
use Nette\CommandLine\ParseException;
use Nette\CommandLine\Parser;

$command = new Command('convert');
$command->addFlag('--help', 'Show this help', alias: '-h', standalone: true);
$command->addOption(
	'--format',
	'Output format',
	alias: '-f',
	valueOptional: true,
	enum: ['json', 'xml', 'csv'],
	default: 'json',
);
$command->addOption('--output', 'Output file', alias: '-o');
$command->addArgument('input', normalizer: Normalizers::realPath());

if (count($_SERVER['argv'] ?? []) < 2) {
	(new HelpRenderer)->render($command);
	exit;
}

try {
	$args = (new Parser)->parse($command);
} catch (ParseException $e) {
	fwrite(STDERR, "Error: {$e->getMessage()}\n");
	exit(1);
}

if ($args['--help']) {
	(new HelpRenderer)->render($command);
	exit;
}
```

The echoed usage line is gone, because the generated usage is the same
`convert [options] <input>`. Report for this file: `--help` together with an unknown option now
throws instead of printing the help; the error messages of a missing input path changed.


## The report

End with a list per rewritten file: where the parser was, and for every item from "What 2.0
does differently" or from a dead fallback, what changed and what the author should decide.

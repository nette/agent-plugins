# Running Tests

Contents:

- [Command-Line Options](#command-line-options)
  - [`-p <path>`](#-p-path)
  - [`-c <path>`](#-c-path)
  - [`-C`](#-c)
  - [`-d <key=value>`](#-d-keyvalue)
  - [-s](#-s)
  - [`--stop-on-fail`](#--stop-on-fail)
  - [`-j <num>`](#-j-num)
  - [`-o <console|console-lines|tap|junit|log|none>`](#-o-consoleconsole-linestapjunitlognone)
  - [`-w | --watch <path>`](#-w----watch-path)
  - [`-i | --info`](#-i----info)
  - [`--setup <path>`](#--setup-path)
  - [`--temp <path>`](#--temp-path)
  - [`--colors 1|0`](#--colors-10)
  - [`--coverage <path>`](#--coverage-path)
  - [`--coverage-src <path>`](#--coverage-src-path)
- [Own php.ini](#own-phpini)


The most visible part of Nette Tester is the command-line test runner. It is extremely fast and robust because it automatically runs all tests as separate processes in parallel using multiple threads. It can also run itself in watch mode.

The test runner is invoked from the command line. Pass the directory containing the tests as a parameter. For the current directory, simply enter a dot:

```
vendor/bin/tester .
```

The test runner scans the specified directory and all its subdirectories, looking for tests, which are files ending in `*.phpt` or `*Test.php`. It also reads and evaluates their [annotations](test-annotations.md) to determine which tests to run and how.

Each test file runs in its own isolated PHP process, and the runner executes several of them at once – so the unit of parallelism is the file. Code within a single file therefore runs sequentially. A [TestCase](testcase.md) lives in one file, so by default its methods run one after another in a single process; mark it with the [@testCase](test-annotations.md#testcase) annotation to have the runner run each test method as a separate parallel process instead.

It then executes the tests. During execution, it prints characters to the terminal to indicate the progress:

- <code style="color: #CCC; background-color: #000">.</code> – test passed
- <code style="color: #CCC; background-color: #000">s</code> – test has been skipped
- <code style="color: #FFF; background-color: #900">F</code> – test failed

The output may look like this:

```
 _____ ___  ___ _____ ___  ___
|_   _/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v2.6.1

PHP 8.5.2 (cli) | php | 8 threads

........s..........................

OK (35 tests, 1 skipped, 1.7 seconds)
```

When run again, it first executes the tests that failed in the previous run, so you immediately know if you have fixed the error.

The Tester's exit code is zero if no test fails. Otherwise, it is non-zero.

## Command-Line Options

You can get an overview of command-line options by running Tester without parameters or with the `-h` option:

```
 _____ ___  ___ _____ ___  ___
|_   _/ __)( __/_   _/ __)| _ )
  |_| \___ /___) |_| \___ |_|_\  v2.6.1

Usage:
    tester [options] [ | ]...

Options:
    -p                     Specify PHP interpreter to run (default: php).
    -c                     Use custom php.ini, ignore system configuration.
    -C                           With -c, include system configuration as well.
    -d ...            Define INI entry 'key' with value 'value'.
    -s                           Show information about skipped tests.
    --stop-on-fail               Stop execution upon the first failure.
    -j                      Run  jobs in parallel (default: 8).
    -o   (e.g. -o junit:output.xml)
                                 Specify one or more output formats with optional file name.
    -w | --watch           Watch directory.
    -i | --info                  Show tests environment info and exit.
    --setup                Script for runner setup.
    --temp                 Path to temporary directory. Default by sys_get_temp_dir().
    --colors [1|0]               Enable or disable colors.
    --coverage             Generate code coverage report to file.
    --coverage-src         Path to source code.
    -h | --help                  This help.
```

### `-p <path>`

Specifies the PHP binary that will be used to run tests. By default, it is `php`.

```
tester -p /home/user/php-7.2.0-beta/php-cgi tests
```

### `-c <path>`

Uses a custom `php.ini` file and ignores the system configuration. This is useful for running tests with specific settings. See [Own php.ini](#own-phpini) for more information.

### `-C`

When used together with `-c`, includes the system configuration as well (does not ignore it). See [Own php.ini](#own-phpini) section.

### `-d <key=value>`

Sets the value of a PHP configuration directive for the tests. This parameter can be used multiple times.

```
tester -d max_execution_time=20
```

### -s

Displays information about skipped tests.

### `--stop-on-fail`

Tester stops testing upon the first failing test.

### `-j <num>`

Specifies the number of parallel processes to run tests in. The default value is 8. To run tests sequentially, use the value 1.

### `-o <console|console-lines|tap|junit|log|none>`

Sets the output format. The default is the console format. You can specify the name of the file into which the output will be written (e.g., `-o junit:output.xml`). The `-o` option can be repeated multiple times to generate multiple formats at once.

- `console`: same as the default format, but the ASCII logo is not printed in this case
- `console-lines`: similar to console, but the result of each test is listed on a separate line with additional information
- `tap`: [TAP format](https://en.wikipedia.org/wiki/Test_Anything_Protocol) suitable for machine processing
- `junit`: JUnit XML format, also suitable for machine processing
- `log`: Outputs the testing progress. Includes all failed, skipped, and also successful tests
- `none`: nothing is printed

### `-w | --watch <path>`

After completing the tests, Tester does not exit but continues to run and watch PHP files in the specified directory. When a file changes, it runs the tests again. This parameter can be used multiple times if you want to watch multiple directories.

Useful when refactoring a library or debugging tests.

```
tester --watch src tests
```

### `-i | --info`

Shows information about the runtime environment for tests. For example:

```
tester -p /usr/bin/php7.1 -c tests/php.ini --info

PHP binary:
/usr/bin/php7.1

PHP version:
7.1.7-1+0~20170711133844.5+jessie~1.gbp5284f4 (cli)

Code coverage engines:
(not available)

Loaded php.ini files:
/var/www/dev/demo/tests/php.ini

PHP temporary directory:
/tmp

Loaded extensions:
Core, ctype, date, dom, ereg, fileinfo, filter, hash, ...
```

### `--setup <path>`

Tester loads the specified PHP script at startup. The variable `Tester\Runner\Runner $runner` is available within this script. Assume a file `tests/runner-setup.php` with the following content:

```php
$runner->outputHandlers[] = new MyOutputHandler;
```

We run Tester with:

```
tester --setup tests/runner-setup.php tests
```

### `--temp <path>`

Sets the path to the directory for Tester's temporary files. The default value is returned by `sys_get_temp_dir()`. You will be notified if the default value is not valid.

If you are unsure which directory is being used, run Tester with the `--info` parameter.

### `--colors 1|0`

By default, Tester detects if the terminal supports colors and colorizes its output accordingly. This option overrides the auto-detection. You can set coloring globally using the `NETTE_TESTER_COLORS` environment variable.

### `--coverage <path>`

Tester generates a report showing how much of the source code is covered by tests. This option requires the [Xdebug](https://xdebug.org/) or [PCOV](https://github.com/krakjoe/pcov) PHP extension to be installed, or the PHPDBG SAPI, which is faster. The extension of the target file determines its format: HTML or Clover XML.

```
tester tests --coverage coverage.html  # HTML report
tester tests --coverage coverage.xml   # Clover XML report
```

The priority for selecting the coverage engine is as follows:

1. PCOV
2. PHPDBG
3. Xdebug

When using PHPDBG, extensive tests might fail due to memory exhaustion. Collecting code coverage information is memory-intensive. In this case, calling `Tester\CodeCoverage\Collector::flush()` within your test can help. It writes the collected data to disk and frees up memory. The call only has an effect with the PHPDBG engine; with PCOV, Xdebug, or when data collection is not running, it does nothing.

See an [example HTML report](https://files.nette.org/assets/tester/coverage.html) with code coverage.

### `--coverage-src <path>`

Used in conjunction with the `--coverage` option. `<path>` is the path to the source code for which the report is generated. Can be used multiple times.

## Own php.ini

You can use a custom `php.ini` file for your tests. If you need specific extensions or special INI settings, we recommend creating your own `php.ini` file and distributing it with your tests. Then, run Tester with the `-c` option, for example, `tester -c tests/php.ini tests`. The INI file might look like this:

```ini
[PHP]

extension=php_pdo_mysql.dll
extension=php_pdo_pgsql.dll

memory_limit=512M
```

When using `-c`, Tester **ignores the system configuration** (runs PHP with the `-n` flag). If you want to include system configuration as well, add the `-C` option: `tester -c tests/php.ini -C tests`. Even when combining `-c` and `-C`, other INI files from `/etc/php/conf.d/*.ini` are not loaded on UNIX. This is a PHP behavior, not specific to Tester.

Prior to version 2.6, without `-c`, Tester ran PHP with the `-n` flag, i.e., without php.ini; the `-C` option suppressed this. From version 2.6, the system php.ini is loaded by default. The behavior when using `-c` remains unchanged.

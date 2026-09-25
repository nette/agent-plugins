---
name: nette-php-generator
description: Invoke before writing or reviewing code that generates PHP code with nette/php-generator. Covers ClassType, InterfaceType, TraitType, EnumType, GlobalFunction, Closure, Method, Parameter, Property with hooks and asymmetric visibility, PhpNamespace and its automatic class name resolution, PhpFile, Printer vs PsrPrinter, Literal, Type constants, attributes, the placeholders of setBody()/addBody(), loading existing code with from(), fromCode() and Factory, ClassManipulator and Dumper. Also trigger for code generators, scaffolding commands, DI container or proxy generation, and "generate a PHP class/file". Not for Latte templates that output PHP, nor for nikic/php-parser AST work.
---

## Nette PhpGenerator

```shell
composer require nette/php-generator
```

Version 4.2 supports PHP 8.1 to 8.5 and generates the latest syntax (enums, readonly, property hooks, asymmetric visibility).

### Print the Namespace or the File, Not the Class

Code is modeled as objects and turned into a string by a printer: `echo $object` uses `Printer` (Nette style: tabs, brace of a method on its own line), `new PsrPrinter` produces PSR-12 / PER output. Pick the one the target project uses.

```php
$file = new Nette\PhpGenerator\PhpFile;
$file->setStrictTypes();                   // declare(strict_types=1)
$namespace = $file->addNamespace('App\Model');
$namespace->addUse(App\Entity\Product::class);
$class = $namespace->addClass('ProductRepository');

file_put_contents($path, (new Nette\PhpGenerator\PsrPrinter)->printFile($file));
```

A class that belongs to a namespace, printed on its own, has its names already shortened by the `use` clauses but carries neither the `namespace` line nor the `use` lines – the result does not compile. Print the `PhpNamespace` or the `PhpFile`. A `PhpFile` holds only namespaces, classes and functions; there is no API for other global code.

### Class Names Inside a Namespace Are Fully Qualified

A class added to a namespace has all its types **resolved**: parent, interfaces, traits, attributes, parameter, property and return types. Write every name fully qualified **without the leading backslash**; the printer shortens it by the `use` clauses and the current namespace, or prints `\Foo\Bar` when nothing applies.

```php
$method->addParameter('product')->setType('App\Entity\Product'); // printed as Product thanks to addUse()
$method->addComment('@return ' . $namespace->simplifyType('App\Entity\Product[]'));
```

Comments and bodies are **not** resolved – they are copied as written, so simplify names in them by hand (`simplifyName()`, `simplifyType()`). A class created by `new ClassType` outside any namespace gets no resolution; names are printed as written. `Printer::setTypeResolving(false)` turns resolution off.

### Bodies and Placeholders

`setBody($code)` and `addBody($line)` (one line per call) take PHP code as a string. With a second argument, an array, they fill placeholders by `Dumper`, which exports any value as valid PHP:

| Placeholder | Result |
|---|---|
| `?` | the value exported as PHP |
| `...?` | array items as arguments, `...?:` as named arguments |
| `$?`, `->?`, `::?` | a variable, property or static member of that name |
| `\?` | a literal `?` |

With arguments, **every** `?` not followed by a letter is a placeholder, including those in `??`, `?->` and `? :`. Write them as `\?\?`, `\?->` and `\? :`, or build that part of the code without arguments. Without arguments nothing is replaced and `\?` stays in the output literally.

```php
$method->setBody('return $this->db->table(?)->where(?, ?);', [$table, $column, $value]);
$method->addBody('return $x \?\? ?;', [$default]);
```

### Members

- `addProperty()`, `addMethod()`, `addConstant()` and `addTrait()` **throw** `InvalidStateException` when the member exists; the first three take `overwrite: true` to replace it. `addParameter()` overwrites silently.
- A default value is exported as data: `addProperty('mode', 'fast')` prints `'fast'`. For code (a constant, an expression, `new Foo`) pass `new Literal('self::Fast')` or `Literal::new(Foo::class, [$arg])`.
- `addProperty('list')->setType('?array')` has no default; `->setInitialized()` adds `= null`.
- Variadic is a flag of the **function**, not the parameter: `$method->setVariadic()` makes the last parameter variadic.
- Types are strings (`'?array'`, `'int|string'`) or `Type` constants and helpers: `Type::Array`, `Type::nullable(Type::String)`, `Type::union(...)`.
- Constructor promotion: `addMethod('__construct')->addPromotedParameter('name')->setType('string')->setPrivate()->setReadOnly()`. A readonly property or promoted parameter must have a type, otherwise printing throws.

### Reading Existing Code

```php
$class = ClassType::from(Foo::class);                    // by reflection, bodies are empty
$class = ClassType::from(Foo::class, withBodies: true);  // bodies too, needs nikic/php-parser
$file = PhpFile::fromCode(file_get_contents($path));     // needs nikic/php-parser
```

`fromCode()` keeps the file comment and `strict_types` but drops other global code and single-line comments outside bodies. To edit statements inside bodies, use nikic/php-parser directly. `ClassManipulator` adds `inheritMethod()`, `inheritProperty()` and `implement()` (stubs for every abstract member of an interface or abstract class).

`(new Dumper)->dump($value)` is a readable replacement for `var_export()` when generating configuration or cache files.

### Documentation

The full Nette documentation for this topic is bundled with the plugin. Read a page when you need details beyond this skill:

- [PhpGenerator](../../docs/php-generator/index.md) – every element, the printers, namespaces, files, loading existing code
- [PhpGenerator Upgrading](../../docs/php-generator/upgrading.md) – changes between major versions

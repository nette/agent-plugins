# PHP Reflection

Contents:

- [`areCommentsAvailable(): bool`](#arecommentsavailable-bool)
- [`expandClassName(string $name, ReflectionClass $context): string`](#expandclassnamestring-name-reflectionclass-context-string)
- [`getMethodDeclaringMethod(ReflectionMethod $method): ReflectionMethod`](#getmethoddeclaringmethodreflectionmethod-method-reflectionmethod)
- [`getParameterDefaultValue(\ReflectionParameter $param): mixed`](#getparameterdefaultvaluereflectionparameter-param-mixed)
- [`getPropertyDeclaringClass(ReflectionProperty $prop): ReflectionClass`](#getpropertydeclaringclassreflectionproperty-prop-reflectionclass)
- [`getUseStatements(ReflectionClass $class): array`](#getusestatementsreflectionclass-class-array)
- [`isBuiltinType(string $type): bool` (deprecated)](#isbuiltintypestring-type-bool-deprecated)
- [`toString($reflection): string`](#tostringreflection-string)


[Nette\Utils\Reflection](https://api.nette.org/utils/master/Nette/Utils/Reflection.html) is a static class providing useful functions for PHP reflection. Its purpose is to address shortcomings in native reflection classes and unify behavior across different PHP versions.

Installation:

```shell
composer require nette/utils
```

All examples assume the following class alias is defined:

```php
use Nette\Utils\Reflection;
```

## `areCommentsAvailable(): bool`

Checks if reflection has access to PHPdoc comments. Comments might be unavailable due to opcode caching, see for example the [opcache.save-comments](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.save-comments) directive.

## `expandClassName(string $name, ReflectionClass $context): string`

Expands the class name `$name` to its fully qualified name within the context of the class `$context`, considering its namespace and defined aliases. Essentially, it determines how the PHP parser would interpret `$name` if it were written inside the body of the `$context` class.

```php
namespace Foo;
use Bar;

class DemoClass
{
	// new Bar, new Baz
}

$context = new ReflectionClass(Foo\DemoClass::class);
Reflection::expandClassName('Bar', $context); // 'Bar'
Reflection::expandClassName('Baz', $context); // 'Foo\Baz'
```

## `getMethodDeclaringMethod(ReflectionMethod $method): ReflectionMethod`

Returns the reflection of the method that contains the actual declaration of `$method`. Usually, a method is its own declaration, but the method body might originate from a trait and potentially under a different name.

Since PHP doesn't provide sufficient information to determine the true declaration reliably, Nette uses its own heuristics, which **should be** reliable.

```php
trait DemoTrait
{
	function foo()
	{
	}
}


class DemoClass
{
	use DemoTrait {
		DemoTrait::foo as foo2;
	}
}


$method = new ReflectionMethod('DemoClass::foo2');
Reflection::getMethodDeclaringMethod($method); // ReflectionMethod('DemoTrait::foo')
```

## `getParameterDefaultValue(\ReflectionParameter $param): mixed`

Returns the default value of a function or method parameter. Unlike the native `ReflectionParameter::getDefaultValue()`, it also correctly resolves constants and class constants used as the default value. If the constant cannot be resolved, it throws a `\ReflectionException`.

```php
class DemoClass
{
	const DefaultValue = 123;
}

function demo($param = DemoClass::DefaultValue)
{
}

$param = new ReflectionParameter('demo', 'param');
echo Reflection::getParameterDefaultValue($param); // 123
```

## `getPropertyDeclaringClass(ReflectionProperty $prop): ReflectionClass`

Returns the reflection of the class or trait that contains the declaration of the property `$prop`. The property might be declared within a trait.

Since PHP doesn't provide sufficient information to determine the true declaration reliably, Nette uses its own heuristics, which **are not** fully reliable.

```php
trait DemoTrait
{
	public $foo;
}


class DemoClass
{
	use DemoTrait;
}

$prop = new ReflectionProperty(DemoClass::class, 'foo');
Reflection::getPropertyDeclaringClass($prop); // ReflectionClass('DemoTrait')
```

## `getUseStatements(ReflectionClass $class): array`

Returns an array of use statements (aliases) from the file in which the class `$class` is defined. The keys are the aliases, the values are the fully qualified class names. Requires the `tokenizer` PHP extension.

```php
namespace Foo;

use Nette\Utils\Strings;
use Nette\Utils\Arrays as Arr;

class DemoClass
{
}

$class = new ReflectionClass(Foo\DemoClass::class);
Reflection::getUseStatements($class);
// ['Strings' => 'Nette\Utils\Strings', 'Arr' => 'Nette\Utils\Arrays']
```

## `isBuiltinType(string $type): bool` (deprecated)

Checks if `$type` is a PHP built-in type. Otherwise, it's considered a class name.

```php
Reflection::isBuiltinType('string'); // true
Reflection::isBuiltinType('Foo');    // false
```

Use [Nette\Utils\Validators::isBuiltinType()](validators.md#isbuiltintypestring-type-bool).

## `toString($reflection): string`

Converts a reflection object to a human-readable string.

```php
$func = new ReflectionFunction('func');
echo Reflection::toString($func); // 'func()'

$class = new ReflectionClass('DemoClass');
echo Reflection::toString($class); // 'DemoClass'

$method = new ReflectionMethod('DemoClass', 'foo');
echo Reflection::toString($method); // 'DemoClass::foo()'

$param = new ReflectionParameter(['DemoClass', 'foo'], 'param');
echo Reflection::toString($param); // '$param in DemoClass::foo()'

$prop = new ReflectionProperty('DemoClass', 'foo');
echo Reflection::toString($prop); // 'DemoClass::$foo'
```

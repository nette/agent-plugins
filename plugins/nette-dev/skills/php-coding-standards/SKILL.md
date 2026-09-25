---
name: php-coding-standards
description: 'Invoke BEFORE writing or modifying any PHP code. Provides coding standards and naming conventions for Nette repos: TABs, single quotes, strict_types, PSR-12 modifications, use statement ordering. Use this whenever creating, modifying, or refactoring any PHP code - even small bug fixes or one-line changes.'
---

## PHP Coding Standards

### Using nette/coding-standard

Install globally using `/php-fixer:install-php-fixer`. After installation, PHP files are automatically fixed when edited - see the `php-auto-fixer` skill for editing workflow rules (especially `use` statement ordering).

### General Rules
- Every PHP file must include `declare(strict_types=1)`
- Use two empty lines between methods - Nette convention for visual separation in longer classes
- Document shut-up operator use: `@mkdir($dir); // @ - directory may already exist`
- Document weak comparison operators: `// == accept null`
- Multiple exceptions can be written in a single `exceptions.php` file, and multiple enums into `enums.php`
- Interface methods don't need visibility as they're always public
- All properties, return values, and parameters must have types
- Final constants don't need types as they're self-evident
- Write all code, comments, and variables in English only (even if communicating with the user in Czech)

### Strings
- Use single quotes - they signal "no interpolation here," making code easier to scan
- Use double quotes only when the string contains apostrophes or interpolation is needed
- In HTML attributes, double quotes are standard

### Naming Conventions
- Avoid abbreviations unless the full name is too long
- Use UPPERCASE for two-letter abbreviations (`IO`, `DB`), PascalCase/camelCase for longer ones (`Http`, `Xml`)
- Use nouns or noun phrases for class names
- Class names should include both specificity and generality (e.g., `ArrayIterator`)
- Never let a method name be a bare noun - a method is an action: `getProvider()`, not `provider()`. A verb is the usual form; the exceptions are settled conventions, namely static factories (`fromFile()`), conversions (`toArray()`), interface methods (`jsonSerialize()`) and fluent interfaces whose names echo the domain rather than an action, as a query builder echoes SQL clauses
- PascalCase for classes and class constants/enums
- camelCase for methods and properties
- Never use prefixes/suffixes like `Abstract`, `Interface`, or `I` - the type system already distinguishes them

### Method Order
- Place a new method deliberately - the order of methods is the outline a reader gets, saying which parts are primary and which are derived. The three rules below are in order of precedence: when they disagree, the earlier one wins
- Keep a method with those covering the same subject, and never break up a group that belongs together, not even with a method on that same subject - it goes before the whole group or after it
- Put the general before the special: a variant of an existing method goes right after it, never before, so the reader meets the plain form first and the special case second. A method serving one narrow use case follows the general ones rather than sitting among them
- A helper goes after the method that needs it, after all of them when it serves several. Where exactly is settled by the two rules above and by what surrounds it: it may follow immediately, it may sit after the whole group, it may close the class. Visibility does not decide the order - a private helper is not moved to the end of the class for being private
- Leave existing methods where they are - reordering them buries the real change in the diff

### Formatting
- Use TABs for indentation everywhere (PHP, JS, HTML, CSS/SCSS, NEON, Latte, ...)
- PHP follows Nette Coding Standard (based on PSR-12) with these modifications:
  - No space before parentheses in arrow functions: `fn($a) => $b`
  - No blank lines required between different `use` import types
  - When parameters span multiple lines, return type and opening brace go on separate lines:

```php
// Short params - standard single-line
public function getItems(string $type): array
{
	// method body
}

// Multi-line params - return type and brace on separate lines
public function example(
	string $param,
	array $options,
): string
{
	// method body
}
```

### Global Functions and Constants
- Write global functions/constants without leading backslash: `count($arr)` not `\count($arr)`

### Code Style Preferences
- Uses DOM API with HTML5 parser Lexbor for HTML processing
- Prefer modern PHP syntax and concise expressions:
  - Example: `if (is_array($response['data'] ?? null))` instead of `if (isset($response['data']) && is_array($response['data']))`
- Use named arguments for boolean parameters whose meaning isn't obvious from context (e.g., `is_a($obj, $class, allow_string: true)`), but not when the method name makes it clear (e.g., `setReadonly(true)`)
- Place interface/base class outside the namespace containing its implementations (e.g., `Foo\Network` next to `Foo\Networks\*`, not inside it) - this keeps the interface discoverable at the package level
- For structured data, prefer typed classes (DTOs) with public typed properties and default values over plain `array`. Arrays lose type information; typed objects let PHPStan catch errors at analysis time and give full IDE support.
- Don't add comments referencing specific bug fixes, issues, or tickets ("fixes #123", "workaround for bug X", "added in PR #456"). Only write comments with general validity that explain non-obvious logic any reader would benefit from. Git history provides the fix-specific context and doesn't rot over time.

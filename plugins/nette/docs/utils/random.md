# Generating Random Strings

[Nette\Utils\Random](https://api.nette.org/utils/master/Nette/Utils/Random.html) is a static class for generating cryptographically secure pseudo-random strings.

Installation:

```shell
composer require nette/utils
```

## `generate(int $length=10, string $charlist='0-9a-z'): string`

Generates a random string of a specified length using characters from the `$charlist`. Character ranges like `0-9` can also be used.

```php
use Nette\Utils\Random;

Random::generate(10);       // '6zq3a1nl8n'
Random::generate(5, 'A-Z'); // 'HLKUR'
```

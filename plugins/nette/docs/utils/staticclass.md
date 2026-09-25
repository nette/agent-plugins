# Static Classes

StaticClass is used to denote static classes.

Installation:

```shell
composer require nette/utils
```

Static classes, i.e., classes that are not intended to be instantiated, can be marked with the trait [Nette\StaticClass](https://api.nette.org/utils/master/Nette/StaticClass.html):

```php
class Strings
{
	use Nette\StaticClass;
}
```

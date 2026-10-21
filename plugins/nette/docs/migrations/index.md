# Upgrade Guide

Nette is split into independent packages, each with its own version history. When upgrading, follow the upgrade notes for the individual packages you use.

Always upgrade gradually, one version at a time: not from 3.0 straight to 3.2, but first to 3.1 and then to 3.2.

> [!TIP]
> When you upgrade from Nette 3.0 on, a large part of the work can be done for you by [DressCode](https://doc.nette.org/en/tools/coding-standard#toc-newer-php-and-nette). With the `dresscode/upgrade-nette` package, it rewrites your code to the new API of the Nette libraries, from renamed classes and constants to changed method calls, and what it cannot rewrite, it reports together with what to write instead. See [Upgrading Nette](https://dresscode.run/en/upgrading-nette).

Before testing, we recommend disabling `E_USER_DEPRECATED` reporting and re-enabling it once everything works:

```php
$configurator->enableTracy();
error_reporting(~E_USER_DEPRECATED); // note the ~ before E_USER_DEPRECATED
```

## Package Upgrade Guides

- [Application](../application/upgrading.md)
- [Bootstrap](../bootstrap/upgrading.md)
- [Caching](../caching/upgrading.md)
- [Component Model](../component-model/upgrading.md)
- [Database](../database/upgrading.md)
- [Dependency Injection](../dependency-injection/upgrading.md)
- [Forms](../forms/upgrading.md)
- [HTTP](../http/upgrading.md)
- [Mail](../mail/upgrading.md)
- [NEON](../neon/upgrading.md)
- [PHP Generator](../php-generator/upgrading.md)
- [RobotLoader](../robot-loader/upgrading.md)
- [SafeStream](../safe-stream/upgrading.md)
- [Security](../security/upgrading.md)
- [Tracy](../tracy/upgrading.md)
- [Utils](../utils/upgrading.md)

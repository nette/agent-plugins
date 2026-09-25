# Upgrade Guide

Nette is split into independent packages, each with its own version history. When upgrading, follow the upgrade notes for the individual packages you use.

Always upgrade gradually, one version at a time: not from 3.0 straight to 3.2, but first to 3.1 and then to 3.2.

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

# Configuring Nette

An overview of all configuration options in the Nette Framework.

Nette components are configured using configuration files, which are usually written in the [NEON format](../neon/format.md). They are best edited in [editors that support it](https://doc.nette.org/en/tools/ide). If you are using the full framework, the configuration will be [loaded during application boot](../application/bootstrapping.md#di-container-configuration); if not, read [how to load the configuration](../bootstrap/index.md).

<pre>
[application](../application/configuration.md#application): Application<br>
[assets](../assets/configuration.md): Assets<br>
[constants](../application/configuration.md#constants): Definition of PHP constants<br>
[database](../database/configuration.md): Database<br>
[decorator](../dependency-injection/configuration.md#decorator): Decorator<br>
[di](../dependency-injection/configuration.md#di): DI Container<br>
[extensions](../dependency-injection/configuration.md#extensions): Installation of additional DI extensions<br>
[forms](../forms/configuration.md): Forms<br>
[http](../http/configuration.md#http-headers): HTTP Headers<br>
[includes](../dependency-injection/configuration.md#including-files): Including files<br>
[latte](../application/configuration.md#latte-templates): Latte Templates<br>
[mail](../mail/index.md#configuration): Mailing<br>
[parameters](../dependency-injection/configuration.md#parameters): Parameters<br>
[php](../application/configuration.md#php): PHP configuration options<br>
[routing](../application/configuration.md#routing): Routing<br>
[search](../dependency-injection/configuration.md#search): Automatic service registration<br>
[security](../security/configuration.md): Access Control<br>
[services](../dependency-injection/services.md): Services<br>
[session](../http/configuration.md#session): Session<br>
[tracy](../tracy/configuring.md#nette-framework): Tracy Debugger
</pre>

To write a string containing the character `%`, you must escape it by doubling it to `%%`.

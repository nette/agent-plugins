# Directory Structure of the Application

Contents:

- [Basic Project Structure](#basic-project-structure)
- [Code Organization Principles](#code-organization-principles)
  - [Namespaces](#namespaces)
  - [Singular vs Plural in Names](#singular-vs-plural-in-names)
- [Public Directory `www/`](#public-directory-www)
- [Application Directory `app/`](#application-directory-app)
- [Presenters and Templates](#presenters-and-templates)
  - [Flexible Structure Development](#flexible-structure-development)
  - [Template Location](#template-location)
  - [Helper Classes and Components](#helper-classes-and-components)
- [Model – Heart of the Application](#model-heart-of-the-application)
- [Infrastructure Code](#infrastructure-code)
- [Command Scripts](#command-scripts)
- [Other Possible Directories](#other-possible-directories)
- [Presenter Mapping](#presenter-mapping)


How to design a clear and scalable directory structure for projects in Nette Framework? We will show you proven practices that will help you organize your code. You will learn:

- how to **logically structure** the application into directories
- how to design the structure so that it **scales well** as the project grows
- what are the **possible alternatives** and their advantages or disadvantages

It is important to mention that Nette Framework itself does not enforce any specific structure. It is designed to be easily adaptable to any needs and preferences.

## Basic Project Structure

Although Nette Framework does not dictate any fixed directory structure, there is a proven default arrangement in the form of the [Web Project](https://github.com/nette/web-project):

```
web-project/
├── app/              ← application directory
├── assets/           ← SCSS, JS files, images..., alternatively resources/
├── bin/              ← scripts for command line
├── config/           ← configuration
├── log/              ← logged errors
├── temp/             ← temporary files, cache
├── tests/            ← tests
├── vendor/           ← libraries installed by Composer
└── www/              ← public directory (document-root)
```

You can modify this structure freely according to your needs – rename or move folders. Then you just need to adjust the relative paths to directories in `Bootstrap.php` and possibly `composer.json`. Nothing more is needed, no complex reconfiguration, no changes to constants. Nette has smart autodetection and automatically recognizes the application's location, including its base URL.

## Code Organization Principles

When you first explore a new project, you should be able to quickly orient yourself. Imagine clicking on the `app/Model/` directory and seeing this structure:

```
app/Model/
├── Services/
├── Repositories/
└── Entities/
```

From this, you only learn that the project uses some services, repositories, and entities. You learn nothing about the actual purpose of the application.

Let's look at a different approach – **organization by domains**:

```
app/Model/
├── Cart/
├── Payment/
├── Order/
└── Product/
```

Here it's different – at first glance, it's clear that this is an e-shop. The directory names themselves reveal what the application can do – it works with payments, orders, and products.

The first approach (organization by class type) brings several problems in practice: code that is logically related is fragmented across different folders, and you have to jump between them. Therefore, we will organize by domains.

### Namespaces

It is customary for the directory structure to correspond to the namespaces in the application. This means that the physical location of files matches their namespace. For example, a class located in `app/Model/Product/ProductRepository.php` should have the namespace `App\Model\Product`. This principle helps in navigating the code and simplifies autoloading.

### Singular vs Plural in Names

Notice that we use singular for the main application directories: `app`, `config`, `log`, `temp`, `www`. The same applies inside the application: `Model`, `Core`, `Presentation`. This is because each represents a single cohesive concept.

Similarly, `app/Model/Product` represents everything related to products. We don't call it `Products` because it's not a folder full of products (that would contain files like `nokia.php`, `samsung.php`). It's a namespace containing classes for working with products – `ProductRepository.php`, `ProductService.php`.

The folder `app/Tasks` is plural because it contains a set of separate executable scripts – `CleanupTask.php`, `ImportTask.php`. Each of them is an independent unit.

For consistency, we recommend using:

- Singular for namespaces representing a functional unit (even if working with multiple entities)
- Plural for collections of independent units
- In case of uncertainty, or if you don't want to think about it, choose singular

## Public Directory `www/`

This directory is the only one accessible from the web (the document-root). You might often encounter the name `public/` instead of `www/` – it's just a matter of convention and does not affect the application's functionality. The directory contains:

- Application [entry point](bootstrapping.md#indexphp) `index.php`
- `.htaccess` file with rules for mod_rewrite (for Apache)
- Static files (CSS, JavaScript, images)
- Uploaded files

For proper application security, it is crucial to have the [document-root configured correctly](../nette/troubleshooting.md#how-to-change-or-remove-www-directory-from-url).

Never place the `node_modules/` folder in this directory – it contains thousands of files that might be executable and should not be publicly accessible.

## Application Directory `app/`

This is the main directory containing the application code. Basic structure:

```
app/
├── Core/               ← infrastructure concerns
├── Model/              ← business logic
├── Presentation/       ← presenters and templates
├── Tasks/              ← command scripts
└── Bootstrap.php       ← application bootstrap class
```

`Bootstrap.php` is the [application startup class](bootstrapping.md) that initializes the environment, loads configuration, and creates the DI container.

Let's now look at the individual subdirectories in more detail.

## Presenters and Templates

The presentation part of the application is located in the `app/Presentation` directory. An alternative is the shorter `app/UI`. This is the place for all presenters, their templates, and any associated helper classes.

We organize this layer according to domains. In a complex project combining an e-shop, blog, and API, the structure would look like this:

```
app/Presentation/
├── Shop/              ← e-shop frontend
│   ├── Product/
│   ├── Cart/
│   └── Order/
├── Blog/              ← blog
│   ├── Home/
│   └── Post/
├── Admin/             ← administration
│   ├── Dashboard/
│   └── Products/
└── Api/               ← API endpoints
	└── V1/
```

Conversely, for a simple blog, we would use the following structure:

```
app/Presentation/
├── Front/             ← website frontend
│   ├── Home/
│   └── Post/
├── Admin/             ← administration
│   ├── Dashboard/
│   └── Posts/
├── Error/
└── Export/            ← RSS, sitemaps, etc.
```

Folders like `Home/` or `Dashboard/` contain presenters and templates. Folders like `Front/`, `Admin/`, or `Api/` are called **modules**. Technically, these are regular directories used for the logical partitioning of the application.

Each folder containing a presenter includes the presenter file itself and its templates. For example, the `Dashboard/` folder contains:

```
Dashboard/
├── DashboardPresenter.php     ← presenter
└── default.latte              ← template
```

This directory structure is reflected in the class namespaces. For example, `DashboardPresenter` is located in the `App\Presentation\Admin\Dashboard` namespace (see [Presenter Mapping](#presenter-mapping)):

```php
namespace App\Presentation\Admin\Dashboard;

class DashboardPresenter extends Nette\Application\UI\Presenter
{
	// ...
}
```

We refer to the `Dashboard` presenter within the `Admin` module in the application using colon notation as `Admin:Dashboard`. Its `default` action is then referred to as `Admin:Dashboard:default`. For nested modules, we use multiple colons, for example, `Shop:Order:Detail:default`.

### Flexible Structure Development

One of the great advantages of this structure is how elegantly it adapts to the growing needs of the project. As an example, let's take the part generating XML feeds. Initially, we have a simple form:

```
Export/
├── ExportPresenter.php   ← one presenter for all exports
├── sitemap.latte         ← template for sitemap
└── feed.latte            ← template for RSS feed
```

Over time, more feed types are added, and we need more logic for them… No problem! The `Export/` folder simply becomes a module:

```
Export/
├── Sitemap/
│   ├── SitemapPresenter.php
│   └── sitemap.latte
└── Feed/
	├── FeedPresenter.php
	├── amazon.latte         ← feed for Amazon
	└── ebay.latte           ← feed for eBay
```

This transformation is completely smooth – just create new subfolders, divide the code into them and update links (e.g. from `Export:feed` to `Export:Feed:amazon`). Thanks to this, we can gradually expand the structure as needed; the nesting level is not limited in any way.

For example, if in the administration you have many presenters related to order management, such as `OrderDetail`, `OrderEdit`, `OrderDispatch`, etc., you can create a module (folder) named `Order` for better organization, which will contain (folders for) presenters `Detail`, `Edit`, `Dispatch`, and others.

### Template Location

In the previous examples, we saw that templates are located directly in the folder with the presenter:

```
Dashboard/
├── DashboardPresenter.php     ← presenter
├── DashboardTemplate.php      ← optional template class
└── default.latte              ← template
```

This location proves to be the most convenient in practice – you have all related files readily available.

Alternatively, you can place templates in a `templates/` subfolder. Nette supports both variants. You can even place templates completely outside the `Presentation/` folder. Everything about template location options can be found in the chapter [Finding Templates](templates.md#template-lookup).

### Helper Classes and Components

Presenters and templates often come with other helper files. We place them logically according to their scope:

1. **Directly with the presenter** in the case of specific components for that presenter:

```
Product/
├── ProductPresenter.php
├── ProductGrid.php        ← component for product listing
└── FilterForm.php         ← form for filtering
```

2. **For the module** – we recommend using the `Accessory` folder, which is placed conveniently at the beginning alphabetically:

```
Front/
├── Accessory/
│   ├── NavbarControl.php    ← components for frontend
│   └── TemplateFilters.php
├── Product/
└── Cart/
```

3. **For the entire application** – in `Presentation/Accessory/`:

```
app/Presentation/
├── Accessory/
│   ├── LatteExtension.php
│   └── TemplateFilters.php
├── Front/
└── Admin/
```

Alternatively, you can place helper classes like `LatteExtension.php` or `TemplateFilters.php` in the infrastructure folder `app/Core/Latte/`. And components in `app/Components`. The choice depends on team conventions.

## Model – Heart of the Application

The model contains all the business logic of the application. The rule for its organization is again – structure by domains:

```
app/Model/
├── Payment/                   ← everything about payments
│   ├── PaymentFacade.php      ← main entry point
│   ├── PaymentRepository.php
│   ├── Payment.php            ← entity
├── Order/                     ← everything about orders
│   ├── OrderFacade.php
│   ├── OrderRepository.php
│   ├── Order.php
└── Shipping/                  ← everything about shipping
```

In the model, you typically encounter these types of classes:

**Facades**: represent the main entry point into a specific domain within the application. They act as an orchestrator, coordinating cooperation between various services to implement complete use-cases (like “create order” or “process payment”). Beneath its orchestration layer, the facade hides implementation details from the rest of the application, thereby providing a clean interface for working with the given domain.

```php
class OrderFacade
{
	public function createOrder(Cart $cart): Order
	{
		// validation
		// order creation
		// email sending
		// writing to statistics
	}
}
```

**Services**: focus on specific business operations within a domain. Unlike facades, which orchestrate entire use-cases, a service implements specific business logic (like price calculations or payment processing). Services are typically stateless and can be used either by facades as building blocks for more complex operations or directly by other parts of the application for simpler tasks.

```php
class PricingService
{
	public function calculateTotal(Order $order): Money
	{
		// price calculation
	}
}
```

**Repositories**: handle all communication with the data storage, typically a database. Their task is to load and save entities and implement methods for searching them. A repository shields the rest of the application from the implementation details of the database and provides an object-oriented interface for working with data.

```php
class OrderRepository
{
	public function find(int $id): ?Order
	{
	}

	public function findByCustomer(int $customerId): array
	{
	}
}
```

**Entities**: objects representing the main business concepts in the application, which have their own identity and change over time. Typically, these are classes mapped to database tables using an ORM (like Nette Database Explorer or Doctrine). Entities can contain business rules related to their data and validation logic.

```php
// Entity mapped to the 'orders' database table
class Order extends Nette\Database\Table\ActiveRow
{
	public function addItem(Product $product, int $quantity): void
	{
		$this->related('order_items')->insert([
			'product_id' => $product->id,
			'quantity' => $quantity,
			'unit_price' => $product->price,
		]);
	}
}
```

**Value Objects**: immutable objects representing values without their own identity – for example, a monetary amount or an email address. Two instances of a value object with the same values are considered identical.

## Infrastructure Code

The `Core/` folder (or alternatively `Infrastructure/`) is home to the technical foundation of the application. Infrastructure code typically includes:

```
app/Core/
├── Router/               ← routing and URL management
│   └── RouterFactory.php
├── Security/             ← authentication and authorization
│   ├── Authenticator.php
│   └── Authorizator.php
├── Logging/              ← logging and monitoring
│   ├── SentryLogger.php
│   └── FileLogger.php
├── Cache/                ← caching layer
│   └── FullPageCache.php
└── Integration/          ← integration with external services
	├── Slack/
	└── Stripe/
```

For smaller projects, a flat structure is naturally sufficient:

```
Core/
├── RouterFactory.php
├── Authenticator.php
└── QueueMailer.php
```

This is code that:

- Handles technical infrastructure (routing, logging, caching)
- Integrates external services (Sentry, Elasticsearch, Redis)
- Provides basic services for the entire application (mail, database)
- Is mostly independent of a specific domain – cache or logger works the same for an e-shop or a blog.

Are you wondering whether a certain class belongs here or in the model? The key difference is that code in `Core/`:

- Knows nothing about the domain (products, orders, articles)
- Can usually be transferred to another project
- Solves “how it works” (how to send an email), not “what it does” (which email to send)

An example for better understanding:

- `App\Core\MailerFactory` – creates instances of the class for sending emails, handles SMTP settings
- `App\Model\OrderMailer` – uses `MailerFactory` to send emails about orders, knows their templates and when they should be sent

## Command Scripts

Applications often need to perform activities outside of regular HTTP requests – whether it's background data processing, maintenance, or periodic tasks. Simple scripts in the `bin/` directory are used for execution, while the actual implementation logic is placed in `app/Tasks/` (or `app/Commands/`).

Example:

```
app/Tasks/
├── Maintenance/               ← maintenance scripts
│   ├── CleanupCommand.php     ← deleting old data
│   └── DbOptimizeCommand.php  ← database optimization
├── Integration/               ← integration with external systems
│   ├── ImportProducts.php     ← import from supplier system
│   └── SyncOrders.php         ← order synchronization
└── Scheduled/                 ← regular tasks
	├── NewsletterCommand.php  ← sending newsletters
	└── ReminderCommand.php    ← customer notifications
```

What belongs in the model and what in command scripts? For example, the logic for sending a single email is part of the model, while the bulk sending of thousands of emails belongs in `Tasks/`.

Tasks are usually run from the command line or via cron: the script in `bin/` creates the DI container using the [bootConsoleApplication()](bootstrapping.md#different-environments) method and pulls the required service from it. They can also be run via an HTTP request, but security must be considered. The presenter that runs the task needs to be secured, for example, only for logged-in users or with a strong token and access from allowed IP addresses. For long-running tasks, it is necessary to increase the script time limit and use `session_write_close()` to avoid locking the session.

## Other Possible Directories

In addition to the mentioned basic directories, you can add other specialized folders according to project needs. Let's look at the most common ones and their use:

```
app/
├── Api/              ← API logic independent of the presentation layer
├── Database/         ← migration scripts and seeders for test data
├── Components/       ← shared visual components across the entire application
├── Event/            ← useful if using an event-driven architecture
├── Mail/             ← email templates and related logic
└── Utils/            ← helper classes
```

For shared visual components used in presenters across the application, you can use the `app/Components` or `app/Controls` folder:

```
app/Components/
├── Form/                 ← shared form components
│   ├── SignInForm.php
│   └── UserForm.php
├── Grid/                 ← components for data listings
│   └── DataGrid.php
└── Navigation/           ← navigation elements
	├── Breadcrumbs.php
	└── Menu.php
```

This is where components with more complex logic belong. If you want to share components between multiple projects, it is advisable to extract them into a separate Composer package.

In the `app/Mail` directory, you can place email communication management:

```
app/Mail/
├── templates/            ← email templates
│   ├── order-confirmation.latte
│   └── welcome.latte
└── OrderMailer.php
```

## Presenter Mapping

Mapping defines the rules for deriving the class name from the presenter name. We specify them in the [configuration](configuration.md) under the key `application › mapping`.

On this page, we have shown that we place presenters in the `app/Presentation` folder (or `app/UI`). Since Nette Application 3.3, this is the default convention that does not need to be configured. If you use a different structure or want to specify the mapping explicitly, the default setting corresponds to this line:

```neon
application:
	mapping: App\Presentation\*\**Presenter
```

How does mapping work? For a better understanding, let's first imagine an application without modules. We want the presenter classes to fall under the `App\Presentation` namespace, so that the `Home` presenter maps to the `App\Presentation\HomePresenter` class. This is achieved with this configuration:

```neon
application:
	mapping: App\Presentation\*Presenter
```

Mapping works by replacing the asterisk in the mask `App\Presentation\*Presenter` with the presenter name `Home`, resulting in the final class name `App\Presentation\HomePresenter`. Simple!

However, as you see in the examples in this and other chapters, we place presenter classes in eponymous subdirectories, for example, the `Home` presenter maps to the class `App\Presentation\Home\HomePresenter`. We achieve this by using double asterisks `**` (requires Nette Application 3.2.3):

```neon
application:
	mapping: App\Presentation\**Presenter
```

Now we proceed to mapping presenters into modules. We can define specific mapping for each module:

```neon
application:
	mapping:
		Front: App\Presentation\Front\**Presenter
		Admin: App\Presentation\Admin\**Presenter
		Api: App\Api\*Presenter
```

According to this configuration, the presenter `Front:Home` maps to the class `App\Presentation\Front\Home\HomePresenter`, while the presenter `Api:OAuth` maps to the class `App\Api\OAuthPresenter`.

Since the `Front` and `Admin` modules have a similar mapping pattern, and there will likely be more such modules, it is possible to create a general rule that replaces them. A new asterisk for the module is added to the class mask:

```neon
application:
	mapping:
		*: App\Presentation\*\**Presenter
		Api: App\Api\*Presenter
```

It also works for deeper nested directory structures, such as the presenter `Admin:User:Edit`, where the segment with the asterisk repeats for each module level, resulting in the class `App\Presentation\Admin\User\Edit\EditPresenter`.

An alternative notation is to use an array consisting of three segments instead of a string. For the examples shown above, this notation is equivalent to the previous one:

```neon
application:
	mapping:
		*: [App\Presentation, *, **Presenter]
		Api: [App\Api, '', *Presenter]
```

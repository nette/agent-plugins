---
name: nette-architecture
description: Invoke before designing presenters, modules, or application structure in web application. Use when asking about directory structure (app/ folder organization), presenter organization (modules, Admin/Front/Api, BasePresenter), domain-driven placement (Core/ vs Model/), component and factory placement, presenter lifecycle (action/render/template), CLI tasks, Accessory placement, project skeleton, or refactoring architecture. Also trigger when starting a new Nette project.
---

For new projects, see [the project skeleton reference](references/skeleton.md).
For the `#[Requires]` attribute (HTTP method/AJAX restrictions on actions), see [the reference](references/requires.md).

## Application Architecture

### Presenter Lifecycle

Understanding the request flow is essential for placing logic correctly:

1. **`checkRequirements()`** – evaluates the `#[Requires]` attribute
2. **`startup()`** – use for access checks and early redirects. An override **must call
   `parent::startup()`**, otherwise Nette throws `InvalidStateException`
3. **`action<Name>()`** – processes the request (data writes, redirects)
4. **canonicalization** – the redirect to the canonical URL happens here, i.e. *after* the action
5. **`handle<Signal>()`** – signals run **after** the action phase and after canonicalization,
   not inside the action phase
6. **`beforeRender()`** – runs before every render, use for shared template variables
7. **`render<Name>()`** – prepares data for the template (read-only, no redirects)
8. **`afterRender()`** – after the render methods, before the template is sent
9. **template is sent**, then **`shutdown()`**

Each phase also has an event: `$onStartup`, `$onRender`, `$onShutdown`.

The key insight: actions and signals *do things* (write, redirect), renders *prepare views* (read). Mixing these responsibilities leads to redirect-after-render bugs and untestable presenters.

### Directory Structure

The application follows domain-driven organization. The reason: when code is grouped by domain (products, orders, customers), related files are close together and changes to one feature don't scatter across multiple directories.

- App: The main application namespace (`App\`)
  - Bootstrap: Application initialization and configuration
  - Core: Infrastructure concerns (routing, integrations)
  - Entity: Database entities (see nette-database skill for entity design)
  - Model: Business logic services organized by domain
  - Presentation: UI layer organized by modules
  - Tasks: Command-line executable tasks

### Evolution Strategy

**Start minimal** -> **Grow organically** -> **Refactor when painful**

Start with flat structure – create subdirectories only when you have 5+ related files or clear implementation variants. The threshold exists because below 5 files, subdirectories add navigation overhead without improving discoverability.

Don't architect for theoretical future complexity. Address actual complexity when it emerges with clear user needs driving structural decisions.

**When refactoring to deeper structure:** Move files one domain at a time. Create the new subdirectory, move related presenters/services into it, update namespaces, and verify. Don't reorganize everything at once.

### Configuration

- config/common.neon: Main application configuration
- config/services.neon: Service definitions and auto-wiring configuration

For DI configuration details (service registration, autowiring, parameters), see the nette-configuration skill.

### Core vs Model Decision Matrix

The distinction matters because Core/ code is reusable across projects while Model/ code is specific to your business. This affects testability, replaceability, and team ownership.

**Use Core/ for:**
- Technology-agnostic infrastructure (MyExplorer, RouterFactory, QueueMailer)
- External service integrations (SentryLogger, AI/, GoogleSearch/)
- Framework extensions and utilities
- Code that could be moved to another project unchanged

**Use Model/ for:**
- Business domain logic (CatalogService, CustomerService, OrderService)
- Domain-specific operations and rules
- Entity-specific processing logic
- Code that knows about your business concepts (products, orders, customers)

### Model Layer Principles

```
app/Model/
├── CatalogService.php       ← Main domain services at root
├── CustomerService.php
├── OrderService.php
├── mails/                   ← Email templates (specialized assets)
├── Payment/                 ← Implementation variants
│   ├── CardOnlinePayment.php
│   ├── BankTransferPayment.php
│   └── CashPayment.php
└── exceptions.php           ← Domain exceptions
```

Naming convention: `mails/` is lowercase because it contains non-PHP assets (email templates). `Payment/` is uppercase because it contains PHP classes following PSR-4.

**Service placement rules:**
1. Main domain coordinator services directly in Model/
2. Implementation variants get subdirectories when 3+ implementations exist
3. Specialized assets (templates, exceptions) in focused locations


### Module Structure

Modules group presenters by user audience and access requirements. Admin, Front, and Api have different authentication, layouts, and URL patterns – that's why they're separate modules, not just for organization.

```
app/Presentation/
├── Accessory/               ← UI shared across entire application
│   ├── LatteExtension.php
│   └── TemplateFilters.php
├── Admin/
│   ├── BasePresenter.php    ← Admin-specific functionality
│   ├── Auth/                ← Authentication
│   ├── Catalog/             ← Product management
│   │   ├── Brand/
│   │   ├── List/            ← Overview/utility presenters
│   │   └── Product/
│   └── Fulfill/             ← Order processing
└── Front/
    ├── Customer/
    └── Listing/
```

**Keep presenters flat until complexity demands structure:**

```
# Start simple
Dashboard/DashboardPresenter.php

# Grow when needed
Admin/Catalog/Product/ProductPresenter.php
Admin/Catalog/Brand/BrandPresenter.php
Admin/Catalog/List/ListPresenter.php
```

**Create nested structure when:**
- Single functional area has 4+ presenters
- Clear sub-domains emerge (Product management, Order fulfillment)
- Shared logic between related presenters


### Presenter Directory Contents

Each presenter directory contains the presenter class, its templates, and its local components:

```
Product/
├── ProductPresenter.php
├── default.latte
├── edit.latte
└── ProductFormFactory.php   ← Form factory used only by this presenter
```

For template organization details (layouts, partials, @-prefixed files), see the latte-templates skill.

### Base Presenter Strategy

Create BasePresenter for each major module **only when needed:**
- `Admin\BasePresenter` – authentication checks, admin-specific setup
- Contains common `startup()` checks, `beforeRender()` template variables

**Avoid deep inheritance** – prefer composition over inheritance chains deeper than BasePresenter -> SpecificPresenter. Deep chains make it hard to understand which method runs when and create fragile coupling between unrelated presenters.

### Component, Factory, and Accessory Placement

Where to place components, form factories, Latte extensions, and other shared code follows a proximity principle – keep code close to where it's used:

**In the presenter directory** – used by one presenter only:
```
Product/
├── ProductPresenter.php
├── ProductFormFactory.php   ← Only ProductPresenter uses this
└── edit.latte
```

**In Module/Accessory/** – shared across presenters within one module:
```
Admin/
├── Accessory/
│   ├── DataGridFactory.php  ← Used by multiple Admin presenters
│   └── AdminFilters.php     ← Admin-specific template helpers
├── Product/
└── Order/
```

**In Presentation/Accessory/** – shared across modules:
```
Presentation/
├── Accessory/
│   ├── LatteExtension.php     ← App-wide Latte filters/functions
│   ├── NavigationFactory.php  ← Used in Admin and Front
│   └── TemplateFilters.php
```

Form factories that encapsulate form creation with validation and callbacks are preferred over building forms directly in presenters when the same form appears in multiple places. For form factory implementation patterns, see the nette-forms skill.

### When to Create New Module

**Create module when:**
- You have 5+ related presenters
- Functionality has distinct user base (Admin vs Front vs Api)
- Different authentication/authorization requirements
- Separate URL structure patterns

**Avoid modules for:**
- Single presenter with single purpose
- Artificial separation without clear user/functional boundaries


### Tasks and Command Organization

```
app/Tasks/
├── Maintenance/           ← Cleanup, optimization
├── Integration/           ← External data sync
└── Scheduled/             ← Recurring operations
```

**Task responsibility boundaries:**
- Tasks handle execution context (CLI arguments, error handling, scheduling)
- Business logic stays in Model services
- Tasks coordinate, services execute

This separation means business logic is testable without CLI context and reusable from presenters or other entry points.

### Anti-Patterns to Avoid

**Don't separate by technical layer** – Services/, Repositories/, Controllers/ separation forces you to jump between directories for every feature change. Domain organization keeps related code together.

**Don't create deep hierarchies** – prefer descriptive names over nested structure (OrderFulfillmentService vs Fulfill/Order/Service). Deep nesting increases cognitive load and makes imports longer without adding clarity.

### Online Documentation

For details, see the official documentation:

- [Application](https://doc.nette.org/en/application) – presenters, routing, templates
- [Components](https://doc.nette.org/en/application/components) – components and signal handling
- [Directory Structure](https://doc.nette.org/en/application/directory-structure) – recommended layout

---
name: nette-database
description: Invoke before writing database queries or working with Selection API, ActiveRow in Nette. Use when creating entity classes, configuring database connections, writing queries, fetching data, using joins, or designing Row classes. Also consult when deciding between Selection API and raw SQL, or setting up database configuration in .neon files.
---

## Database

Uses Nette Database, typically with MySQL, PostgreSQL or SQLite as the backend.

```shell
composer require nette/database
```

See [the Explorer API reference](references/explorer.md) for the full ActiveRow/Selection API.
See [the SQL query reference](references/sql-way.md) for direct SQL queries.

### Database Conventions

- Table names use **singular form** (e.g., `user` not `users`)
- Use TINYINT(1) for booleans
- Use `id` for primary keys
- Character encoding: `utf8mb4` with appropriate collation (e.g. `utf8mb4_0900_ai_ci`, or `utf8mb4_cs_0900_ai_ci` for Czech)
- Standard timestamp fields:
  - `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
  - never use TIMESTAMP for date/time fields


### Entity Mapping

Nette Database has a built-in `EntityMapping` interface (since 3.2) that the Explorer uses to resolve an `ActiveRow` subclass for each table and (optionally) translate between column names and PHP property names. The default implementation (`DefaultEntityMapping`) supports:

- **Table-to-class map with wildcards.** Keys may be exact names (`special_table`), wildcard patterns (`forum_*`) or a bare `*` catch-all. Class names may contain `*`, which is replaced with the PascalCase of the captured portion: `forum_post` + `App\Forum\*Row` → `App\Forum\PostRow`. A class without `*` is a fixed class (`log_*: App\Logging\LogRow` maps every `log_*` table to the same `LogRow`). Schema prefixes like `public.` are stripped before PascalCase conversion. Exact keys take precedence; wildcard entries are tried in declaration order, so put more specific patterns first and the bare `*` last.
- **camelCase column ↔ property translation** — when enabled, column `author_id` is exposed as property `authorId` everywhere: property access, iteration, `toArray()`, `insert()`, `update()`, and column references in `where()` / `order()`.

Activate via the `mapping` config option (see Database Configuration below).

### Entity Design Strategy

All entities in `App\Entity` with consistent `Row` suffix (matches the `App\Entity\*Row` convention):

- `product` table → `ProductRow`
- `order_item` table → `OrderItemRow`
- `variant_expiration` table → `VariantExpirationRow`

**Why flat:** Entities are data structures that cross domain boundaries. A `ProductRow` might be used in catalog, orders, inventory, and reporting contexts. Subdividing entities by domain forces you to either pick one arbitrary "home" domain or duplicate references.

#### Entity Organization

**All entities in single App\Entity namespace** - avoid domain subdivision:

```
app/Entity/
├── ProductRow.php          ← Core business entities
├── OrderItemRow.php        ← Relationship entities
└── StockTransferRow.php    ← Operational entities
```

#### Entity Documentation Patterns

```php
use Nette\Database\Table;

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read bool $active
 * @property-read ?CategoryRow $category        ← nullable relationship
 * @property-read UserRow $author               ← required relationship
 */
final class ProductRow extends Table\ActiveRow
{
}
```

**Documentation rules:**
1. Document ALL accessible properties (including inherited id)
2. Use nullable types for optional foreign keys
3. Include relationship properties for IDE navigation
4. Match database schema exactly

#### Entity Relationships in phpDoc

**Foreign key patterns:**
- `@property-read ?CategoryRow $category` for optional relationships
- `@property-read UserRow $author` for required relationships
- `@property-read Selection<OrderItemRow> $order_items` for back-references

**Naming convention:** Follow Nette Database relationship naming (foreign key without _id suffix).

**With `camelCase: true` mapping:** scalar columns map to camelCase properties (`$firstName`, `$createdAt`). Relationship property names (`$author`, `$category`) are resolved by `Conventions` against database columns, so they aren't affected by the camelCase setting.

### When to Use Selection API

**Use for:**
- Simple filtering and sorting
- Standard CRUD operations
- Queries that benefit from lazy loading
- When you need to chain conditions dynamically

```php
return $this->db->table('product')
	->where('active', true)
	->where('category_id', $categoryId)
	->order('name');
```

### When to Use Raw SQL

**Use for:**
- Complex analytics and reporting
- Recursive queries (WITH RECURSIVE)
- Performance-critical queries
- Complex joins that are awkward in Selection API

```php
return $this->db->query('
	WITH RECURSIVE category_tree AS (...)
	SELECT ...
', $params)->fetchAll();
```

### Query Building Patterns

Build queries by progressive refinement – start with a base method, then add conditions. Always use generic types for Selection returns:

```php
/** @return Selection<ProductRow> */
public function getProducts(): Selection
{
	return $this->db->table('product');
}

/** @return Selection<ProductRow> */
public function getActiveProducts(): Selection
{
	return $this->getProducts()->where('active', true);
}

/** @return Selection<ProductRow> */
public function getProductsInCategory(int $categoryId): Selection
{
	return $this->getActiveProducts()
		->where(':product_category.category_id', $categoryId);
}
```

### Relationship Navigation

**Use colon notation for efficient joins:**

```php
// Forward relationship (via foreign key)
->where('category.slug', $categorySlug)

// Back-reference (reverse relationship)
->where(':order_item.quantity >', 1)

// Deep relationships
->where('category.parent.name', 'Root Category')
```

### Traps in where() and SQL Fragments

**The array KEY is SQL, the value is a bound parameter.** `where()` splits the key on the first
space into a column and an operator and emits both into the query, so a key built from user
input is an injection hole – and mass-assigning a request array into `where()` or `insert()`
lets the client choose columns and operators:

```php
$db->table('user')->where(['id >' => 5]);        // fine: key is yours, value is bound
$db->table('user')->where($_GET['filter']);      // NEVER: keys come from the client
```

**Write SQL keywords in UPPERCASE inside fragments.** Explorer quotes lowercase words as
identifiers, so `where('name like ?', $x)` compiles to `` `name` `like` ? `` and fails; uppercase
words are left alone:

```php
->where('name LIKE ?', "%$q%")      // correct
->where('name like ?', "%$q%")      // `like` gets quoted as a column name
```

**An empty array flips the meaning of a condition.** `where('id', [])` becomes
`IS NULL AND FALSE` (matches nothing), while `where('id NOT', [])` becomes `IS NULL OR TRUE`
(matches **everything**). Guard empty filter lists explicitly. Mixing operators without
parentheses raises `Possible SQL query corruption. Add parentheses around operators.`

### Fetching Strategies by Use Case

**Single optional result:** `->fetch()`
**All results as array:** `->fetchAll()`
**Key-value pairs:** `->fetchPairs('key_column', 'value_column')`
**Single scalar value:** `->fetchField()` (first column of first row)
**Count only:** `->count('*')`

**Structured data with fetchAssoc:**

```php
// Key by column value
$byId = $db->table('product')->fetchAssoc('id');
// [1 => ProductRow, 2 => ProductRow, ...]

// Group by column
$byCategory = $db->table('product')->fetchAssoc('category_id[]');
// [5 => [ProductRow, ProductRow], 8 => [ProductRow, ...]]

// Nested grouping
$nested = $db->table('product')->fetchAssoc('category_id|active');
// [5 => [true => ProductRow, false => ProductRow], ...]
```

The path string uses `[]` for array grouping, `|` for nested keys, and `=` to extract a single value.

### Schema and Constraints

**Use direct SQL migrations** rather than ORM-style migrations – store schema in `sql/db.sql` with manual migration scripts. Rely on database constraints (foreign keys, unique, check) for data integrity and handle constraint violations in services with meaningful business exceptions.

### Transactions

Wrap multi-step writes in transactions to ensure consistency:

```php
$this->db->transaction(function () use ($data, $items) {
	$order = $this->db->table('order')->insert($data);
	foreach ($items as $item) {
		$order->related('order_item')->insert($item);
	}
});
```

The callback approach automatically commits on success and rolls back on exception, and it
**returns whatever the callback returns**, so you can produce a value out of it.

Nested `transaction()` calls are counted, and only the outermost one actually commits. Calling
`beginTransaction()`, `commit()` or `rollBack()` manually inside the callback throws
`LogicException` – pick one style per code path and stay with it.

### Anti-Patterns to Avoid

**Don't create separate Repository classes** – in Nette, services combine data access with business logic. A separate repository layer adds indirection without benefit because Nette Database Explorer already provides a clean query API. The service IS the repository.

### Error Handling

**Transform database errors to business exceptions:**

```php
try {
	$customer->update(['email' => $newEmail]);
} catch (Nette\Database\UniqueConstraintViolationException) {
	throw new EmailAlreadyExistsException();
}
```

**Handle at service boundary** – presenters should receive business exceptions, not database exceptions. This keeps the UI layer independent of the storage layer and produces meaningful error messages.

### Database Configuration

```neon
database:
	dsn: 'mysql:host=127.0.0.1;dbname=myapp'
	user: root
	password: secret

	# Entity mapping (table → ActiveRow subclass, optional column ↔ property)
	mapping:
		tables: App\Entity\*Row    # string shortcut: equivalent to { '*': 'App\Entity\*Row' }

	options:
		lazy: true              # Connect on first query
		charset: utf8mb4        # Default
		convertBoolean: true    # TINYINT(1) to bool
		newDateTime: true       # Return DateTimeImmutable
```

Full mapping form when you need explicit overrides or camelCase properties:

```neon
database:
	dsn: 'mysql:host=127.0.0.1;dbname=myapp'
	mapping:
		tables:
			special_table: App\Entity\SpecialRow    # exact match wins over wildcards
			forum_*: App\Forum\*Row                 # wildcard in key (* captured as PascalCase)
			"*": App\Entity\*Row                    # catch-all fallback (must be last)
		camelCase: true         # column author_id ↔ property authorId
```

Multiple connections:

```neon
database:
	main:
		dsn: 'mysql:host=127.0.0.1;dbname=app'
		user: root
		password: secret

	logs:
		dsn: 'mysql:host=127.0.0.1;dbname=logs'
		user: logs
		password: secret
		autowired: false  # Must reference explicitly
```

Reference non-autowired connection:

```neon
services:
	- LogService(@database.logs.connection)
```

### Online Documentation

For details, see the official documentation:

- [SQL way](https://doc.nette.org/en/database/sql-way) – SQL queries and Connection API
- [Database Explorer](https://doc.nette.org/en/database/explorer) – Selection/ActiveRow API
- [Database Configuration](https://doc.nette.org/en/database/configuration) – connection setup

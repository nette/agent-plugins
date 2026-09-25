---
name: latte-templates
description: Invoke before creating or modifying .latte files, even for single-line changes. Provides Latte syntax, tags, filters, n:attributes, layouts, template inheritance, AJAX snippets, extensions, and Nette integration. Also trigger when user mentions Latte by name.
---

## Latte Templating System

Latte is a secure templating engine with context-aware escaping, intuitive syntax, and powerful template inheritance.

```shell
composer require latte/latte
```

### Basic Syntax

```latte
{* this is a comment *}
<ul n:if="$items">                {* n:attribute *}
{foreach $items as $item}         {* tag *}
	<li>{$item|capitalize}</li>   {* variable with filter *}
{/foreach}
</ul>
```

### Printing Variables

```latte
{$name}                    {* prints escaped variable *}
{$user->name}              {* object property *}
{$items[0]}                {* array access *}
{='hello'|upper}           {* expression with filter *}
{$html|noescape}           {* disable escaping (use carefully!) *}
```

Latte uses **context-aware escaping** — output adapts to where the variable is printed. In `<script>` context, arrays and objects are automatically serialized to JSON:

```latte
{* Arrays auto-serialize to JSON inside <script> *}
<script type="application/ld+json">{$schemaData}</script>

<script>
	let config = {$config};
</script>
```

Never use `json_encode()` in PHP + `|noescape` for this — Latte handles serialization and escaping safely on its own.

### Filters

Filters modify output, written after `|`:

```latte
{$title|upper}                    {* HELLO *}
{$text|truncate:100}              {* shortens to 100 chars *}
{$price|number:2}                 {* formats number *}
{$date|date:'j. n. Y'}            {* formats date *}
{$name|lower|capitalize}          {* chained filters *}
```

Common filters: `upper`, `lower`, `capitalize`, `truncate`, `number`, `date`, `noescape`, `escapeUrl`, `stripHtml`, `trim`, `replace`, `first`, `last`, `length`, `sort`, `reverse`

The filter `|` applies to the entire expression before it. Parentheses around PHP operators (like `??`) before a filter are unnecessary:

```latte
{$a ?? $b|texyLine|noescape}        {* correct – filter applies to result of ?? *}
{($a ?? $b)|texyLine|noescape}      {* redundant – parentheses change nothing *}
```

See [the complete filter reference](references/filters.md) for all available filters.

### n:attributes

Pair tags can be written as HTML attributes:

```latte
{* These are equivalent: *}
{if $condition}<div>...</div>{/if}
<div n:if="$condition">...</div>

{* Applies to element content only: *}
<div n:inner-foreach="$items as $item">...</div>

{* Applies to tag only (not content): *}
<a href={$url} n:tag-if="$url">Link</a>
```

### Conditions and Loops

`{if}` / `{elseif}` / `{else}`, `{foreach}` and `{for}` work as expected. What is Latte-specific:

```latte
{ifset $user}...{/ifset}          {* the variable exists, unlike {if isset()} *}

{switch $type}                    {* no break, cases do not fall through *}
	{case admin}Administrator
	{case user}User
	{default}Guest
{/switch}

{foreach $items as $item}
	<li>{$item}</li>
{else}                            {* runs when the array is empty *}
	<li>No items found</li>
{/foreach}

{foreach $items as $item}
	{$iterator->counter}. {$item}   {* 1, 2, 3…; also ->first, ->last, ->odd, ->even *}
	{first}<ul>{/first}             {* rendered only in the first iteration *}
	{last}</ul>{/last}
	{sep}, {/sep}                   {* separator, skipped after the last item *}
{/foreach}
```

### Variables

```latte
{var $name = 'John'}
{var $items = [1, 2, 3]}
{default $lang = 'en'}            {* only if not set *}

{capture $content}
	<p>Captured HTML</p>
{/capture}
{$content}
```

### Template Inheritance

**Layout template (`@layout.latte`):**

```latte
<!DOCTYPE html>
<html>
<head>
	<title>{block title}Default{/block}</title>
</head>
<body>
	{block content}{/block}
</body>
</html>
```

**Child template:**

```latte
{layout '@layout.latte'}

{block title}My Page{/block}

{block content}
	<h1>Welcome</h1>
	<p>Content here</p>
{/block}
```

### Including Templates

```latte
{include 'header.latte'}
{include 'item.latte', item: $item, showPrice: true}
{include $dynamicTemplate}
```

### Blocks

```latte
{block sidebar}
	<aside>Sidebar content</aside>
{/block}

{include sidebar}                          {* print block *}
{include sidebar from 'other.latte'}       {* from another file *}

{* Reusable definitions with parameters *}
{define button, $text, $type = 'primary'}
	<button class="btn btn-{$type}">{$text}</button>
{/define}

{include button, 'Submit'}
{include button, 'Cancel', 'secondary'}
```

### Other Common Tags

| Tag | Description |
|-----|-------------|
| `{do expression}` | Execute without output |
| `{php expression}` | Obsolete alias for `{do}` – a single expression only. Real PHP code needs `RawPhpExtension` |
| `{dump $var}` | Debug dump (Tracy) |

See [the complete tag reference](references/tags.md) for all available tags.

### Smart HTML Attributes (Latte 3.1)

Applies only when the **whole** attribute value is one `{...}` expression (`title={$t}` or
`title="{$t}"`). In a mixed value (`title="foo {$t}"`) none of it applies – null becomes `''`,
an array becomes `"Array"`. `n:attr` follows the same rules, so `{if}` / `n:attr` wrappers
around attributes are obsolete.

| Kind | Attributes | Value handling |
|---|---|---|
| bool | `allowfullscreen async autofocus autoplay checked controls default defer disabled formnovalidate inert ismap itemscope loop multiple muted nomodule novalidate open playsinline readonly required reversed selected` | truthy → bare name; falsey (`false`, `null`, `0`, `''`, `[]`) → omitted |
| tristate | `contenteditable draggable spellcheck writingsuggestions` | `bool` → the keywords `"true"` / `"false"`; `null` omits the attribute |
| valued bool | `hidden popover` | flag like a bool, but a non-empty value survives (`hidden="until-found"`); falsy is tested first, so `'0'` omits it rather than rendering an invalid value |
| list | `accesskey class headers itemprop ping rel role sandbox` | array → space-separated |
| style | `style` | array → `prop: value; …` |
| data | `data-*` | array / `stdClass` → JSON; `bool` → `"true"` / `"false"` |
| aria | `aria-*` | `bool` → `"true"` / `"false"`; array → space-separated |
| plain | everything else | `string`/`int`/`float`/`Stringable` printed; `null` omits the attribute; array, `bool` or other object → `E_USER_WARNING` and the attribute is dropped |

In list/aria arrays a plain item is used as-is, a keyed item yields the **key** only if its
value is `=== true` (`active => 1` renders `1`, not `active`); items with `null`, `false`, `0`,
`''` are skipped, an all-skipped array omits the attribute. In `style`, a keyed item is skipped
by the same rule, so `opacity => 0` vanishes (`'0px'` survives).

```latte
<input type="text" disabled={$disabled} readonly={$readonly}>
{* false, true → <input type="text" readonly> *}

<div title={$title}>x</div>          {* null → <div>x</div> *}
<div title="Hi {$name}">x</div>      {* null → <div title="Hi ">x</div> (mixed value) *}
<div title={$title|upper}>x</div>    {* null → <div title="">x</div> (filter stringifies null) *}
<div title={$title?|upper}>x</div>   {* null → <div>x</div> (?| preserves the drop) *}

<button class={[btn, btn-primary, active => $isActive, disabled => $isDisabled]}>x</button>
{* true, false → <button class="btn btn-primary active">x</button> *}

<a rel={[nofollow, external => $isExternal]}>l</a>  {* true → <a rel="nofollow external">l</a> *}
<span title={[a, b]}></span>  {* warning: array is not allowed for 'title'; attribute dropped *}
<input value={$rows}>         {* array → same warning, no more value="Array" *}

<div style={[background => lightblue, opacity => 0, display => $vis ? block : null]}></div>
{* false → <div style="background: lightblue"></div> *}

<div data-config={[theme: dark, version: 2]} data-open={=true}></div>
{* → <div data-config='{"theme":"dark","version":2}' data-open="true"></div> *}

<button aria-expanded={=false} aria-labelledby={[title, desc => $has]}></button>
{* false → <button aria-expanded="false" aria-labelledby="title"></button> *}

<div data-active={$on|toggle}></div>  {* toggle = bool handling for any attribute; false → <div></div> *}
<div title={$cfg|json}></div>         {* json (must be the last filter) = JSON handling *}
```

A real filter defeats smart handling – it stringifies the value: `class={$arr|noescape}`
renders `class="Array"`. Keep smart attributes filter-free; `toggle`, `json`, `nocheck` and
`accept` are compile-time hints, not filters, and are safe.

Traps:

- **URL attributes are exempt** (`href`, `src`, `action`, `formaction`, `<object data>`): Latte
  appends an internal `checkUrl` filter, so `href={$url}` with null renders `href=""`, and an
  array gives `href="Array"` with only a PHP notice. Use `href={$url?|nocheck}` for the drop
  (this also skips the URL safety check).
- Tristate attributes distinguish **three** states: `true` and `false` render the keyword,
  `null` drops the attribute. That matters, because "missing" and `="false"` mean different
  things — `contenteditable="false"` disables editing inside an editable region, whereas a
  missing attribute inherits from the parent.

#### Prefer `class={[...]}` and smart attributes over `n:class` / `n:attr`

Since 3.1 the attribute forms cover almost everything the `n:` ones did, so new templates
should reach for them first. Neither `n:class` nor `n:attr` is deprecated in Latte, and there
is one case only `n:attr` can express (below), so this is a preference, not a removal.

**Do not rewrite existing templates mechanically** — two differences change the rendered
output:

```latte
<a n:class="$cnt ? open, item">      {* → *}  <a class={[$cnt ? open, item]}>  {* keep the ternary *}
<a class={[open => $cnt]}>           {* wrong – $cnt = 3 renders class="3" *}
<a class={[sel => $row]}>            {* wrong – a Stringable renders its string, a plain object throws *}
<option n:attr="selected: $sel">     {* → *}  <option selected={$sel}>
```

1. **A keyed item applies only when its value is `=== true`.** With any other truthy value the
   *value* is printed instead of the key, which is silent. Keep the ternary rather than moving
   the condition into a key.
2. **`n:class` de-duplicates the list** (`array_unique`), `class={[...]}` does not — the same
   class listed twice survives after the rewrite.

`n:class` additionally rejects an array variable (`n:class="$arr"` → `class="Array"`; use
`class={$arr}`) and a sibling `class="…"` on the same tag (CompileException); both fail loudly.

The one case `n:attr` still owns is a **dynamic** set or name of attributes, which no static
attribute can express:

```latte
<tr n:attr="$attrs">          {* ['data-id' => 5, 'class' => 'row'] → data-id="5" class="row" *}
<a n:attr="[$name => $val]">  {* attribute name from a variable *}
```

**Migration from 3.0** – changed: `null` used to print `=""`; `data-`/`aria-` bools printed
`"1"`/`""`; `data-` arrays printed a space-separated list; `on*` values were JSON-encoded.
`$engine->setFeature(Latte\Feature::MigrationWarnings, true)` warns on every affected
attribute; the `|accept` filter silences it per value.

---

## Latte in Nette Applications

### Template Organization Strategy

**Keep templates with presenters:**

```
Product/
├── ProductPresenter.php
├── default.latte
├── edit.latte
└── detail.latte
```

**Layout placement follows presenter organization:**

```
Admin/
├── @layout.latte           ← Admin-wide layout
├── Auth/
│   ├── @layout.latte       ← Auth-specific layout
│   └── AuthPresenter.php
└── Catalog/
	└── Product/
		├── ProductPresenter.php
		└── edit.latte
```

The more specific a layout, the closer it sits to the presenters it serves; whether it extends the enclosing one is up to it. Keep such chains short: a part that only some pages share usually fits better through `{include}` or `{define}` than through another layout level.

### Template Partial Patterns

**Shared template parts use @ prefix:**
- `@layout.latte` - layout templates
- `@form.latte` - reusable form structures
- `@item.latte` - list item templates

### Passing Variables to Templates

Templates display data; computing it (calculations, filtering, aggregation) belongs in `render<View>()` or a service. The standard way is assigning to `$this->template`:

```php
$this->template->article = $this->articles->getById($id);
```

For properties that should always be available in templates, use the `#[TemplateVariable]` attribute (the property **must be public** – on a protected one it throws `LogicException`) instead of repeating assignments in every action:

```php
use Nette\Application\Attributes\TemplateVariable;

class ArticlePresenter extends Nette\Application\UI\Presenter
{
	#[TemplateVariable]
	public string $siteName = 'My blog';
}
```

The property value is automatically passed as `$siteName` in every template. If you explicitly assign `$this->template->siteName` in an action, the explicit value wins.

### Template Class Strategy

**Create template classes for complex presenters:**

```php
/**
 * @property-read ProductTemplate $template
 */
class ProductPresenter extends BasePresenter
{
}

class ProductTemplate extends Nette\Bridges\ApplicationLatte\Template
{
	public ProductRow $product;
	public array $variants;
	public ?CategoryRow $category;
}
```

**When to use template classes:**
- Presenters with 5+ template variables
- Complex data structures passed to templates
- When you want full IDE support in templates

**Template Type Declaration:**

```latte
{templateType App\Presentation\Product\ProductTemplate}

<h1>{$product->name}</h1>
{foreach $variants as $variant}
	<div class="variant">{$variant->name} - {$variant->price}</div>
{/foreach}
```

### Nette-specific Tags

```latte
{* Links *}
<a n:href="Product:detail $id">Detail</a>
<a href={link Product:detail $id}>Detail</a>
<a href={link //Product:detail $id}>Absolute (// makes it absolute)</a>
<a href={plink Product:detail $id}>From a component template, link to a PRESENTER</a>

{* Components *}
{control productForm}
{control dataGrid}

{* AJAX Snippets *}
{snippet items}
	{foreach $items as $item}
		<div>{$item->name}</div>
	{/foreach}
{/snippet}

{* Forms *}
{form loginForm}
	{label username}{input username}
	{label password}{input password}
	{input submit}
{/form}

{* Assets (Nette Assets) *}
{asset 'admin.js'}
{asset 'front.css'}
```

### Extension and Customization

**Create single extension for entire application:**

```php
final class LatteExtension extends Latte\Extension
{
	public function getFilters(): array
	{
		return [
			'money' => fn($amount) => number_format($amount, 0, ',', ' ') . ' Kč',
		];
	}

	public function getFunctions(): array
	{
		return [
			'canEdit' => fn($entity) => $this->user->isAllowed($entity, 'edit'),
		];
	}
}
```

**Register in config:**

```neon
latte:
	strictParsing: yes
	extensions:
		- App\Presentation\Accessory\LatteExtension
```

Enable `strictParsing` to catch template errors early (missing variables, typos in tag names).

### Online Documentation

For details, see the official documentation:

- [Syntax](https://latte.nette.org/en/syntax) – complete syntax guide
- [Tags](https://latte.nette.org/en/tags) – all available tags
- [Filters](https://latte.nette.org/en/filters) – all filters with examples
- [Template Inheritance](https://latte.nette.org/en/template-inheritance) – layouts, blocks, embed
- [Extending Latte](https://latte.nette.org/en/extending-latte) – custom tags, filters, extensions
- [Type System](https://latte.nette.org/en/type-system) – template types and IDE support

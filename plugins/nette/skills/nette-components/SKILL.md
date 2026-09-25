---
name: nette-components
description: Invoke before writing or modifying a Nette component (Control), a component factory or a signal handler, and before any AJAX/snippet code. Use when working with createComponent<Name>(), $this['name'], handle<Signal>() methods, signal links (n:href="click!", link('click')), {plink} in a component template, persistent parameters (#[Persistent]) and persistent components, redrawControl()/isControlInvalid(), {snippet} and {snippetArea}, dynamic snippets, isAjax() and $this->payload, Multiplier, or the component tree (getComponents(), getComponentTree(), monitor(), lookup(), $onAnchor). Also trigger for "snippet is not redrawing", BadSignalException, or "how do I do AJAX in Nette".
---

## Nette Components, Signals and Snippets

A component is a descendant of `Nette\Application\UI\Control`, and the presenter is itself a `Control`
(`ComponentModel\Component` -> `Container` -> `UI\Component` -> `UI\Control` -> `UI\Presenter`), so
everything below applies to both. Related skills: **nette-forms** (controls, validation, `onSuccess`),
**nette-architecture** (where components live), **latte-templates** (`{control}`/`{snippet}` syntax),
**nette-configuration** (generated factories), **frontend-development** (Naja on the JS side).

### Component Factories

Any container (presenter or control) creates children lazily through `createComponent<Name>()`:

```php
protected function createComponentPoll(): PollControl
{
	return new PollControl($this->items);
}
```

The factory is never called directly. It fires on first access – `$this->getComponent('poll')` or the
equivalent `$this['poll']` (the `ComponentModel\ArrayAccess` trait), which is what `{control poll}` does.
An untouched component is never built.

- **The name starts with a lowercase letter.** `Container::createComponent()` requires
  `ucfirst($name) !== $name`, so asking for `Poll` never reaches `createComponentPoll()`.
- The factory receives the name as its first argument, and may either return the component or add it
  itself with `addComponent()`; doing neither throws `Nette\UnexpectedValueException`.
- Names match `#^[a-zA-Z0-9_]+$#D` – **no hyphen in a name**, `-` is the tree separator:
  `$this['cart-form']` means "child `form` of child `cart`", nesting to any depth.
- Not presenter-only: a `Control` defines `createComponent<Name>()` for its own children the same way.
- For components with dependencies, inject a factory (a class or a DI-generated interface) and call it
  from `createComponent<Name>()` – see **nette-configuration** for `implement:`.

### Signals

A signal performs an action on the *current* page. It calls `handle<Signal>()` on the component
addressed by the `do` query parameter (`?do=cart-remove`); matching is case-insensitive.

```php
public function handleDelete(int $id): void   // ?do=delete&id=…
{
	$this->facade->delete($id);
	$this->redirect('this');
}
```

**Where signals run** – **after** the action phase and canonicalization, **before** any render
method, not "in the action phase" (the full lifecycle order is in **nette-architecture**).
Consequence: a handler can prepare data that `render<View>()` then respects
(`if (!isset($this->template->articles)) …`).

- A signal cannot target another presenter or action; it re-runs the current request plus the handler.
  A missing handler throws `BadSignalException` (403 page).
- Signal parameters come from the URL **and from the POST body**, but a URL parameter of the same name
  wins. They share one namespace with action and persistent parameters.
- **A form submit is not a signal.** `UI\Form::signalReceived()` accepts only the signal `submit`
  and turns it into `onSubmit`/`onSuccess`; any other signal name on a form throws `BadSignalException`.
  Never write `handleSubmit()` for a form – put the logic in an `onSuccess` handler (see **nette-forms**).

### Links Inside a Component

In a component template, `n:href`, `{link}` and `$this->link()`/`$this->redirect()` **always mean a
signal of that component**, with or without `!` – `LinkGenerator` switches to signal mode as soon as the
link source is not a `Presenter`.

```latte
{* inside a component template *}
<a n:href="click!">signal</a>          {* the ! is optional here *}
<a n:href="subcomponent:click!">signal of a child</a>
<a href={plink Home:default}>presenter link</a>
```

`{plink}` = **presenter link**: it compiles to `$this->global->uiPresenter->link(...)`, where `{link}`
and `n:href` compile to `$this->global->uiControl->link(...)`. It is *not* "absolute" and *not*
"persistent" – an absolute URL comes from the `//` prefix, which works with `{link}` too. In PHP the
equivalent is `$this->getPresenter()->link('Home:default')`.

The only non-signal destination inside a component is `'this'` (current presenter + action):
`$this->redirect('this')`. Arguments cannot be passed to `this!`.

### Persistent Parameters

A persistent parameter survives across requests in the URL, automatically, including links generated by
other components on the page.

```php
use Nette\Application\Attributes\Persistent;

class ItemList extends Control
{
	#[Persistent]
	public int $page = 1;   // must be public, non-static, typed
}
```

`loadState()` writes the URL value into the property and answers a type mismatch with a 404 – override it
for range checks; `saveState()` is the reverse direction. In links, `<a n:href="this page: $page + 1">`
changes the value and `page: null` resets it to the default (drops it from the URL).

**Persistent components** use the same attribute on the *presenter class*, listing component names;
their persistent parameters then survive across actions and presenters, subcomponents included:

```php
#[Persistent('calendar', 'poll')]
class DefaultPresenter extends Nette\Application\UI\Presenter
{
}
```

The annotation form `/** @persistent */` still works but triggers `E_USER_DEPRECATED` – always use the
attribute.

### AJAX and Snippets

```php
public function handleLike(int $id): void
{
	$this->service->like($id);
	if ($this->isAjax()) {
		$this->redrawControl('articles');   // NOT invalidateControl()
	} else {
		$this->redirect('this');
	}
}
```

- The method is **`redrawControl(?string $snippet = null, bool $redraw = true)`**. `invalidateControl()`
  does not exist any more – calling it dies with `Nette\MemberAccessException`. Pass `redraw: false` to
  cancel a pending invalidation. No argument invalidates the whole control.
- If **nothing** is invalidated, an AJAX request returns the whole page instead of snippets;
  `isControlInvalid()` is what the presenter checks, and it walks into child controls.
- **`redirect()` during AJAX does not send a 302.** It writes the URL into `payload.redirect` and sends
  the JSON payload; the browser side (Naja) performs the redirect. Therefore a handler that redirects
  ends the request – redraw *or* redirect, not both.
- Extra data for the client goes into the payload: `$this->payload->message = 'ok';`. The snippet's HTML
  id is `snippet-<uniqueId>-<name>` (`getSnippetId()`).

**A snippet redraw calls `render()` with no arguments** – Nette re-renders an invalidated child with a
plain `$child->render()`, so parameterized rendering is incompatible with snippets. Pass such values
through the factory, a setter or a persistent parameter instead.

```latte
{control productGrid}                  {* OK *}
{control productGrid $arg}             {* breaks on AJAX redraw *}
{control productGrid:paginator}        {* breaks on AJAX redraw *}
```

**Dynamic snippets** (a variable in the name) must sit inside a static `{snippet}` or `{snippetArea}`,
otherwise you get `E_USER_WARNING: Dynamic snippets are allowed only inside static snippet/snippetArea.`
Invalidate the *wrapper* – invalidating `item-5` alone redraws nothing, because that name only exists
once the surrounding loop runs.

```latte
<ul n:snippetArea="itemsContainer">
	{foreach $items as $id => $item}
		<li n:snippet="item-{$id}">{$item}</li>
	{/foreach}
</ul>
```

```php
$this->redrawControl('itemsContainer');
```

A redrawn area still *renders* every item and throws the rest away, so narrow the data in the handler
(`$this->template->items = [$one]`). The dynamic-snippet-free alternative is one `Control` per row plus
`Multiplier`, where only the affected instance renders.

### The Component Tree

```php
$this->getComponents();      // immediate children only, array, NO arguments
$this->getComponentTree();   // all descendants, depth-first, list
array_filter($this->getComponentTree(), fn($c) => $c instanceof Control);
```

- **`getComponents()` takes no arguments.** The old `$deep`/`$filterType` arguments left the signature in
  3.1 and throw `Nette\DeprecatedException` in 4.0, so `getComponents(true)` or
  `getComponents(recursive: true)` is always wrong. `getComponentTree()` has existed since 3.1 – it is
  not a new 4.0 API.
- `monitor($type, $attached, $detached)` reacts to (de)attachment; the overridable `attached()` /
  `detached()` methods were **removed in 4.0**, and `monitor()` with no handler throws
  `InvalidStateException`. In 4.0 attach callbacks fire top-down, detach callbacks bottom-up.
- `lookup($type)` returns the nearest ancestor of that type, `lookupPath($type)` the `-`-joined path from
  it – the basis of `getUniqueId()` and of the `do` parameter.
- A component **has no presenter in its constructor**: no links, no persistent parameter values there.
  Use `$onAnchor[]` (or `monitor(Presenter::class, ...)`) for setup that needs the presenter.
- `ComponentModel\Component` dropped the `SmartObject` trait in 4.0 (`UI\Component` still adds it).

### Multiplier

Creates a variable number of children on demand; the callback gets the child name and the multiplier.

```php
protected function createComponentLikeControl(): Multiplier
{
	return new Multiplier(fn(string $articleId) => new LikeControl($this->articles->get($articleId)));
}
```

```latte
{control "likeControl-$article->id"}
```

### Online Documentation

For details, see the official documentation:

- [Components](https://doc.nette.org/en/application/components) – factories, signals, persistent parameters
- [AJAX & Snippets](https://doc.nette.org/en/application/ajax) – redrawing, payload, snippet areas
- [Dynamic Snippets](https://doc.nette.org/en/best-practices/dynamic-snippets) – both approaches compared
- [Multiplier](https://doc.nette.org/en/application/multiplier) – dynamic component creation
- [Creating Links](https://doc.nette.org/en/application/creating-links) – signal syntax, links in components
- [Component Model](https://doc.nette.org/en/component-model) – tree API, monitoring, upgrading notes

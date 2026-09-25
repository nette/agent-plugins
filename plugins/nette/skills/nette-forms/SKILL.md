---
name: nette-forms
description: Invoke before creating or modifying Nette Forms. Provides form controls, validation, rendering patterns. Use when working with form factories, form controls, validation rules, form events, rendering forms in Latte, Bootstrap integration, form containers, or form error handling. Also trigger for $form/$data in Nette context.
---

## Nette Forms

Nette Forms provides secure, reusable forms with automatic validation on both client and server side.

```shell
composer require nette/forms
```

### Forms in Presenters

**Use `Nette\Application\UI\Form`, not `Nette\Forms\Form`.** The former extends the latter and
adds `SignalReceiver`, so it can pick up the submit signal and reach the presenter;
`Nette\Forms\Form` is the standalone variant and will not receive submitted data as a
presenter component.

Forms are created in factory methods named `createComponent<Name>`:

```php
protected function createComponentRegistrationForm(): Form
{
	$form = new Form;
	$form->addText('name', 'Name:')
		->setRequired('Please enter your name.');

	$form->addEmail('email', 'Email:')
		->setRequired('Please enter your email.');

	$form->addPassword('password', 'Password:')
		->setRequired('Please enter password.')
		->addRule($form::MinLength, 'Password must be at least %d characters', 8);

	$form->addSubmit('send', 'Register');

	$form->onSuccess[] = $this->registrationFormSucceeded(...);
	return $form;
}

private function registrationFormSucceeded(Form $form, \stdClass $data): void
{
	// $data->name, $data->email, $data->password
	$this->flashMessage('Registration successful.');
	$this->redirect('Home:');
}
```

Render in template:

```latte
{control registrationForm}
```

### Create and Edit Pattern

Unified form for both creating and editing records:

```php
class ProductPresenter extends BasePresenter
{
	public function __construct(
		private ProductFacade $facade,
	) {}

	public function actionEdit(?int $id = null): void
	{
		if ($id) {
			$product = $this->facade->getProduct($id);
			$this->template->product = $product;
			$this['productForm']->setDefaults($product->toArray());
		}
	}

	protected function createComponentProductForm(): Form
	{
		$form = new Form;
		$form->addText('name', 'Name:')
			->setRequired();
		$form->addTextArea('description', 'Description:');
		$form->addInteger('price', 'Price:')
			->setRequired()
			->addRule($form::Min, 'Price must be positive', 1);
		$form->addSubmit('send', 'Save');
		$form->onSuccess[] = $this->productFormSucceeded(...);
		return $form;
	}

	private function productFormSucceeded(Form $form, \stdClass $data): void
	{
		$id = $this->getParameter('id');
		if ($id) {
			$this->facade->update($id, (array) $data);
			$this->flashMessage('Product updated.');
		} else {
			$this->facade->create((array) $data);
			$this->flashMessage('Product created.');
		}
		$this->redirect('default');
	}
}
```

Template:

```latte
{block content}
<h1>{if $product}Edit: {$product->name}{else}New Product{/if}</h1>

{form productForm}
	<table>
		<tr><td>{label name}{input name}</td></tr>
		<tr><td>{label description}{input description}</td></tr>
		<tr><td>{label price}{input price}</td></tr>
	</table>
	{input send}
{/form}
{/block}
```

Defaults are set in `actionEdit()` via `$form->setDefaults()` – never set defaults in the template.

### Form Reuse with Factory

A common base `FormFactory` creates `Form` instances with shared configuration (CSRF, renderer, translation). Changing it in one place affects all forms in the application – no need to hunt through individual presenters:

```php
final class FormFactory
{
	public function create(): Form
	{
		$form = new Form;
		// Shared setup for all forms: renderer, translator, default classes, etc.
		return $form;
	}
}
```

For specific forms used in multiple places, create a dedicated factory:

```php
final class ProductFormFactory
{
	public function __construct(
		private FormFactory $formFactory,
	) {}

	public function create(callable $onSuccess): Form
	{
		$form = $this->formFactory->create();
		$form->addText('name', 'Name:')
			->setRequired();
		$form->addTextArea('description', 'Description:');
		$form->addInteger('price', 'Price:')
			->setRequired();
		$form->addSubmit('send');
		$form->onSuccess[] = function (Form $form, \stdClass $data) use ($onSuccess) {
			$onSuccess($data);
		};
		return $form;
	}
}
```

Use in presenter:

```php
public function __construct(
	private ProductFormFactory $productFormFactory,
) {}

protected function createComponentProductForm(): Form
{
	return $this->productFormFactory->create(
		function (\stdClass $data): void {
			$this->facade->save($data);
			$this->redirect('default');
		},
	);
}
```

### Common Form Controls

| Method | Creates |
|--------|---------|
| `addText($name, $label)` | Text input |
| `addPassword($name, $label)` | Password input |
| `addTextArea($name, $label)` | Multi-line text |
| `addEmail($name, $label)` | Email with validation |
| `addInteger($name, $label)` | Integer input |
| `addFloat($name, $label)` | Decimal input |
| `addCheckbox($name, $caption)` | Checkbox |
| `addCheckboxList($name, $label, $items)` | Multiple checkboxes |
| `addRadioList($name, $label, $items)` | Radio buttons |
| `addSelect($name, $label, $items)` | Dropdown |
| `addMultiSelect($name, $label, $items)` | Multi-select |
| `addUpload($name, $label)` | File upload |
| `addMultiUpload($name, $label)` | Multiple files |
| `addDate($name, $label)` | Date picker |
| `addTime($name, $label)` | Time picker |
| `addDateTime($name, $label)` | Combined date+time picker |
| `addHidden($name)` | Hidden field |
| `addSubmit($name, $caption)` | Submit button |
| `addButton($name, $caption)` | Button |

See [the complete control reference](references/controls.md) for all form controls.

### Basic Validation

```php
$form->addText('name')
	->setRequired('Name is required.')
	->addRule($form::MinLength, 'At least %d characters', 3)
	->addRule($form::MaxLength, 'Maximum %d characters', 100);

$form->addEmail('email')
	->setRequired()
	->addRule($form::Email, 'Invalid email format.');

$form->addInteger('age')
	->addRule($form::Range, 'Age must be between %d and %d', [18, 120]);

$form->addPassword('password')
	->setRequired()
	->addRule($form::MinLength, 'At least %d characters', 8);

$form->addPassword('password2')
	->setRequired()
	->addRule($form::Equal, 'Passwords must match', $form['password']);
```

Mark every required field with `setRequired()`. Rules added by `addRule()` run only when the control is filled, so an empty field passes them all and its empty value reaches `onSuccess`.

See [the complete validation reference](references/validation.md) for all rules and conditions.

### Conditional Validation

```php
$form->addCheckbox('newsletter', 'Subscribe to newsletter');

$form->addEmail('email')
	->addConditionOn($form['newsletter'], $form::Equal, true)
		->setRequired('Email required for newsletter.');
```

### Form Rendering

**Default rendering:**
```latte
{control productForm}
```

**Manual rendering:**
```latte
{form productForm}
<table>
	<tr>
		<th>{label name /}</th>
		<td>{input name} {inputError name}</td>
	</tr>
	<tr>
		<th>{label email /}</th>
		<td>{input email} {inputError email}</td>
	</tr>
</table>
{input send}
{/form}
```

**With Bootstrap:**
```latte
{form productForm, class => 'form-horizontal'}
<div class="mb-3">
	{label name class => 'form-label' /}
	{input name class => 'form-control'}
	{inputError name class => 'invalid-feedback'}
</div>
{/form}
```

See [the complete rendering reference](references/rendering.md) for all rendering options.

### Form Events

```php
// Before rendering (modify form)
$form->onRender[] = function (Form $form): void {
	// Add CSS classes, modify controls
};

// After successful validation
$form->onSuccess[] = function (Form $form, \stdClass $data): void {
	// Process valid data
};

// After any submission (valid or invalid)
$form->onSubmit[] = function (Form $form): void {
	// Logging, analytics
};

// Cross-field validation. Runs even when rules FAILED, so read values with
// getUntrustedValues() – getValues() here warns and returns unvalidated data.
$form->onValidate[] = function (Form $form): void {
	if ($this->isBlocked($form->getUntrustedValues()->email)) {
		$form->addError('This email is blocked.');
	}
};
```

### Error Handling

```php
private function productFormSucceeded(Form $form, \stdClass $data): void
{
	try {
		$this->facade->save($data);
	} catch (DuplicateEntryException) {
		$form['email']->addError('Email already exists.');
		return;
	}
	$this->redirect('default');
}
```

`redirect()` works by throwing `AbortException` (a `\LogicException`), so keep it outside the `try`: a catch-all `\Exception` there would swallow the redirect.

### Online Documentation

For details, see the official documentation:

- [Forms](https://doc.nette.org/en/forms) – complete forms guide
- [Controls](https://doc.nette.org/en/forms/controls) – all form controls
- [Validation](https://doc.nette.org/en/forms/validation) – validation rules and conditions
- [Rendering](https://doc.nette.org/en/forms/rendering) – rendering and Bootstrap integration

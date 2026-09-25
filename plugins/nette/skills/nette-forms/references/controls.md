# Nette Forms Controls Reference

## Text Inputs

### addText(string $name, $label = null): TextInput

Single-line text input.

```php
$form->addText('username', 'Username:')
	->setRequired()
	->setMaxLength(50)
	->setHtmlAttribute('placeholder', 'Enter username');
```

### addPassword(string $name, $label = null): TextInput

Password input (value not sent back to browser).

```php
$form->addPassword('password', 'Password:')
	->setRequired()
	->addRule($form::MinLength, 'Minimum %d characters', 8);
```

### addEmail(string $name, $label = null): TextInput

Email input with built-in validation.

```php
$form->addEmail('email', 'Email:')
	->setRequired();
```

### addTextArea(string $name, $label = null): TextArea

Multi-line text input.

```php
$form->addTextArea('description', 'Description:')
	->setHtmlAttribute('rows', 10)
	->setHtmlAttribute('cols', 50);
```

### addInteger(string $name, $label = null): TextInput

Integer input (returns int or null).

```php
$form->addInteger('quantity', 'Quantity:')
	->setRequired()
	->addRule($form::Range, 'Must be between %d and %d', [1, 100]);
```

### addFloat(string $name, $label = null): TextInput

Decimal number input.

```php
$form->addFloat('price', 'Price:')
	->setRequired()
	->addRule($form::Min, 'Minimum price is %d', 0);
```

## Date and Time

### addDate(string $name, $label = null): DateTimeControl

Date picker (returns DateTimeImmutable).

```php
$form->addDate('birthdate', 'Birth Date:')
	->setRequired();
```

### addTime(string $name, $label = null, bool $withSeconds = false): DateTimeControl

Time picker.

```php
$form->addTime('meeting_time', 'Meeting Time:');
```

### addDateTime(string $name, $label = null, bool $withSeconds = false): DateTimeControl

Combined date and time picker.

```php
$form->addDateTime('event_start', 'Event Start:');
```

## Selection Controls

### addSelect(string $name, $label = null, ?array $items = null, ?int $size = null): SelectBox

Dropdown select.

```php
$form->addSelect('country', 'Country:', [
	'cz' => 'Czech Republic',
	'sk' => 'Slovakia',
	'pl' => 'Poland',
])
	->setPrompt('Select country...')
	->setRequired();
```

With option groups:

```php
$form->addSelect('category', 'Category:', [
	'Fruits' => [
		'apple' => 'Apple',
		'banana' => 'Banana',
	],
	'Vegetables' => [
		'carrot' => 'Carrot',
		'tomato' => 'Tomato',
	],
]);
```

### addMultiSelect(string $name, $label = null, ?array $items = null, ?int $size = null): MultiSelectBox

Multiple selection (Ctrl+click).

```php
$form->addMultiSelect('tags', 'Tags:', $tagOptions)
	->setRequired('Select at least one tag.')
	->addRule($form::MaxLength, 'Maximum %d tags', 5);
```

### addRadioList(string $name, $label = null, ?array $items = null): RadioList

Radio button group.

```php
$form->addRadioList('gender', 'Gender:', [
	'm' => 'Male',
	'f' => 'Female',
	'o' => 'Other',
])
	->setRequired();
```

### addCheckboxList(string $name, $label = null, ?array $items = null): CheckboxList

Multiple checkboxes.

```php
$form->addCheckboxList('interests', 'Interests:', [
	'sport' => 'Sport',
	'music' => 'Music',
	'travel' => 'Travel',
]);
```

## Checkbox

### addCheckbox(string $name, $caption = null): Checkbox

Single checkbox (returns bool).

```php
$form->addCheckbox('agree', 'I agree to terms')
	->setRequired('You must agree to terms.');
```

## File Upload

### addUpload(string $name, $label = null): UploadControl

Single file upload.

```php
$form->addUpload('photo', 'Photo:')
	->addRule($form::Image, 'Must be JPEG, PNG, GIF, or WebP.')
	->addRule($form::MaxFileSize, 'Maximum size is %d bytes.', 2 * 1024 * 1024);
```

### addMultiUpload(string $name, $label = null): UploadControl

Multiple file upload.

```php
$form->addMultiUpload('documents', 'Documents:')
	->addRule($form::MaxLength, 'Maximum %d files', 10)
	->addRule($form::MimeType, 'Only PDF files.', 'application/pdf');
```

Processing uploaded files:

```php
private function formSucceeded(Form $form, \stdClass $data): void
{
	/** @var Nette\Http\FileUpload $file */
	$file = $data->photo;

	if ($file->isOk()) {
		$file->move('/path/to/uploads/' . $file->getSanitizedName());
	}
}
```

## Hidden and Special

### addHidden(string $name, $default = null): HiddenField

Hidden input.

```php
$form->addHidden('id', $id);
$form->addHidden('returnUrl', $this->getParameter('returnUrl'));
```

Note: CSRF protection is automatic in Nette Forms — no need to add a hidden token manually.
Since nette/http 3.4 it is based on the `Sec-Fetch-*` headers rather than a token, so
`addProtection()` is deprecated ("default protection is sufficient"). A legitimate
cross-origin POST is allowed with `$form->allowCrossOrigin()`; there is no `setCrossOrigin()`.
See the **nette-http** skill for the mechanism and its Safari fallback.

### addContainer(string $name): Container

Group related controls.

```php
$address = $form->addContainer('address');
$address->addText('street', 'Street:');
$address->addText('city', 'City:');
$address->addText('zip', 'ZIP:');

// Access: $data->address->street, $data->address->city
```

## Buttons

### addSubmit(string $name, $caption = null): SubmitButton

Submit button.

```php
$form->addSubmit('send', 'Save');

// Multiple submit buttons
$form->addSubmit('save', 'Save');
$form->addSubmit('saveAndNew', 'Save and create new');
```

Detecting which button was clicked:

```php
private function formSucceeded(Form $form, \stdClass $data): void
{
	if ($form['saveAndNew']->isSubmittedBy()) {
		$this->redirect('add');
	}
	$this->redirect('default');
}
```

### addButton(string $name, $caption): Button

Regular button (no submit, handled via JavaScript event listeners).

```php
$form->addButton('preview', 'Preview')
	->setHtmlAttribute('data-action', 'preview');
```

### addImageButton(string $name, ?string $src = null, ?string $alt = null): ImageButton

Image submit button.

```php
$form->addImageButton('submit', '/images/submit.png', 'Submit');
```

## Control Methods

### Common Methods

```php
$control = $form->addText('name', 'Name:');

// Validation
$control->setRequired('This field is required.');
$control->addRule($form::MinLength, 'Min %d chars', 3);
$control->addCondition(...)->addRule(...);

// Default value
$control->setDefaultValue('John');

// HTML attributes
$control->setHtmlAttribute('class', 'form-control');
$control->setHtmlAttribute('placeholder', 'Enter name...');
$control->setHtmlAttribute('autofocus');

// Disable/readonly
$control->setDisabled();
$control->setHtmlAttribute('readonly');

// Options
$control->setOption('description', 'Additional info');
```

### Getting/Setting Values

```php
// Get submitted value
$value = $form['name']->getValue();

// Set value programmatically
$form['name']->setValue('John Doe');

// Set default (only if not submitted)
$form['name']->setDefaultValue('John Doe');

// Set defaults for entire form
$form->setDefaults([
	'name' => 'John Doe',
	'email' => 'john@example.com',
]);
```

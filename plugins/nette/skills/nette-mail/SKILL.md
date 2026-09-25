---
name: nette-mail
description: Invoke before writing or reviewing code that sends email with nette/mail. Covers Nette\Mail\Message (setFrom, addTo/addCc/addBcc, setSubject, setBody, setHtmlBody with automatic image embedding, addEmbeddedFile, addAttachment, setUnsubscribe), sending Latte templates as email, and the mailers SmtpMailer, SendmailMailer, FallbackMailer and FileMailer. Also trigger for the mail section in NEON, SMTP configuration, OAuth 2.0 / XOAUTH2 for Gmail or Microsoft 365, DKIM signing, CssInliner, inline cid images, redirecting mail in development, or Mailpit/MailHog. Not for reading mailboxes (IMAP/POP3), which nette/mail does not do.
---

## Nette Mail

```shell
composer require nette/mail
```

### Message

```php
$mail = (new Nette\Mail\Message)
	->setFrom('John <john@example.com>')   // or setFrom($email, $name)
	->addTo('peter@example.com')           // addCc(), addBcc(), addReplyTo() likewise
	->setSubject('Order Confirmation')
	->setBody("plain text");               // setHtmlBody() for HTML
```

Everything must be UTF-8. Priority constants are `Message::High` / `Normal` / `Low`
(1/3/5). Internationalized domains (`jan@příklad.cz`) are punycoded automatically, but
**only when `intl` is loaded** – without it the UTF-8 domain goes out as-is. (4.2.0)

### Embedding Images

A second argument to `setHtmlBody()` makes it scan the HTML and embed what it finds:

```php
$mail->setHtmlBody('<b>Hello</b> <img src="background.gif">', '/path/to/images');
```

It rewrites `<img src>`, `<body background>`, `url(...)` in `style` attributes and
`<style>` blocks, and the `[[file.png]]` placeholder. It **skips anything absolute** – a
value starting with a scheme, a `/` or a `#` is left alone, which is the usual reason
"automatic embedding didn't work". It also fills the subject from `<title>` when none is
set and generates the plain-text alternative. Doing it by hand:

```php
$part = $mail->addEmbeddedFile('/path/to/logo.png');   // returns a MimePart
$cid = trim($part->getHeader('Content-ID'), '<>');
$mail->setHtmlBody('<img src="cid:' . $cid . '">');
```

**`MimePart` has no `getContentId()`.** The id lives in the `Content-ID` header, stored
wrapped in angle brackets (`<random@host>`) that must be stripped for a `cid:` URI. Never
concatenate the returned object into markup – it is a `MimePart`, not a string.
`addAttachment($file, $content = null, $contentType = null)` and `addEmbeddedFile()` share
a trap: when `$content` is given, `$file` is only the display filename, nothing is read.

### One-Click Unsubscribe (4.2.0)

```php
$mail->setUnsubscribe('https://example.com/unsubscribe?token=xyz');  // or setUnsubscribe(email: '...')
```

Sets `List-Unsubscribe` plus `List-Unsubscribe-Post` (RFC 8058), which Gmail and Yahoo
require from bulk senders. The `-Post` header is emitted **only** with a URL, because
one-click needs an endpoint that unsubscribes on a bare HTTP POST with no confirmation.

### Latte Templates and CSS

No template integration lives in `Message`; render first, pass the string. For `n:href` /
`{link}` in a mail template, feed `Nette\Application\LinkGenerator` into Latte as the
`uiControl` provider – links then come out absolute.

```php
$html = (new Nette\Mail\CssInliner)->addCss($css)->inline($html);  // inline BEFORE setHtmlBody
$mail->setHtmlBody($html, '/path/to/images');
```

`CssInliner` requires **PHP 8.4+** (`Dom\HTMLDocument`) and the `dom` extension. Since
4.2.0 conflicts are resolved by the real CSS cascade: `!important`, then an existing
inline `style` attribute, then specificity, then source order, with `<style>` rules
processed before `addCss()` ones. It also emits Outlook attributes (`bgcolor`, `width`,
`height`, `cellspacing`), and leaves `@media` / `:hover` in the preserved `<style>` tag.

### Mailers

```php
$mailer = new Nette\Mail\SmtpMailer(
	host: 'smtp.gmail.com', username: 'john@gmail.com', password: '*****',
	encryption: 'ssl',     // or 'tls'; port defaults to 465 / 587 / 25 respectively
);
```

Use named arguments – the positional order is
`(host, username, password, port, encryption, persistent, timeout, clientHost, streamOptions)`,
which matches neither the NEON key order nor intuition.

`host`, `username` and `password` are **required, without defaults**, so an unauthenticated
local catcher still needs them spelled out:
`new SmtpMailer(host: '127.0.0.1', username: '', password: '', port: 1025)` – omitting the
two empty ones is an `ArgumentCountError`.

**OAuth 2.0 / XOAUTH2** (4.2.0), now required by Gmail and Microsoft 365: keep the
username, leave the password empty, and call
`$mailer->setAccessToken(fn() => $oauth->getFreshToken())`. A `Closure` is re-resolved on
every connection, so an expiring token can refresh itself.

`FallbackMailer([$a, $b], retryCount: 3, retryWaitTime: 1000)` cycles through mailers and
throws `FallbackMailerException` (with a `$failures` array) if everything fails; since
4.2.0 a **permanent** failure such as rejected credentials drops that mailer from later
attempts. `FileMailer('/path/to/mails')` (4.2.0) writes each message as a timestamped
`.eml` instead of sending it – the right choice for tests.

### DKIM

```php
$mailer->setSigner(new Nette\Mail\DkimSigner(
	domain: 'example.com',
	selector: 'dkim',
	privateKey: file_get_contents('/path/to/dkim.key'),   // the key CONTENT
	oversignHeaders: ['From'],                            // 4.2.0
));
```

The key type is detected from the key: PEM means RSA (needs `openssl`), raw base64 of 32
or 64 bytes means Ed25519 per RFC 8463 (needs `sodium`). (4.2.0) `oversignHeaders` signs
a header one extra time so a spoofer cannot append a second copy. **The constructor takes
the key contents, but the NEON `privateKey` takes a file path** – DI reads the file.

### Configuration

```neon
mail:
	smtp: true
	host: 127.0.0.1      # with port 1025: a local Mailpit / MailHog catcher
	port: 1025
	encryption: ...      # ssl|tls|null; 'secure' is the deprecated alias
	dkim: {domain: example.com, selector: dkim, privateKey: %appDir%/cert/dkim.key}
	redirect: dev@example.com     # or {to: ..., subjectPrefix: '[debug]'}
```

`redirect` clears `To`, `Cc`, `Bcc`, `Disposition-Notification-To` and `X-Confirm-Reading-To`,
preserves each in an `X-Original-*` header and sets `To` alone to the given address – the safe
way to run staging. Registered services: `mail.mailer`
and, with DKIM, `mail.signer`.

### Online Documentation

For details, see the official documentation:

- [Mail](https://doc.nette.org/en/mail) – Message API, embedding, mailers, CssInliner, DKIM, configuration
- [Mail Upgrading](https://doc.nette.org/en/mail/upgrading) – changes between major versions

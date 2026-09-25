---
name: frontend-development
description: Provides frontend development guidelines for Nette. Use when working with Vite, SCSS, JavaScript/TypeScript, Nette Assets ({asset} tag, asset mapping), ESLint with @nette/eslint-plugin, Naja AJAX library, frontend entry points, npm packages in Nette context, Tailwind CSS with Latte templates, nette-forms npm package, HMR, build commands (npm run dev/build), or passing data from PHP to JavaScript.
---

## Frontend Development

See [the Nette Assets reference](references/assets.md) for asset management.
See [the Vite integration guide](references/vite.md) for build configuration.

### Technology Stack

- **Build system:** Vite with TypeScript support
- **JavaScript:** ES Modules (ESM) with `"type": "module"` in package.json
- **Styling:** SCSS with modular component architecture
- **Asset integration:** Nette Assets for PHP-frontend bridge
- **Admin framework:** Bootstrap for rapid interface development
- **Code quality:** ESLint with @nette/eslint-plugin
- **Forms:** Nette Forms with progressive enhancement

## Asset Architecture Strategy

**Source vs Built Assets separation:**
- **Development assets:** `assets/` directory with source files (SCSS, TypeScript, images)
- **Production assets:** `www/assets/` with optimized, versioned files for browsers

### Coding Standards

- Use single quotes for strings unless containing apostrophes (CSS, SCSS, JavaScript)


### Nette Assets Integration Patterns

**Basic asset loading**
```latte
{* Loads complete bundle with all dependencies *}
{asset 'app.js'}
```

**Configuration**

```neon
assets:
	mapping:
		default:
			type: vite
			path: assets       # REQUIRED in practice - must match Vite's build.outDir
```

`path` is optional in the schema and nothing validates it, but when omitted it falls back to
`%wwwDir%`, so the manifest is looked up in `www/.vite/manifest.json` instead of
`www/assets/.vite/manifest.json` and you get `RuntimeException: Failed to read Vite manifest`.
`devServer` defaults to `true`, so there is no need to write it.

### Data Flow from Backend to Frontend

**Passing data to JavaScript:**
```latte
{* In template *}
<script>
window.appConfig = {
	apiUrl: {$baseUrl . '/api'},
	userId: {$user->isLoggedIn() ? $user->getId() : null},
	locale: {$locale},
	csrfToken: {$csrfToken}
};
</script>
{asset 'app.js'}
```

Latte automatically applies context-sensitive escaping – values inside `<script>` are JSON-encoded, so strings get quoted and `null` stays `null`.

```javascript
// In JavaScript component
const { apiUrl, userId, csrfToken } = window.appConfig;

fetch(`${apiUrl}/user-data`, {
	headers: { 'X-CSRF-Token': csrfToken }
});
```

### Naja (AJAX Library)

Naja is the standard AJAX library for Nette – it handles snippet redrawing, form submissions, and history integration:

```shell
npm install naja
```

```javascript
import naja from 'naja';

// Initialize after DOM is ready
naja.initialize();
```

Naja automatically intercepts links and forms with the `ajax` CSS class and handles snippet updates from the server. See [Naja documentation](https://naja.js.org/) for configuration and extensions.

### Nette Forms Integration

Requires `nette-forms` npm package:

```shell
npm install nette-forms
```

**Standard enhancement pattern:**
```javascript
import netteForms from 'nette-forms';

// Initialize Nette Forms validation
netteForms.initOnLoad();
```

### ESLint Configuration

```shell
npm install --save-dev @nette/eslint-plugin eslint
```

**Basic configuration with recommended rules:**
```javascript
// eslint.config.js
import nette from '@nette/eslint-plugin';
import { defineConfig } from 'eslint/config';

export default defineConfig([
	{
		extends: [nette.configs.recommended],
	},
]);
```

**Linting JavaScript in Latte templates:**

```shell
npm install --save-dev eslint-plugin-html
```

```javascript
// eslint.config.js
import nette from '@nette/eslint-plugin';
import pluginHtml from 'eslint-plugin-html';
import { defineConfig } from 'eslint/config';

export default defineConfig([
	{
		extends: [nette.configs.recommended],
	},
	{
		files: ['app/**/*.latte'],
		plugins: {
			html: pluginHtml,
		},
		processor: '@nette/latte',  // Handles Latte tags in JS
	},
]);
```

This allows ESLint to check JavaScript inside `<script>` tags with Latte variables:

```latte
<script>
let name = {$name};
</script>
```

**TypeScript support:**

```shell
npm install --save-dev typescript typescript-eslint
```

```javascript
import nette from '@nette/eslint-plugin/typescript';

export default defineConfig([
	{
		extends: [nette.configs.typescript],
	},
]);
```

**Custom rules:**
- `@nette/no-this-in-arrow-except` - Prevents `this` binding issues in arrow functions
- `@nette/prefer-line-comments` - Enforces `//` over `/* */` for single-line comments

### Tailwind CSS Integration

When using Tailwind CSS, configure it to scan Latte templates for class names:

```css
/* assets/css/app.css */
@import 'tailwindcss';
@source '../app/**/*.latte';
```

### Online Documentation

For details, see the official documentation:

- [Assets](https://doc.nette.org/en/assets) – Nette Assets documentation
- [Naja](https://naja.js.org/) – AJAX library for Nette

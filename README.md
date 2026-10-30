# Dynamic Notice Toasts for WooCommerce

Customizable storefront notices by Thanos Leftheriotis. Version 1.2.0 requires WordPress 6.5+, PHP 7.4+, and WooCommerce. The release package is named `wc-dynamic-notice-toasts`. The display name remains Dynamic Notice Toasts for WooCommerce and the main plugin file remains `woocommerce-toasts.php`. The WordPress.org submission slug and translation domain are `dynamic-notice-toasts-for-woocommerce`; the plugin header, translation calls, POT filename, WPCS rules and Plugin Check configuration must match that slug. Existing `wc_toasts_settings` options and PHP/JavaScript API names remain unchanged.

## Configure your notices

Open **WooCommerce > Notice Toasts > Appearance**. Choose Margin Note, System Capsule, Retro Outline, or Status Canvas, then choose a palette. All four layouts are available on the storefront and in the previews.

| Palette | Background | Text | Accent |
| --- | --- | --- | --- |
| Sky Blue & Dark Aubergine | `#D7EBFF` | `#3B2350` | `#FAFAF7` |
| Lime Chartreuse & Charcoal | `#D9F752` | `#2B2B2B` | `#F4F1E6` |
| Default Light | `#F8F7F2` | `#1F2328` | `#D8DDE6` |
| Default Dark | `#1E222A` | `#F4F1E8` | `#4B7BFF` |

The color editor provides 24 optional overrides next to a live toast preview. Blank fields inherit palette or automatic values. Reset clears the overrides; **Save All Settings** applies them to the store. The standalone `dev-preview.html` uses the same palette definitions and styling engine, and can download a style configuration as JSON. It is a repository-only development tool and is not included in the release package.

Position, duration, progress, hover pause, close buttons, mobile docking, deduplication, and notice categories remain configurable. Keyboard focus pauses dismissal while someone uses a notice. Suppression of WooCommerce's original notices is optional.

The plugin handles standard WooCommerce notice markup and classic cart/checkout events. WooCommerce Blocks and third-party checkout interfaces may expose notices differently. Test those flows before enabling suppression on a live store. HPOS compatibility is declared; the plugin does not read or write order tables. Broad Blocks compatibility is not claimed.

## Color token contract

Every variable begins with `--wc-toast-`. Saved color overrides accept three- or six-digit hex values. Unknown keys and CSS expressions are rejected. The base palette has `surface`, `ink`, and an optional `accent`; two-color palettes derive their accent from 12% ink and 88% surface.

| Tokens | Role |
| --- | --- |
| `surface`, `ink`, `accent` | Background, text, and accent fill |
| `on-accent`, `muted` | Text over accents and secondary text |
| `border`, `icon`, `focus` | Borders, default icons, and keyboard focus rings |
| `control-fill`, `shadow-color` | Shared highlight fill and shadow color |
| `action-bg`, `action-ink`, `action-hover-bg`, `action-hover-ink` | Action button colors |
| `close-bg`, `close-ink`, `close-hover-bg`, `close-hover-ink` | Dismiss control colors |
| `icon-bg`, `icon-ink` | Status icon tile colors |
| `success`, `error`, `info`, `warning` | Notice status/progress colors |

The engine chooses the higher-contrast surface or ink color for text over an explicit accent, unless `on-accent` is overridden. This is a contrast heuristic, not a guarantee that arbitrary custom colors meet WCAG. Verify the combinations used on your store. Retro Outline's hover and focus highlights share the permanent symbol fill unless a more specific control token is set.

Preset data lives in `assets/config/styling.json`. After editing it, run `npm run build:data` to regenerate the standalone browser data. WordPress reads the JSON directly. No source build tools are needed on the production site.

## Extend through PHP

Register extensions from a separate plugin, an mu-plugin, or a child theme. Do not edit this plugin's files. Keep palette values as hex colors, and use lowercase identifier keys.

```php
add_filter( 'wc_toasts_palettes', function ( $palettes ) {
    $palettes['brand'] = array(
        'label'   => 'My brand',
        'surface' => '#FAFAF7',
        'ink'     => '#3B2350',
        'accent'  => '#D7EBFF',
    );
    return $palettes;
} );

add_filter( 'wc_toasts_color_tokens', function ( $tokens ) {
    $tokens['brand-detail'] = 'Brand detail';
    return $tokens;
} );

add_filter( 'wc_toasts_layouts', function ( $layouts ) {
    $layouts['brand'] = 'Brand layout';
    return $layouts;
} );
```

Registered palettes, layouts, themes, tokens, and category switches survive settings sanitization. A registered custom layout adds `layout-brand` to the toast container; enqueue CSS targeting `.layout-brand .wc-toast` to implement it. The standard notice DOM provides icon, content, action, close, and progress elements. Use the browser render event for additional markup.

| Filter | Arguments | Purpose |
| --- | --- | --- |
| `wc_toasts_palettes` | `$palettes` | Register or override palettes |
| `wc_toasts_color_tokens` | `$tokens` | Register editable, validated color roles |
| `wc_toasts_layouts` | `$layouts` | Add layout choices |
| `wc_toasts_themes` | `$themes` | Extend legacy theme choices |
| `wc_toasts_settings` | `$settings` | Override settings at runtime |
| `wc_toasts_default_settings` | `$settings` | Override factory defaults |
| `wc_toasts_sanitized_settings` | `$settings`, `$input` | Add trusted extension settings after validation |
| `wc_toasts_frontend_params` | `$params` | Extend client configuration and icon definitions |
| `wc_toasts_categories` | `$categories` | Add or adjust notice categories |
| `wc_toasts_cases` | `$cases` | Extend classification patterns |
| `wc_toasts_classified_notice` | `$notice`, `$raw_notice`, `$type` | Adjust classified payloads |

```php
add_filter( 'wc_toasts_classified_notice', function ( $notice ) {
    if ( 'cart_item_added' === $notice['case_id'] ) {
        $notice['title'] = 'Ready when you are';
    }
    return $notice;
} );
```

For custom CSS, use the Customizer's Additional CSS or enqueue your own stylesheet after `wc-toasts-frontend`. The plugin uses WordPress' `manage_woocommerce` capability and nonce verification for writes. Extensions run as trusted PHP; they remain responsible for sanitizing their own settings and custom markup.

## Extend in the browser

```js
const record = window.WCToasts.show({
  type: 'success',
  title: 'Saved',
  message: 'Your changes are ready.',
  action: { text: 'View cart', url: '/cart/' }
});
// show() returns { id, element, timerId }, or null when rejected.
if (record) window.WCToasts.dismiss(record.id);

document.addEventListener('wc-toasts:before-show', event => {
  if (event.detail.notice.category === 'custom-hidden') event.preventDefault();
});
document.addEventListener('wc-toasts:render', event => {
  event.detail.element.dataset.brand = 'my-store';
});
document.addEventListener('wc-toasts:dismiss', event => {
  console.log('Dismissed', event.detail.id);
});
```

`WCToasts.success()`, `.error()`, `.info()`, `.clearAll()`, and `.pushNotices()` are also available. Enqueue code using this API after `wc-toasts-frontend`. The render event fires after all standard elements are present. Notice HTML and action URLs receive a final client-side safety pass; actions allow HTTP(S) and relative web URLs.

`WCToastStyling.resolve(palette, overrides, tokenNames, layout)` returns CSS token values. `WCToastStyling.apply(element, palette, overrides, tokenNames, layout)` applies them and clears overrides from a previous call, so removed values do not leak between styles. A palette can supply `layoutTokens: { utility: { 'action-bg': '#2B2B2B', 'action-ink': '#FFFFFF' } }` for a layout-specific treatment. Individual user overrides take precedence over these defaults.

## Development and reviews

PHP/Composer are needed for coding checks. Node 22.16+ runs the build and native review helpers. The optional Playground preview requires its upstream-supported Node 24.18+ and npm 11.16+. Install dependencies using the committed lockfiles:

```sh
composer install
npm ci
composer lint
npm run check:js
npm run test:unit
npm run build
```

`phpcs.xml.dist` runs the full WordPress ruleset plus PHPCompatibilityWP, with PHP 7.4 as the minimum. The WooCommerce capability is explicitly registered in the ruleset. Narrow inline exceptions explain bundled local-file reads, read-only navigation, and safe CSS/SVG output.

For a disposable Playground site, run `npm run preview -- --port=9410`. After installing a Playwright Chromium browser (`npx playwright install chromium`), run `npm run test:browser`. Set `WC_TOASTS_TEST_URL` or `WC_TOASTS_CHROMIUM_PATH` to use another test site or browser. The suite changes settings only on the test installation, and overwrites `.wordpress-org/screenshot-1.png` with a fresh admin capture. Start the preview from PowerShell or cmd on Windows; Git Bash rewrites the `--mount` path and the server exits. The first request after boot can exceed the suite's 30-second wait, so re-run once if the editor preview times out.

On Windows, native Plugin Check avoids WebAssembly process limitations:

```sh
npm run review:setup
npm run build
npm run review:native
```

The helper downloads an isolated portable PHP/WordPress/WooCommerce installation into ignored `.runtime/`, using SQLite. It does not modify system PHP or existing sites. The runtime assertions cover activation, token and setting validation, extension registration, safe HTML, and uninstall behavior. Reports are written to `test-results/`. Native review also regenerates the POT translation template; rebuild afterwards to include it. CI runs standards, JavaScript syntax, a release build, and the official WordPress Plugin Check action against the release directory.

## Release and WordPress.org submission

`npm run build` writes `dist/wc-dynamic-notice-toasts.zip` and stages the runtime files in `dist/wc-dynamic-notice-toasts/`, with a SHA-256 checksum in `dist/SHA256SUMS.txt`. Every ZIP must be flat: `assets/`, `includes/`, `languages/`, `templates/`, `woocommerce-toasts.php`, and the other runtime files sit directly at the archive root. Zip the directory contents, never an enclosing plugin/project folder. This project rule is also recorded in `AGENTS.md` and enforced by `npm run verify:release`.

The archive includes runtime PHP, templates, assets, translations, readme, and licenses. The design lab, development dependencies, test sites, tests, reports, archives, and Git files are excluded. The verifier also rejects inline `<script>`/`<style>` tags and arbitrary CSS settings in packaged PHP, plus any standalone HTML page, because the WordPress.org review requires enqueued assets and no code-insertion fields. GPLv2-or-later covers the plugin; bundled Iconoir icons use MIT and TikTok Sans uses SIL OFL 1.1.

Use `RELEASE-REVIEW.txt` for the review evidence and remaining submission steps. Confirm that `thanos` is your actual WordPress.org account username before uploading; the public author name is Thanos Leftheriotis. The proposed slug is subject to WordPress.org approval. Upload the ZIP through the official [Add Your Plugin](https://wordpress.org/plugins/developers/add/) form. Automated checks do not replace the directory team's manual review. No submission or publishing is automated by this repository.

After approval, copy the release directory contents to SVN `trunk/`, copy that release to `tags/1.2.0/`, and keep the PHP version, readme stable tag, and SVN tag synchronized. Directory screenshots and artwork belong in SVN `assets/`, outside the plugin's runtime directory. Review the current [plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) before submission.

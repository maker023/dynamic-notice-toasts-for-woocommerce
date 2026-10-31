# Dynamic Notice Toasts for WooCommerce

Customizable storefront notices by Thanos Leftheriotis. Version 1.3.0 requires WordPress 6.5+, PHP 7.4+, and WooCommerce. The release package is named `wc-dynamic-notice-toasts`. The display name remains Dynamic Notice Toasts for WooCommerce and the main plugin file remains `woocommerce-toasts.php`. The WordPress.org submission slug and translation domain are `dynamic-notice-toasts-for-woocommerce`; the plugin header, translation calls, POT filename, WPCS rules and Plugin Check configuration must match that slug. Existing `wc_toasts_settings` options and PHP/JavaScript API names remain unchanged.

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

The plugin handles classic and block-based stores. On classic themes it reads WooCommerce notice markup and the jQuery cart, coupon, and checkout events. On block themes and in the Cart and Checkout blocks it reads the `wc-block-components-notice-banner` markup that the Store Notices block, the blocks, and WooCommerce's block-theme notice templates share, and turns the `wc-blocks_added_to_cart` event into an added-to-cart toast. Mini-Cart re-dispatches the classic `added_to_cart` event as `wc-blocks_added_to_cart` without `detail`; those re-dispatches are ignored so each add produces one toast. Form field errors in the Cart and Checkout blocks (`wc-block-components-validation-error`) are not converted, because they belong next to the field that needs fixing. Third-party checkout interfaces may expose notices differently, so test them before enabling suppression on a live store. HPOS compatibility is declared; the plugin does not read or write order tables.

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

`npm run test:flows` is the end-to-end check for storefront notices. It builds the package, boots Playground from `.wordpress-org/blueprints/blueprint.json` with `dist/wc-dynamic-notice-toasts/` mounted in place of the WordPress.org download, and runs two stores in Chromium. The classic store (Storefront, shortcode Cart and Checkout) covers AJAX add to cart, a sold-individually rejection, invalid and valid coupons, and an empty checkout. The block store (default block theme, Product Collection, Cart and Checkout blocks) covers block add to cart, the Store Notices error, a coupon snackbar, hidden banners, and inline checkout validation. The test records every `wc-toasts:render` event and fails on missing, duplicated or wrongly typed toasts, or on generic checkout failures that hide validation messages. Run it before every release; CI runs it on every push. It needs network access to install WooCommerce and Storefront, and uses port 9420 unless `WC_TOASTS_FLOWS_PORT` is set. `tools/playground.mjs` holds the shared Playground and browser helpers.

`npm run build:directory-assets` regenerates the directory banners and icons in `.wordpress-org/`. The banners show a real toast rendered by the built plugin in the demo store, so rerun it after visual changes to the default layout or palette. It uses port 9421 unless `WC_TOASTS_ASSETS_PORT` is set.

On Windows, native Plugin Check avoids WebAssembly process limitations:

```sh
npm run review:setup
npm run build
npm run review:native
```

The helper downloads an isolated portable PHP/WordPress/WooCommerce installation into ignored `.runtime/`, using SQLite. It does not modify system PHP or existing sites. The runtime assertions cover activation, token and setting validation, extension registration, safe HTML, and uninstall behavior. Reports are written to `test-results/`. Native review also regenerates the POT translation template; rebuild afterwards to include it. CI runs standards, JavaScript syntax, a release build, and the official WordPress Plugin Check action against the release directory.

## Release packaging

`npm run build` writes `dist/wc-dynamic-notice-toasts.zip` and stages the runtime files in `dist/wc-dynamic-notice-toasts/`, with a SHA-256 checksum in `dist/SHA256SUMS.txt`. Every ZIP must be flat: `assets/`, `includes/`, `languages/`, `templates/`, `woocommerce-toasts.php`, and the other runtime files sit directly at the archive root. Zip the directory contents, never an enclosing plugin/project folder. This project rule is also recorded in `AGENTS.md` and enforced by `npm run verify:release`.

The archive includes runtime PHP, templates, assets, translations, readme, and licenses. The design lab, development dependencies, test sites, tests, reports, archives, and Git files are excluded. The verifier also rejects inline `<script>`/`<style>` tags and arbitrary CSS settings in packaged PHP, plus any standalone HTML page, because the WordPress.org review requires enqueued assets and no code-insertion fields. GPLv2-or-later covers the plugin; bundled Iconoir icons use MIT and TikTok Sans uses SIL OFL 1.1.

`RELEASE-REVIEW.txt` keeps the review evidence and release history.

## Publishing to WordPress.org

The plugin is listed at [wordpress.org/plugins/dynamic-notice-toasts-for-woocommerce](https://wordpress.org/plugins/dynamic-notice-toasts-for-woocommerce/). Releases go through SVN at `https://plugins.svn.wordpress.org/dynamic-notice-toasts-for-woocommerce` as the committer `thanosleftheriotis`. The SVN password is separate from the WordPress.org login. Generate it under [Account & Security](https://profiles.wordpress.org/me/profile/edit/group/3/?screen=svn-password), where it is shown once. Committer pages such as Test Preview and the Advanced view's Danger Zone only appear when logged in with two-factor authentication.

`tools/deploy-svn.mjs` publishes the verified build. It needs a Subversion client. On Windows, `winget install Slik.Subversion` installs one to the default path the script checks; set `SVN_BIN` to use another. It keeps a sparse working copy in ignored `.svn-deploy/`, mirrors `dist/wc-dynamic-notice-toasts/` into `trunk/` and `.wordpress-org/` into `assets/`, sets image mime types, and copies `trunk/` to `tags/<version>/`. Every run is a dry run unless `--commit` is passed.

To release a new version:

1. Bump `Version` and `WC_TOASTS_VERSION` in `woocommerce-toasts.php`, `Stable tag` in `readme.txt`, the POT header, and the expected version in `tools/verify-release.mjs`. Add Changelog and Upgrade Notice entries.
2. Run `composer lint`, `npm run check:js`, `npm run test:unit`, `npm run test:flows`, `npm run build` and `npm run verify:release`.
3. Commit and tag `v<version>` in Git.
4. Run `node tools/deploy-svn.mjs` and review the dry-run status.
5. Set `SVN_PASSWORD` for the user, run `node tools/deploy-svn.mjs --commit`, then delete the variable. The password is passed through `--password-from-stdin` with `--no-auth-cache`, so it is never stored by Subversion or shown in process arguments.

Run the script with `node` rather than `npm run deploy:svn -- --commit` in PowerShell, because PowerShell drops the `--` separator for npm and the run silently stays a dry run. The script refuses to reuse an existing SVN tag. For banners, icons, screenshots or the blueprint alone, use `--assets-only`, which updates `assets/` without rebuilding, touching `trunk/` or tagging.

WordPress.org holds each new version for about six hours of moderation and security scanning before sites receive it. Test the Live Preview and announce a release only after the plugin page shows the new version. Ask `plugins@wordpress.org` for an earlier release only for security fixes.

The Live Preview blueprint in `.wordpress-org/blueprints/` installs Storefront, WooCommerce and the plugin from WordPress.org, uses the classic Cart and Checkout shortcodes, enables Cash on Delivery, and creates demo products, including a sold-individually item and the coupon `toast10`. The classic theme keeps the demo's checkout errors visible as toasts; the Cart and Checkout blocks show field errors inline instead. Plugin Preview must be made public under Danger Zone > Toggle Live Preview; until then only committers see a Test Preview button. The directory hides tags that no other plugin uses, so choose readme tags that at least one other plugin shares. Review the current [plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) before each release.

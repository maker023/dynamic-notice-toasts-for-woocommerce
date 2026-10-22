=== WooCommerce Dynamic Notice Toasts ===
Contributors: thanos
Tags: woocommerce, toasts, notices, notifications, cart, checkout, alert
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 9.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modernize WooCommerce notifications. Intercepts and replaces default alert banners with dynamic toasts, audio chimes, and action buttons.

== Description ==

Tired of WooCommerce's clunky, static alert banners that jump your page layout and push content down?

**WooCommerce Dynamic Notice Toasts** intercepts all default WooCommerce notices across your entire store and replaces them with sleek, modern, interactive toast notifications with smooth animations, synthesized Web Audio chimes, countdown progress bars, and dedicated action buttons (e.g., "View Cart", "Undo").

### 🚀 Key Features

* **Complete Notice Hijacking:** Automatically suppresses default static WooCommerce banners (`.woocommerce-message`, `.woocommerce-error`, `.woocommerce-info`) to eliminate layout shifts.
* **Full WooCommerce Event Coverage:** Intercepts server-side page reloads, AJAX add-to-cart clicks, cart updates, coupon actions, and checkout errors.
* **Intelligent Notice Catalog & Classification:** Identifies and classifies every possible WooCommerce notification across 6 categories:
  * **Cart & Basket:** Item additions, removals with "Undo" button, quantity limits, empty cart.
  * **Coupons & Discounts:** Coupon applied, expired coupons, spend requirements, restrictions.
  * **Checkout & Validation:** Missing required fields, invalid postcode/email, terms acceptance, gateway errors.
  * **Stock & Inventory:** Out-of-stock items, purchase limits, backorders.
  * **Customer & Account:** Login failures, password resets, account updates.
  * **System & Store Notices:** General announcements, digital download limits.
* **Action Button Parser:** Extracts inline links from notices (such as "View Cart" or "Undo") and transforms them into clean, high-converting action buttons.
* **Zero External Audio Assets:** Built-in Web Audio API synthesizer generates crystal-clear notification chimes directly in the browser without loading audio files.
* **Live Interactive Admin Customizer:** Test any toast notification and hear audio chimes directly inside the WordPress dashboard simulator with real-time feedback.
* **6 Design Presets:** Modern Light, Frosted Glass (Glassmorphism), Dark Slate, Minimalist Clean, WooCommerce Purple, and Custom Colors.
* **Granular Display Settings:** Customize positions (6 screen locations), pause on hover, auto-dismiss durations per type, deduplication spam protection, and max visible toasts.
* **Mobile Docking:** Automatically docks toasts at the bottom of the screen on mobile devices for easy one-thumb dismissal.
* **Developer Extensibility & HPOS Ready:** 100% compatible with WooCommerce High-Performance Order Storage (HPOS) and WordPress Coding Standards (WPCS).

== Installation ==

1. Upload the `woocommerceToasts` folder to the `/wp-content/plugins/` directory, or install the ZIP file via WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **WooCommerce > Notice Toasts** to configure positions, themes, and sound effects.

== Frequently Asked Questions ==

= Does this plugin work with AJAX Add-to-Cart? =
Yes! It intercepts WooCommerce's native `added_to_cart` jQuery event and extracts product names directly from the button or markup, rendering a modern toast immediately.

= Does it handle checkout validation errors? =
Yes. When checkout validation fails or a payment gateway throws an error, each error is parsed and presented as a high-visibility toast alert.

= Do I need external audio files for notification sounds? =
No. The audio engine uses the HTML5 Web Audio API to synthesize smooth sine/triangle harmonic chimes in real-time. This guarantees zero broken audio links, zero asset load times, and instant playback.

= Can I customize the colors to match my brand? =
Yes. Set the Design Theme to "Custom Color Palette" in **WooCommerce > Notice Toasts > Appearance** to choose custom background, accent, and text colors.

= Is High-Performance Order Storage (HPOS) supported? =
Yes, WooCommerce Dynamic Notice Toasts is fully tested and declares full compatibility with HPOS and WooCommerce Blocks.

== Screenshots ==

1. Admin Settings Dashboard with real-time interactive preview simulator.
2. Dynamic "Added to Cart" toast with quick "View Cart" action button.
3. Frosted Glassmorphism theme toast on product archive page.
4. Checkout validation error toasts with distinct error styling.
5. Notification Catalog displaying all 6 categorized notice domains.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Complete WooCommerce core notification catalog (Cart, Coupon, Checkout, Stock, Account, System).
* Interactive live preview admin panel with Web Audio synthesizer.
* Native AJAX and DOM MutationObserver notice interception.
* HPOS and Cart/Checkout Blocks compatibility.

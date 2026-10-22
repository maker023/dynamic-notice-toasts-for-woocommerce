# WooCommerce Dynamic Notice Toasts 🍞✨

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)](https://woocommerce.com/)
[![HPOS](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://developer.woocommerce.com/)
[![License: GPL-2.0+](https://img.shields.io/badge/License-GPL--2.0%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.txt)

A commercial-grade WordPress / WooCommerce plugin that intercepts and suppresses default WooCommerce notification banners (`.woocommerce-message`, `.woocommerce-error`, `.woocommerce-info`) and replaces them with sleek, dynamic, interactive toast notifications with synthesized audio chimes, countdown progress bars, and smart action buttons.

---

## 📑 Table of Contents

- [Core Features](#-core-features)
- [Architecture & Notice Interception](#-architecture--notice-interception)
- [Comprehensive WooCommerce Notice Catalog](#-comprehensive-woocommerce-notice-catalog)
- [Web Audio Synthesizer (Zero Assets)](#-web-audio-synthesizer-zero-assets)
- [Settings & Customization](#-settings--customization)
- [WordPress Coding Standards & Security](#-wordpress-coding-standards--security)
- [Extensibility & Pro Bridge API](#-extensibility--pro-bridge-api)
- [File Structure](#-file-structure)
- [Installation & Testing](#-installation--testing)

---

## 🚀 Core Features

- **Zero Layout Shifts:** Automatically hides static notice wrappers (`.woocommerce-notices-wrapper`) with early scoped styles so pages never jump when notices appear.
- **Full Notice Lifecycle Coverage:**
  - Standard page loads and redirects (`wc_get_notices()`).
  - AJAX add-to-cart clicks (`added_to_cart` jQuery event).
  - Cart updates, quantity edits, and removals with an **Undo** button.
  - Coupon application, expiration, and invalid discount alerts.
  - Checkout validation errors (e.g., missing address, invalid phone/email, gateway declines).
  - 3rd-party plugin notices caught via a non-blocking `MutationObserver`.
- **Intelligent Action Parser:** Isolates `<a href="..." class="button">` links from notices and renders them as distinct, high-converting action buttons (e.g., "View Cart →", "Undo ↺").
- **HTML5 Web Audio Synthesizer:** Built-in sine/triangle synthesizer creates pleasant audio chimes (`gentle_chime`, `modern_ping`, `crisp_pop`, `bell_ding`, `subtle_click`) without loading any external sound files.
- **Interactive Admin Customizer:** Test toasts, adjust themes, tweak corner radiuses, and play sound chimes directly in the admin preview dashboard before saving.
- **6 Modern Visual Themes:**
  1. *Modern Light* (Clean SaaS white with subtle drop shadows)
  2. *Frosted Glass (Glassmorphism)* (Translucent backdrop blur with high-contrast borders)
  3. *Dark Slate* (High-contrast obsidian mode)
  4. *Minimalist Clean* (Flat aesthetic with colored accent line)
  5. *WooCommerce Purple* (Branded store styling)
  6. *Custom Color Palette* (Color pickers for background, accent, and text)
- **Granular Controls:** 6 screen positions, pause-on-hover, deduplication spam prevention, mobile dock-to-bottom, and custom CSS support.
- **HPOS Compatible:** Fully declared and tested for WooCommerce High-Performance Order Storage (HPOS) and Cart/Checkout Blocks.

---

## 🔍 Architecture & Notice Interception

```
                      ┌──────────────────────────────────────────────┐
                      │            WooCommerce Storefront            │
                      └──────────────────────┬───────────────────────┘
                                             │
               ┌─────────────────────────────┼─────────────────────────────┐
               ▼                             ▼                             ▼
  ┌────────────────────────┐   ┌───────────────────────────┐   ┌───────────────────────────┐
  │  Server-Side Notices   │   │     AJAX & DOM Events     │   │   3rd Party Notice DOM    │
  │   wc_get_notices()     │   │   added_to_cart / checkout│   │   .woocommerce-error etc. │
  └────────────┬───────────┘   └─────────────┬─────────────┘   └─────────────┬─────────────┘
               │                             │                               │
               ▼                             ▼                               ▼
  ┌────────────────────────┐   ┌───────────────────────────┐   ┌───────────────────────────┐
  │ WC_Toasts_Interceptor  │   │   jQuery / Fetch Hooks    │   │     MutationObserver      │
  │ • Clears WC Queue      │   │ • Extracts product/action │   │ • Hides unstyled DOM node │
  │ • Injects Suppress CSS │   │ • Serializes payload      │   │ • Converts to Toast       │
  └────────────┬───────────┘   └─────────────┬─────────────┘   └─────────────┬─────────────┘
               │                             │                               │
               └──────────────────────┬──────┴───────────────────────────────┘
                                      │
                                      ▼
                        ┌───────────────────────────┐
                        │    WC_Toasts_Catalog      │
                        │ • Pattern Matching        │
                        │ • Category Classification │
                        │ • Action Link Extraction  │
                        └─────────────┬─────────────┘
                                      │
                                      ▼
                        ┌───────────────────────────┐
                        │   WCToasts Frontend API   │
                        │ • Web Audio Chime         │
                        │ • Animation In / Out      │
                        │ • Progress Bar Timer      │
                        │ • Screen Stacking & Pos   │
                        └───────────────────────────┘
```

---

## 📚 Comprehensive WooCommerce Notice Catalog

The plugin features a categorized catalog that classifies every core notification:

| Category | Typical Cases | Default Icon | Action Button |
|---|---|---|---|
| **Cart & Basket** (`cart`) | • `cart_item_added`: Product added to cart<br>• `cart_item_removed`: Item removed from cart<br>• `cart_item_restored`: Item restored to cart<br>• `cart_updated`: Basket updated<br>• `cart_empty`: Cart is empty<br>• `cart_minimum_order`: Minimum purchase threshold<br>• `cart_quantity_invalid`: Step / Min / Max quantity limits | `cart_check`<br>`trash`<br>`refresh`<br>`cart_empty`<br>`alert_triangle` | **View cart →**<br>**Undo?** |
| **Coupons & Discounts** (`coupon`) | • `coupon_applied`: Coupon activated successfully<br>• `coupon_removed`: Coupon removed<br>• `coupon_not_exist`: Invalid coupon code<br>• `coupon_already_applied`: Duplicate code<br>• `coupon_expired`: Expiration notice<br>• `coupon_usage_limit`: Maximum redemption reached<br>• `coupon_min_spend` / `max_spend`: Threshold requirements<br>• `coupon_excluded_items`: Excluded products or categories | `ticket_check`<br>`ticket_cross`<br>`ticket_alert`<br>`clock`<br>`shield_alert` | None (Informative) |
| **Checkout & Orders** (`checkout`) | • `checkout_field_required`: Missing required fields (Name, Phone, Address)<br>• `checkout_invalid_email`: Invalid email format<br>• `checkout_invalid_postcode`: Invalid ZIP code<br>• `checkout_terms_required`: Terms and conditions checkbox<br>• `checkout_no_shipping_method`: Shipping selection required<br>• `checkout_payment_failed`: Gateway decline or processing error<br>• `checkout_shipping_updated`: Recalculated shipping rates | `edit_alert`<br>`mail_alert`<br>`map_pin`<br>`truck_alert`<br>`card_error`<br>`truck` | Optional retry link |
| **Stock & Inventory** (`stock`) | • `stock_out_of_stock`: Product sold out<br>• `stock_insufficient`: Insufficient stock for requested quantity<br>• `stock_sold_individually`: Limit 1 per order<br>• `stock_not_purchasable`: Unpurchasable product<br>• `stock_backordered`: Backorder fulfillment notice | `archive_alert`<br>`alert_circle`<br>`clock` | None |
| **Customer & Account** (`account`) | • `account_login_failed`: Password or username incorrect<br>• `account_registration_success`: Account created<br>• `account_password_reset_sent`: Recovery email sent<br>• `account_password_reset_success`: Password changed<br>• `account_details_saved`: Profile saved<br>• `account_address_saved`: Billing/Shipping address saved | `lock_alert`<br>`user_check`<br>`mail_check`<br>`lock_check`<br>`map_pin` | **Log in →** |
| **System & Store** (`system`) | • `system_download_limit`: Digital download count exceeded<br>• `system_order_cancelled`: Order cancellation<br>• `system_generic_success` / `error` / `notice`: Catch-all fallback notices | `download_alert`<br>`bell`<br>`info`<br>`check` | Contextual |

---

## 🎵 Web Audio Synthesizer (Zero Assets)

Unlike legacy plugins that enqueue 50KB `.mp3` files that fail to load or get blocked by cross-origin policies, this plugin uses the **HTML5 Web Audio API** to synthesize chimes in real-time:

- **`gentle_chime`**: Harmonic major triad (E5, A5, C6) with exponential gain envelope decay.
- **`modern_ping`**: Crisp triangle wave with pitch sweep.
- **`crisp_pop`**: Frequency-dropping bubble pop (450Hz to 120Hz in 80ms).
- **`bell_ding`**: Dual-harmonic bell overtone chime.
- **`subtle_click`**: 1400Hz haptic micro click (35ms).

Store owners can adjust the volume (5% to 100%) or mute audio altogether.

---

## ⚙️ Settings & Customization

The settings page is located at **WooCommerce > Notice Toasts**:

1. **General:**
   - Enable/Disable Toast Engine.
   - Suppress Default WooCommerce Notice Banners.
   - Screen Position (`top-right`, `top-left`, `top-center`, `bottom-right`, `bottom-left`, `bottom-center`).
   - Visual Themes (`modern-light`, `glassmorphism`, `dark-slate`, `minimal`, `woocommerce-purple`, `custom`).
   - Max Visible Toasts (1 to 10).
   - Newest on Top vs Bottom.
   - Anti-Spam Deduplication (prevents rapid double-clicks from stacking identical toasts).
2. **Appearance:**
   - Border Radius (0px to 28px).
   - Drop Shadow Intensity (`none`, `subtle`, `medium`, `elevated`, `glow`).
   - Toast Max Width (280px to 550px).
   - Entrance Animations (`slide-in`, `fade-in`, `bounce-in`, `zoom-in`, `flip-in`).
   - Exit Animations (`fade-out`, `slide-out`, `zoom-out`).
   - Custom hex color pickers for Success, Error, and Info types.
3. **Behavior & Sound:**
   - Per-type display duration (e.g., 4.5s for success, 7s or 0 for persistent errors).
   - Animated countdown progress bar.
   - Pause timer on mouse hover.
   - Dismiss "X" button toggle.
   - Sound theme selection and volume slider.
   - Mobile Bottom Dock (docks to bottom on screens < 640px).
4. **Notice Catalog:**
   - Per-category master enable/disable switches.
   - Complete searchable breakdown table of all cases.
5. **Advanced:**
   - Full vs Standard AJAX interception.
   - Scoped Custom CSS editor.
   - Clean removal of database options upon uninstall.

---

## 🔒 WordPress Coding Standards & Security

Built strictly following WordPress.org official handbook standards:

- **Namespacing & Prefixing:** Every function, class, constant, CSS class, and handle is prefixed with `wc_toasts_`, `WC_Toasts_`, or `wct_`.
- **Direct Access Prevention:** Every PHP file starts with `defined( 'ABSPATH' ) || exit;`.
- **Nonce Verification:** All admin form submissions and AJAX requests verify nonces via `check_admin_referer()` or `check_ajax_referer()`.
- **Capability Checks:** All administrative actions verify `current_user_can( 'manage_woocommerce' )`.
- **Sanitization & Escaping:**
  - Inputs sanitized using `sanitize_text_field()`, `sanitize_hex_color()`, `absint()`, `sanitize_key()`.
  - Outputs escaped using `esc_html()`, `esc_attr()`, `esc_url()`, and `wp_kses_post()`.
- **Internationalization:** Full `.pot` translation file included in `languages/`.
- **Clean Uninstallation:** Includes `uninstall.php` to prune `wp_options` only when confirmed by the admin.

---

## 🔌 Extensibility & Pro Bridge API

The plugin includes clean extension hooks ready for 3rd-party add-ons and the upcoming **Pro version**:

### Custom Category Registration
```php
add_filter( 'wc_toasts_categories', function( $categories ) {
    $categories['wishlist'] = array(
        'id'          => 'wishlist',
        'name'        => __( 'Wishlist Alerts', 'text-domain' ),
        'description' => __( 'Notices triggered when items are added to wishlists.', 'text-domain' ),
        'icon'        => 'heart',
        'color'       => '#ec4899',
    );
    return $categories;
});
```

### Custom Notice Pattern Matching
```php
add_filter( 'wc_toasts_cases', function( $cases ) {
    $cases['wishlist_added'] = array(
        'category' => 'wishlist',
        'type'     => 'success',
        'title'    => __( 'Added to Wishlist', 'text-domain' ),
        'patterns' => array( '/added to your wishlist/i' ),
        'icon'     => 'heart',
        'sound'    => 'celebration',
    );
    return $cases;
});
```

### Intercept or Modify Toast Payloads
```php
add_filter( 'wc_toasts_classified_notice', function( $payload, $raw_notice, $type ) {
    if ( 'cart_item_added' === $payload['case_id'] ) {
        // Append free shipping hint to the message.
        $payload['message'] .= ' (Free shipping over $50!)';
    }
    return $payload;
}, 10, 3 );
```

### Pro Features Roadmap
- [x] Extensibility Bridge Architecture
- [ ] In-toast cross-sell recommendations (frequently bought together)
- [ ] Free shipping dynamic threshold progress meter
- [ ] Custom MP3/WAV audio uploads
- [ ] Toast impression and CTR conversion analytics
- [ ] VIP Customer & B2B role-based targeting

---

## 📁 File Structure

```
woocommerceToasts/
├── woocommerce-toasts.php             # Main bootstrap & HPOS declaration
├── uninstall.php                      # Database cleanup on uninstallation
├── readme.txt                         # WordPress.org standard repository readme
├── README.md                          # Complete developer and user documentation
├── languages/
│   └── woocommerce-toasts.pot         # Translation template
├── templates/
│   └── empty-notice.php               # Template override to suppress default banners
├── includes/
│   ├── class-wc-toasts.php            # Singleton orchestrator & admin UI
│   ├── class-wc-toasts-i18n.php       # Internationalization loader
│   ├── class-wc-toasts-interceptor.php # Server-side notice hijacker & script localizer
│   ├── class-wc-toasts-catalog.php    # Catalog of all WC notices, patterns & icons
│   ├── class-wc-toasts-settings.php   # Configuration schema, defaults & sanitization
│   ├── class-wc-toasts-ajax.php       # AJAX test triggers & reset endpoints
│   └── class-wc-toasts-pro-bridge.php # Extensibility hooks & Pro feature bridge
└── assets/
    ├── css/
    │   ├── wc-toasts-frontend.css     # Responsive toast styles, themes & animations
    │   └── wc-toasts-admin.css        # Admin dashboard, toggle switches & live preview
    └── js/
        ├── wc-toasts-frontend.js      # Vanilla JS toast engine, Web Audio & event listeners
        └── wc-toasts-admin.js         # Admin live preview controller & sound tester
```

---

## 🛠️ Installation & Testing

1. Clone or copy `woocommerceToasts` to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/thanos/woocommerceToasts.git
   ```
2. In WordPress Admin, go to **Plugins** and click **Activate** under **WooCommerce Dynamic Notice Toasts**.
3. Go to **WooCommerce > Notice Toasts** to test and customize.
4. Add any product to your cart on the frontend to experience the new dynamic toasts!

<?php
/**
 * Plugin Name:       Dynamic Notice Toasts for WooCommerce
 * Description:       Replaces WooCommerce notice banners with readable, customizable toasts, progress bars, and action links.
 * Version:           1.3.0
 * Author:            Thanos Leftheriotis
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dynamic-notice-toasts-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires Plugins:  woocommerce
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   11.2
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
if ( ! defined( 'WC_TOASTS_VERSION' ) ) {
	define( 'WC_TOASTS_VERSION', '1.3.0' );
}
if ( ! defined( 'WC_TOASTS_PLUGIN_FILE' ) ) {
	define( 'WC_TOASTS_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WC_TOASTS_PLUGIN_DIR' ) ) {
	define( 'WC_TOASTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WC_TOASTS_PLUGIN_URL' ) ) {
	define( 'WC_TOASTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WC_TOASTS_PLUGIN_BASENAME' ) ) {
	define( 'WC_TOASTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Declare High-Performance Order Storage (HPOS) compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Check if WooCommerce is active before loading the plugin.
 */
function wc_toasts_check_woocommerce_active() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wc_toasts_missing_woocommerce_notice' );
		return false;
	}
	return true;
}

/**
 * Admin notice when WooCommerce is inactive.
 */
function wc_toasts_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php esc_html_e( 'WooCommerce Dynamic Notice Toasts', 'dynamic-notice-toasts-for-woocommerce' ); ?>:</strong>
			<?php esc_html_e( 'This plugin requires WooCommerce to be installed and active.', 'dynamic-notice-toasts-for-woocommerce' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Include the main plugin class.
 */
require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts.php';

/**
 * Plugin activation hook.
 */
function wc_toasts_activate() {
	require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-settings.php';
	WC_Toasts_Settings::set_default_options();
}
register_activation_hook( __FILE__, 'wc_toasts_activate' );

/**
 * Plugin deactivation hook.
 */
function wc_toasts_deactivate() {
	// Flush any temporary notice caches if needed.
}
register_deactivation_hook( __FILE__, 'wc_toasts_deactivate' );

/**
 * Initialize the plugin instance.
 *
 * @return WC_Toasts
 */
function wc_toasts() {
	return WC_Toasts::instance();
}

// Start the plugin once plugins are loaded.
add_action(
	'plugins_loaded',
	function () {
		if ( wc_toasts_check_woocommerce_active() ) {
			wc_toasts();
		}
	}
);

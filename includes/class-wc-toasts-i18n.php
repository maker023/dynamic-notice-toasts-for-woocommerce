<?php
/**
 * Internationalization functionality for WooCommerce Dynamic Notice Toasts.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts_I18n
 */
class WC_Toasts_I18n {

	/**
	 * Load plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'woocommerce-toasts',
			false,
			dirname( WC_TOASTS_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}

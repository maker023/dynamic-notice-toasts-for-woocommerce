<?php
/**
 * WooCommerce Dynamic Notice Toasts Uninstaller
 *
 * Cleans up options upon plugin deletion if user chosen.
 *
 * @package WooCommerce_Toasts
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Delete the plugin options.
$wc_toasts_settings = get_option( 'wc_toasts_settings', array() );
if ( ! empty( $wc_toasts_settings['delete_data_on_uninstall'] ) ) {
	delete_option( 'wc_toasts_settings' );
	delete_option( 'wc_toasts_version' );
	delete_transient( 'wc_toasts_activation_redirect' );
}

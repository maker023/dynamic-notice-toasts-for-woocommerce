<?php
/** Integration assertions run only in the isolated review installation. */
require ( getenv( 'WC_TOASTS_TEST_ROOT' ) ? rtrim( getenv( 'WC_TOASTS_TEST_ROOT' ), '/\\' ) : '/wordpress' ) . '/wp-load.php';

function wc_toasts_review_assert( $value, $message ) {
	if ( ! $value ) {
		throw new RuntimeException( $message );
	}
}

wc_toasts_review_assert( class_exists( 'WC_Toasts' ), 'Plugin did not activate.' );
wc_toasts_review_assert( count( WC_Toasts_Styling::get_palettes() ) === 4, 'Expected exactly four bundled palettes.' );
wc_toasts_review_assert( array_keys( WC_Toasts_Styling::get_tokens() ) === array_keys( WC_Toasts_Styling::get_data()['tokens'] ), 'The PHP and standalone token schemas drifted.' );
$tokens = WC_Toasts_Styling::sanitize_tokens( array( 'surface' => '#123456', 'ink' => 'red;position:fixed', 'border' => array( '#fff' ), 'unknown' => '#abcdef' ) );
wc_toasts_review_assert( $tokens === array( 'surface' => '#123456' ), 'Unsafe or unknown color tokens were accepted.' );
$settings = WC_Toasts_Settings::sanitize_settings( array( 'layout' => array( 'bad' ), 'palette' => array( 'bad' ), 'custom_css' => '.wc-toast{color:red}','custom_tokens' => 'bad' ) );
wc_toasts_review_assert( $settings['layout'] === 'retro-outline' && $settings['palette'] === 'default-light', 'Malformed settings did not fall back safely.' );
wc_toasts_review_assert( $settings['custom_tokens'] === array(), 'Malformed token data survived sanitization.' );
wc_toasts_review_assert( ! array_key_exists( 'custom_css', $settings ), 'Arbitrary CSS survived sanitization.' );

add_filter( 'wc_toasts_color_tokens', function ( $registered ) { $registered['brand'] = 'Brand'; return $registered; } );
wc_toasts_review_assert( WC_Toasts_Styling::sanitize_tokens( array( 'brand' => '#abc' ) ) === array( 'brand' => '#abc' ), 'Token extension was rejected.' );
add_filter( 'wc_toasts_palettes', function ( $registered ) { $registered['brand'] = array( 'label' => 'Brand', 'surface' => '#ffffff', 'ink' => '#111111' ); return $registered; } );
add_filter( 'wc_toasts_layouts', function ( $registered ) { $registered['brand'] = 'Brand'; return $registered; } );
add_filter( 'wc_toasts_themes', function ( $registered ) { $registered['brand'] = array( 'label' => 'Brand', 'pro' => false ); return $registered; } );
$settings = WC_Toasts_Settings::sanitize_settings( array( 'layout' => 'brand', 'palette' => 'brand', 'theme' => 'brand' ) );
wc_toasts_review_assert( $settings['layout'] === 'brand' && $settings['palette'] === 'brand' && $settings['theme'] === 'brand', 'Registered extensions did not survive a settings save.' );
$notice = WC_Toasts_Catalog::classify_notice( '<img src=x onerror=alert(1)><script>alert(1)</script>Added to your cart <a href="javascript:alert(1)">bad</a>', 'success' );
wc_toasts_review_assert( strpos( $notice['raw_html'], '<script' ) === false && strpos( $notice['raw_html'], 'onerror' ) === false, 'Notice HTML was not sanitized.' );

update_option( WC_Toasts_Settings::OPTION_KEY, array( 'delete_data_on_uninstall' => 0 ) );
define( 'WP_UNINSTALL_PLUGIN', true );
require WP_PLUGIN_DIR . '/dynamic-notice-toasts/uninstall.php';
wc_toasts_review_assert( get_option( WC_Toasts_Settings::OPTION_KEY ) !== false, 'Uninstall deleted settings without opt-in.' );
update_option( WC_Toasts_Settings::OPTION_KEY, array( 'delete_data_on_uninstall' => 1 ) );
require WP_PLUGIN_DIR . '/dynamic-notice-toasts/uninstall.php';
wc_toasts_review_assert( get_option( WC_Toasts_Settings::OPTION_KEY ) === false, 'Uninstall did not remove opted-in settings.' );
update_option( WC_Toasts_Settings::OPTION_KEY, WC_Toasts_Settings::get_defaults() );
echo "Activation, sanitization, extension hooks, notice HTML, and uninstall assertions passed.\n";

<?php
/**
 * Palette registry and safe CSS token configuration.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/** Shared styling contract for the storefront, admin editor and extensions. */
class WC_Toasts_Styling {

	/**
	 * Read the bundled palette and token registry.
	 *
	 * @return array
	 */
	public static function get_data() {
		static $data;
		if ( null === $data ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a bundled local JSON file, never a remote URL.
			$data = json_decode( file_get_contents( WC_TOASTS_PLUGIN_DIR . 'assets/config/styling.json' ), true );
		}
		return is_array( $data ) ? $data : array(
			'palettes' => array(),
			'tokens'   => array(),
		);
	}

	/**
	 * Register or override palettes before they are used by the editor or storefront.
	 *
	 * @return array
	 */
	public static function get_palettes() {
		$data     = self::get_data();
		$palettes = $data['palettes'];
		$labels   = array(
			'sky-aubergine' => __( 'Sky Blue & Dark Aubergine', 'dynamic-notice-toasts-for-woocommerce' ),
			'lime-charcoal' => __( 'Lime Chartreuse & Charcoal', 'dynamic-notice-toasts-for-woocommerce' ),
			'default-light' => __( 'Default Light', 'dynamic-notice-toasts-for-woocommerce' ),
			'default-dark'  => __( 'Default Dark', 'dynamic-notice-toasts-for-woocommerce' ),
		);
		foreach ( $labels as $key => $label ) {
			$palettes[ $key ]['label'] = $label;
		}
		/** Filters registered palettes. Each entry contains label, surface, ink and optional accent. */
		return apply_filters( 'wc_toasts_palettes', $palettes );
	}

	/**
	 * Return editable token labels, extensible for custom styles.
	 *
	 * @return array
	 */
	public static function get_tokens() {
		$tokens = array(
			'surface'          => __( 'Background', 'dynamic-notice-toasts-for-woocommerce' ),
			'ink'              => __( 'Text', 'dynamic-notice-toasts-for-woocommerce' ),
			'accent'           => __( 'Accent', 'dynamic-notice-toasts-for-woocommerce' ),
			'on-accent'        => __( 'Text on accent', 'dynamic-notice-toasts-for-woocommerce' ),
			'muted'            => __( 'Secondary text', 'dynamic-notice-toasts-for-woocommerce' ),
			'border'           => __( 'Border', 'dynamic-notice-toasts-for-woocommerce' ),
			'icon'             => __( 'Icon', 'dynamic-notice-toasts-for-woocommerce' ),
			'focus'            => __( 'Focus ring', 'dynamic-notice-toasts-for-woocommerce' ),
			'control-fill'     => __( 'Control highlight', 'dynamic-notice-toasts-for-woocommerce' ),
			'shadow-color'     => __( 'Shadow', 'dynamic-notice-toasts-for-woocommerce' ),
			'action-bg'        => __( 'Action background', 'dynamic-notice-toasts-for-woocommerce' ),
			'action-ink'       => __( 'Action text', 'dynamic-notice-toasts-for-woocommerce' ),
			'action-hover-bg'  => __( 'Action hover background', 'dynamic-notice-toasts-for-woocommerce' ),
			'action-hover-ink' => __( 'Action hover text', 'dynamic-notice-toasts-for-woocommerce' ),
			'close-bg'         => __( 'Close background', 'dynamic-notice-toasts-for-woocommerce' ),
			'close-ink'        => __( 'Close icon', 'dynamic-notice-toasts-for-woocommerce' ),
			'close-hover-bg'   => __( 'Close hover background', 'dynamic-notice-toasts-for-woocommerce' ),
			'close-hover-ink'  => __( 'Close hover icon', 'dynamic-notice-toasts-for-woocommerce' ),
			'icon-bg'          => __( 'Status icon background', 'dynamic-notice-toasts-for-woocommerce' ),
			'icon-ink'         => __( 'Status icon color', 'dynamic-notice-toasts-for-woocommerce' ),
			'success'          => __( 'Success indicator', 'dynamic-notice-toasts-for-woocommerce' ),
			'error'            => __( 'Error indicator', 'dynamic-notice-toasts-for-woocommerce' ),
			'info'             => __( 'Info indicator', 'dynamic-notice-toasts-for-woocommerce' ),
			'warning'          => __( 'Warning indicator', 'dynamic-notice-toasts-for-woocommerce' ),
		);
		/** Filters the token allowlist. Keys must be lowercase CSS identifier suffixes. */
		return apply_filters( 'wc_toasts_color_tokens', $tokens );
	}

	/**
	 * Return the supported layouts.
	 *
	 * @return array
	 */
	public static function get_layouts() {
		return apply_filters(
			'wc_toasts_layouts',
			array(
				'editorial'     => __( 'Margin Note', 'dynamic-notice-toasts-for-woocommerce' ),
				'utility'       => __( 'System Capsule', 'dynamic-notice-toasts-for-woocommerce' ),
				'retro-outline' => __( 'Retro Outline', 'dynamic-notice-toasts-for-woocommerce' ),
				'expressive'    => __( 'Status Canvas', 'dynamic-notice-toasts-for-woocommerce' ),
			)
		);
	}

	/**
	 * Restrict saved overrides to registered tokens and hexadecimal colors.
	 *
	 * @param mixed $input Untrusted token values.
	 * @return array
	 */
	public static function sanitize_tokens( $input ) {
		$output = array();
		if ( ! is_array( $input ) ) {
			return $output;
		}
		foreach ( self::get_tokens() as $token => $label ) {
			if ( ! preg_match( '/^[a-z][a-z0-9-]*$/', $token ) || ! isset( $input[ $token ] ) || ! is_string( $input[ $token ] ) ) {
				continue;
			}
			$color = sanitize_hex_color( $input[ $token ] );
			if ( $color ) {
				$output[ $token ] = $color;
			}
		}
		return $output;
	}

	/**
	 * Build the client styling configuration using public extension hooks.
	 *
	 * @return array
	 */
	public static function get_config() {
		return array(
			'palettes' => self::get_palettes(),
			'tokens'   => self::get_tokens(),
			'layouts'  => self::get_layouts(),
		);
	}
}

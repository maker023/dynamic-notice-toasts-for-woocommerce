<?php
/**
 * Settings and Configuration Management for Dynamic Notice Toasts for WooCommerce.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts_Settings
 */
class WC_Toasts_Settings {

	/**
	 * Option key stored in wp_options.
	 */
	const OPTION_KEY = 'wc_toasts_settings';

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $cached_settings = null;

	/**
	 * Get default settings array.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$defaults = array(
			// General.
			'enabled'                  => 1,
			'hide_default_notices'     => 1,
			'position'                 => 'bottom-left',
			'theme'                    => 'editorial-monolith',
			'layout'                   => 'retro-outline',
			'palette'                  => 'default-light',
			'custom_tokens'            => array(),
			'max_toasts'               => 4,
			'newest_on_top'            => 1,
			'deduplication'            => 1,

			// Appearance.
			'border_radius'            => 12,
			'box_shadow'               => 'medium',
			'toast_width'              => 400,
			'show_icons'               => 1,
			'color_success_bg'         => '#ffffff',
			'color_success_accent'     => '#26715a',
			'color_success_text'       => '#242823',
			'color_error_bg'           => '#ffffff',
			'color_error_accent'       => '#ae4d45',
			'color_error_text'         => '#242823',
			'color_info_bg'            => '#ffffff',
			'color_info_accent'        => '#466c91',
			'color_info_text'          => '#242823',

			// Behavior.
			'duration_success'         => 4500,
			'duration_error'           => 7000,
			'duration_info'            => 5000,
			'show_progress_bar'        => 1,
			'pause_on_hover'           => 1,
			'show_close_button'        => 1,
			'mobile_bottom'            => 1,

			// Category Toggles.
			'enable_cat_cart'          => 1,
			'enable_cat_coupon'        => 1,
			'enable_cat_checkout'      => 1,
			'enable_cat_stock'         => 1,
			'enable_cat_account'       => 1,
			'enable_cat_system'        => 1,

			// Advanced.
			'ajax_interception'        => 'full',
			'delete_data_on_uninstall' => 0,
		);
		return apply_filters( 'wc_toasts_default_settings', $defaults );
	}

	/**
	 * Get all plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( null !== self::$cached_settings ) {
			return apply_filters( 'wc_toasts_settings', self::$cached_settings );
		}

		$options  = get_option( self::OPTION_KEY, array() );
		$defaults = self::get_defaults();

		if ( ! is_array( $options ) ) {
			$options = array();
		}
		unset( $options['sound_enabled'], $options['sound_theme'], $options['sound_volume'], $options['animation_in'], $options['animation_out'], $options['custom_css'] );

		self::$cached_settings = wp_parse_args( $options, $defaults );
		return apply_filters( 'wc_toasts_settings', self::$cached_settings );
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback value if setting not found.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$settings = self::get_all();
		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}
		if ( null !== $fallback ) {
			return $fallback;
		}
		$defaults = self::get_defaults();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
	}

	/**
	 * Set default options in database upon plugin activation if not already set.
	 */
	public static function set_default_options() {
		$existing = get_option( self::OPTION_KEY );
		if ( false === $existing ) {
			update_option( self::OPTION_KEY, self::get_defaults() );
		}
	}

	/**
	 * Sanitize and validate input settings.
	 *
	 * @param array $input Raw form input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		foreach ( $input as $key => $value ) {
			if ( 'custom_tokens' !== $key && ! is_scalar( $value ) ) {
				unset( $input[ $key ] );
			}
		}
		$defaults = self::get_defaults();
		$output   = array();

		// General.
		$output['enabled']              = ! empty( $input['enabled'] ) ? 1 : 0;
		$output['hide_default_notices'] = ! empty( $input['hide_default_notices'] ) ? 1 : 0;

		$allowed_positions  = array( 'top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center' );
		$output['position'] = ( isset( $input['position'] ) && in_array( $input['position'], $allowed_positions, true ) ) ? $input['position'] : $defaults['position'];

		$allowed_themes          = array_keys( WC_Toasts_Pro_Bridge::get_themes() );
		$output['theme']         = ( isset( $input['theme'] ) && in_array( $input['theme'], $allowed_themes, true ) ) ? $input['theme'] : $defaults['theme'];
		$layouts                 = WC_Toasts_Styling::get_layouts();
		$palettes                = WC_Toasts_Styling::get_palettes();
		$output['layout']        = isset( $input['layout'] ) && is_string( $input['layout'] ) && isset( $layouts[ $input['layout'] ] ) ? sanitize_key( $input['layout'] ) : $defaults['layout'];
		$output['palette']       = isset( $input['palette'] ) && is_string( $input['palette'] ) && isset( $palettes[ $input['palette'] ] ) ? sanitize_key( $input['palette'] ) : $defaults['palette'];
		$output['custom_tokens'] = WC_Toasts_Styling::sanitize_tokens( isset( $input['custom_tokens'] ) ? $input['custom_tokens'] : array() );

		$output['max_toasts']    = isset( $input['max_toasts'] ) ? max( 1, min( 10, absint( $input['max_toasts'] ) ) ) : $defaults['max_toasts'];
		$output['newest_on_top'] = ! empty( $input['newest_on_top'] ) ? 1 : 0;
		$output['deduplication'] = ! empty( $input['deduplication'] ) ? 1 : 0;

		// Appearance.
		$output['border_radius'] = isset( $input['border_radius'] ) ? max( 0, min( 30, absint( $input['border_radius'] ) ) ) : $defaults['border_radius'];

		$allowed_shadows      = array( 'none', 'subtle', 'medium', 'elevated', 'glow' );
		$output['box_shadow'] = ( isset( $input['box_shadow'] ) && in_array( $input['box_shadow'], $allowed_shadows, true ) ) ? $input['box_shadow'] : $defaults['box_shadow'];

		$output['toast_width'] = isset( $input['toast_width'] ) ? max( 260, min( 600, absint( $input['toast_width'] ) ) ) : $defaults['toast_width'];

		$output['show_icons'] = ! empty( $input['show_icons'] ) ? 1 : 0;

		// Color sanitization.
		$output['color_success_bg']     = isset( $input['color_success_bg'] ) ? sanitize_hex_color( $input['color_success_bg'] ) : $defaults['color_success_bg'];
		$output['color_success_accent'] = isset( $input['color_success_accent'] ) ? sanitize_hex_color( $input['color_success_accent'] ) : $defaults['color_success_accent'];
		$output['color_success_text']   = isset( $input['color_success_text'] ) ? sanitize_hex_color( $input['color_success_text'] ) : $defaults['color_success_text'];

		$output['color_error_bg']     = isset( $input['color_error_bg'] ) ? sanitize_hex_color( $input['color_error_bg'] ) : $defaults['color_error_bg'];
		$output['color_error_accent'] = isset( $input['color_error_accent'] ) ? sanitize_hex_color( $input['color_error_accent'] ) : $defaults['color_error_accent'];
		$output['color_error_text']   = isset( $input['color_error_text'] ) ? sanitize_hex_color( $input['color_error_text'] ) : $defaults['color_error_text'];

		$output['color_info_bg']     = isset( $input['color_info_bg'] ) ? sanitize_hex_color( $input['color_info_bg'] ) : $defaults['color_info_bg'];
		$output['color_info_accent'] = isset( $input['color_info_accent'] ) ? sanitize_hex_color( $input['color_info_accent'] ) : $defaults['color_info_accent'];
		$output['color_info_text']   = isset( $input['color_info_text'] ) ? sanitize_hex_color( $input['color_info_text'] ) : $defaults['color_info_text'];

		// Fallbacks if sanitize_hex_color returned null.
		foreach ( array( 'color_success_bg', 'color_success_accent', 'color_success_text', 'color_error_bg', 'color_error_accent', 'color_error_text', 'color_info_bg', 'color_info_accent', 'color_info_text' ) as $color_key ) {
			if ( empty( $output[ $color_key ] ) ) {
				$output[ $color_key ] = $defaults[ $color_key ];
			}
		}

		// Behavior.
		$output['duration_success'] = isset( $input['duration_success'] ) ? absint( $input['duration_success'] ) : $defaults['duration_success'];
		$output['duration_error']   = isset( $input['duration_error'] ) ? absint( $input['duration_error'] ) : $defaults['duration_error'];
		$output['duration_info']    = isset( $input['duration_info'] ) ? absint( $input['duration_info'] ) : $defaults['duration_info'];

		$output['show_progress_bar'] = ! empty( $input['show_progress_bar'] ) ? 1 : 0;
		$output['pause_on_hover']    = ! empty( $input['pause_on_hover'] ) ? 1 : 0;
		$output['show_close_button'] = ! empty( $input['show_close_button'] ) ? 1 : 0;
		$output['mobile_bottom']     = ! empty( $input['mobile_bottom'] ) ? 1 : 0;

		// Category Toggles.
		$output['enable_cat_cart']     = ! empty( $input['enable_cat_cart'] ) ? 1 : 0;
		$output['enable_cat_coupon']   = ! empty( $input['enable_cat_coupon'] ) ? 1 : 0;
		$output['enable_cat_checkout'] = ! empty( $input['enable_cat_checkout'] ) ? 1 : 0;
		$output['enable_cat_stock']    = ! empty( $input['enable_cat_stock'] ) ? 1 : 0;
		$output['enable_cat_account']  = ! empty( $input['enable_cat_account'] ) ? 1 : 0;
		$output['enable_cat_system']   = ! empty( $input['enable_cat_system'] ) ? 1 : 0;
		foreach ( WC_Toasts_Catalog::get_categories() as $category_key => $category_info ) {
			$option_key            = 'enable_cat_' . sanitize_key( $category_key );
			$output[ $option_key ] = ! empty( $input[ $option_key ] ) ? 1 : 0;
		}

		// Advanced.
		$allowed_interception        = array( 'full', 'standard' );
		$output['ajax_interception'] = ( isset( $input['ajax_interception'] ) && in_array( $input['ajax_interception'], $allowed_interception, true ) ) ? $input['ajax_interception'] : $defaults['ajax_interception'];

		$output['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] ) ? 1 : 0;

		self::$cached_settings = $output;
		return apply_filters( 'wc_toasts_sanitized_settings', $output, $input );
	}
}

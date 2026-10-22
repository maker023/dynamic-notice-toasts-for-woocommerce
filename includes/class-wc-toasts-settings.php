<?php
/**
 * Settings and Configuration Management for WooCommerce Dynamic Notice Toasts.
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
		return array(
			// General.
			'enabled'                   => 1,
			'hide_default_notices'      => 1,
			'position'                  => 'top-right',
			'theme'                     => 'editorial-monolith',
			'max_toasts'                => 4,
			'newest_on_top'             => 1,
			'deduplication'             => 1,

			// Appearance.
			'border_radius'             => 10,
			'box_shadow'                => 'medium',
			'toast_width'               => 380,
			'animation_in'              => 'slide-in',
			'animation_out'             => 'fade-out',
			'show_icons'                => 1,
			'color_success_bg'          => '#ffffff',
			'color_success_accent'      => '#10b981',
			'color_success_text'        => '#0f172a',
			'color_error_bg'            => '#ffffff',
			'color_error_accent'        => '#ef4444',
			'color_error_text'          => '#0f172a',
			'color_info_bg'             => '#ffffff',
			'color_info_accent'         => '#3b82f6',
			'color_info_text'           => '#0f172a',

			// Behavior & Audio.
			'duration_success'          => 4500,
			'duration_error'            => 7000,
			'duration_info'             => 5000,
			'show_progress_bar'         => 1,
			'pause_on_hover'            => 1,
			'show_close_button'         => 1,
			'sound_enabled'             => 1,
			'sound_theme'               => 'gentle_chime',
			'sound_volume'              => 40,
			'mobile_bottom'             => 1,

			// Category Toggles.
			'enable_cat_cart'           => 1,
			'enable_cat_coupon'         => 1,
			'enable_cat_checkout'       => 1,
			'enable_cat_stock'          => 1,
			'enable_cat_account'        => 1,
			'enable_cat_system'         => 1,

			// Advanced.
			'custom_css'                => '',
			'ajax_interception'         => 'full',
			'delete_data_on_uninstall'  => 0,
		);
	}

	/**
	 * Get all plugin settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( null !== self::$cached_settings ) {
			return self::$cached_settings;
		}

		$options  = get_option( self::OPTION_KEY, array() );
		$defaults = self::get_defaults();

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		self::$cached_settings = wp_parse_args( $options, $defaults );
		return self::$cached_settings;
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value if setting not found.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::get_all();
		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}
		if ( null !== $default ) {
			return $default;
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
		$defaults = self::get_defaults();
		$output   = array();

		// General.
		$output['enabled']              = ! empty( $input['enabled'] ) ? 1 : 0;
		$output['hide_default_notices'] = ! empty( $input['hide_default_notices'] ) ? 1 : 0;

		$allowed_positions = array( 'top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center' );
		$output['position'] = ( isset( $input['position'] ) && in_array( $input['position'], $allowed_positions, true ) ) ? $input['position'] : $defaults['position'];

		$allowed_themes  = array( 'editorial-monolith', 'swiss-basalt', 'nordic-gallery', 'modern-light', 'glassmorphism', 'dark-slate', 'minimal', 'woocommerce-purple', 'custom' );
		$output['theme'] = ( isset( $input['theme'] ) && in_array( $input['theme'], $allowed_themes, true ) ) ? $input['theme'] : $defaults['theme'];

		$output['max_toasts']    = isset( $input['max_toasts'] ) ? max( 1, min( 10, absint( $input['max_toasts'] ) ) ) : $defaults['max_toasts'];
		$output['newest_on_top'] = ! empty( $input['newest_on_top'] ) ? 1 : 0;
		$output['deduplication'] = ! empty( $input['deduplication'] ) ? 1 : 0;

		// Appearance.
		$output['border_radius'] = isset( $input['border_radius'] ) ? max( 0, min( 30, absint( $input['border_radius'] ) ) ) : $defaults['border_radius'];

		$allowed_shadows      = array( 'none', 'subtle', 'medium', 'elevated', 'glow' );
		$output['box_shadow'] = ( isset( $input['box_shadow'] ) && in_array( $input['box_shadow'], $allowed_shadows, true ) ) ? $input['box_shadow'] : $defaults['box_shadow'];

		$output['toast_width'] = isset( $input['toast_width'] ) ? max( 260, min( 600, absint( $input['toast_width'] ) ) ) : $defaults['toast_width'];

		$allowed_anim_in        = array( 'slide-in', 'fade-in', 'bounce-in', 'flip-in', 'zoom-in' );
		$output['animation_in'] = ( isset( $input['animation_in'] ) && in_array( $input['animation_in'], $allowed_anim_in, true ) ) ? $input['animation_in'] : $defaults['animation_in'];

		$allowed_anim_out        = array( 'fade-out', 'slide-out', 'zoom-out' );
		$output['animation_out'] = ( isset( $input['animation_out'] ) && in_array( $input['animation_out'], $allowed_anim_out, true ) ) ? $input['animation_out'] : $defaults['animation_out'];

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

		// Behavior & Audio.
		$output['duration_success'] = isset( $input['duration_success'] ) ? absint( $input['duration_success'] ) : $defaults['duration_success'];
		$output['duration_error']   = isset( $input['duration_error'] ) ? absint( $input['duration_error'] ) : $defaults['duration_error'];
		$output['duration_info']    = isset( $input['duration_info'] ) ? absint( $input['duration_info'] ) : $defaults['duration_info'];

		$output['show_progress_bar'] = ! empty( $input['show_progress_bar'] ) ? 1 : 0;
		$output['pause_on_hover']    = ! empty( $input['pause_on_hover'] ) ? 1 : 0;
		$output['show_close_button'] = ! empty( $input['show_close_button'] ) ? 1 : 0;
		$output['sound_enabled']     = ! empty( $input['sound_enabled'] ) ? 1 : 0;

		$allowed_sounds        = array( 'gentle_chime', 'modern_ping', 'crisp_pop', 'bell_ding', 'subtle_click' );
		$output['sound_theme'] = ( isset( $input['sound_theme'] ) && in_array( $input['sound_theme'], $allowed_sounds, true ) ) ? $input['sound_theme'] : $defaults['sound_theme'];

		$output['sound_volume']  = isset( $input['sound_volume'] ) ? max( 5, min( 100, absint( $input['sound_volume'] ) ) ) : $defaults['sound_volume'];
		$output['mobile_bottom'] = ! empty( $input['mobile_bottom'] ) ? 1 : 0;

		// Category Toggles.
		$output['enable_cat_cart']     = ! empty( $input['enable_cat_cart'] ) ? 1 : 0;
		$output['enable_cat_coupon']   = ! empty( $input['enable_cat_coupon'] ) ? 1 : 0;
		$output['enable_cat_checkout'] = ! empty( $input['enable_cat_checkout'] ) ? 1 : 0;
		$output['enable_cat_stock']    = ! empty( $input['enable_cat_stock'] ) ? 1 : 0;
		$output['enable_cat_account']  = ! empty( $input['enable_cat_account'] ) ? 1 : 0;
		$output['enable_cat_system']   = ! empty( $input['enable_cat_system'] ) ? 1 : 0;

		// Advanced.
		$output['custom_css'] = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';

		$allowed_interception        = array( 'full', 'standard' );
		$output['ajax_interception'] = ( isset( $input['ajax_interception'] ) && in_array( $input['ajax_interception'], $allowed_interception, true ) ) ? $input['ajax_interception'] : $defaults['ajax_interception'];

		$output['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] ) ? 1 : 0;

		self::$cached_settings = $output;
		return $output;
	}
}

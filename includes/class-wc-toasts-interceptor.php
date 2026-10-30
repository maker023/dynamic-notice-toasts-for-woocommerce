<?php
/**
 * WooCommerce Notification Interceptor
 *
 * Intercepts WooCommerce server-side notices, suppresses default alert banners,
 * categorizes notices, and passes them to the frontend toast engine.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts_Interceptor
 */
class WC_Toasts_Interceptor {

	/**
	 * Captured notices storage.
	 *
	 * @var array
	 */
	private $captured_notices = array();

	/**
	 * Flag indicating whether notices were already captured.
	 *
	 * @var bool
	 */
	private $notices_captured = false;

	/**
	 * Number of notices already handed to the browser.
	 *
	 * @var int
	 */
	private $delivered_notice_count = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! WC_Toasts_Settings::get( 'enabled', 1 ) ) {
			return;
		}

		// Enqueue hide-default CSS so it prints in the head and prevents layout shifts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_suppression_styles' ) );

		// Capture server notices at multiple hook points to ensure none are missed.
		add_action( 'template_redirect', array( $this, 'capture_server_notices' ), 99 );
		add_action( 'woocommerce_output_all_notices', array( $this, 'capture_server_notices' ), 1 );
		add_action( 'wp_footer', array( $this, 'capture_server_notices' ), 5 );

		// Filter notice templates to return empty markup if default notices are hidden.
		add_filter( 'wc_get_template', array( $this, 'filter_wc_notice_templates' ), 10, 2 );

		// Enqueue scripts & styles and localize captured notices.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );

		// Late fallback for notices generated during footer rendering; runs before footer scripts print at priority 20.
		add_action( 'wp_footer', array( $this, 'output_late_footer_notices' ), 19 );
	}

	/**
	 * Enqueue the stylesheet that hides default WooCommerce notices before they can flash or shift layout.
	 */
	public function enqueue_suppression_styles() {
		if ( is_admin() || ! WC_Toasts_Settings::get( 'hide_default_notices', 1 ) ) {
			return;
		}

		wp_enqueue_style(
			'wc-toasts-suppress-default',
			WC_TOASTS_PLUGIN_URL . 'assets/css/wc-toasts-suppress.css',
			array(),
			WC_TOASTS_VERSION
		);
	}

	/**
	 * Intercept and capture server notices from WooCommerce session.
	 */
	public function capture_server_notices() {
		if ( ! function_exists( 'wc_get_notices' ) ) {
			return;
		}

		$all_notices = wc_get_notices();
		if ( empty( $all_notices ) || ! is_array( $all_notices ) ) {
			return;
		}

		foreach ( $all_notices as $notice_type => $notices_list ) {
			if ( empty( $notices_list ) || ! is_array( $notices_list ) ) {
				continue;
			}

			foreach ( $notices_list as $notice_item ) {
				// WooCommerce notice items can be string or array with 'notice' and 'data'.
				$raw_content = is_array( $notice_item ) ? ( isset( $notice_item['notice'] ) ? $notice_item['notice'] : '' ) : $notice_item;
				$notice_data = is_array( $notice_item ) && isset( $notice_item['data'] ) ? $notice_item['data'] : array();

				if ( empty( $raw_content ) ) {
					continue;
				}

				// Classify notice with metadata.
				$classified = WC_Toasts_Catalog::classify_notice( $raw_content, $notice_type );
				if ( ! empty( $notice_data ) ) {
					$classified['wc_data'] = $notice_data;
				}

				// Check if category is enabled in settings.
				$cat_setting_key = 'enable_cat_' . $classified['category'];
				if ( ! WC_Toasts_Settings::get( $cat_setting_key, 1 ) ) {
					continue;
				}

				// Prevent duplicates in captured array.
				$is_duplicate = false;
				foreach ( $this->captured_notices as $existing ) {
					if ( $existing['message'] === $classified['message'] && $existing['type'] === $classified['type'] ) {
						$is_duplicate = true;
						break;
					}
				}

				if ( ! $is_duplicate ) {
					$this->captured_notices[] = $classified;
				}
			}
		}

		// If hiding default notices is enabled, clear the WC notice queue so standard HTML is omitted.
		if ( WC_Toasts_Settings::get( 'hide_default_notices', 1 ) && function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}

		$this->notices_captured = true;
	}

	/**
	 * Override notice templates when default notices are suppressed.
	 *
	 * @param string $located   Template path.
	 * @param string $template_name Template name.
	 * @return string
	 */
	public function filter_wc_notice_templates( $located, $template_name ) {
		if ( ! WC_Toasts_Settings::get( 'hide_default_notices', 1 ) ) {
			return $located;
		}

		if ( in_array( $template_name, array( 'notices/error.php', 'notices/notice.php', 'notices/success.php' ), true ) ) {
			// Capture them before returning empty placeholder.
			$this->capture_server_notices();
			return WC_TOASTS_PLUGIN_DIR . 'templates/empty-notice.php';
		}

		return $located;
	}

	/**
	 * Enqueue frontend CSS and JS assets.
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue on frontend when plugin is enabled.
		if ( is_admin() || ! WC_Toasts_Settings::get( 'enabled', 1 ) ) {
			return;
		}

		// Frontend styles.
		wp_enqueue_style(
			'wc-toasts-frontend',
			WC_TOASTS_PLUGIN_URL . 'assets/css/wc-toasts-frontend.css',
			array(),
			WC_TOASTS_VERSION
		);

		// Frontend script.
		wp_enqueue_script( 'wc-toasts-styling', WC_TOASTS_PLUGIN_URL . 'assets/js/wc-toasts-styling.js', array(), WC_TOASTS_VERSION, true );
		wp_enqueue_script(
			'wc-toasts-frontend',
			WC_TOASTS_PLUGIN_URL . 'assets/js/wc-toasts-frontend.js',
			array( 'jquery', 'wc-toasts-styling' ),
			WC_TOASTS_VERSION,
			true
		);

		// Localize frontend parameters and settings.
		$settings = WC_Toasts_Settings::get_all();

		// Add icons dictionary for frontend rendering.
		$icons_map = array();
		foreach ( array( 'check', 'cart', 'cart_check', 'cart_empty', 'trash', 'refresh', 'ticket', 'ticket_check', 'ticket_cross', 'ticket_alert', 'credit-card', 'card_error', 'archive', 'archive_alert', 'user', 'user_check', 'lock_alert', 'lock_check', 'bell', 'alert_triangle', 'alert_circle', 'info', 'truck', 'truck_alert', 'mail_check', 'mail_alert', 'phone_alert', 'edit_alert', 'map_pin', 'clock', 'shield_alert', 'download_alert' ) as $icon_name ) {
			$icons_map[ $icon_name ] = WC_Toasts_Catalog::get_svg_icon( $icon_name );
		}

		$params = array(
			'styling'         => WC_Toasts_Styling::get_config(),
			'categories'      => WC_Toasts_Catalog::get_categories(),
			'settings'        => $settings,
			'icons'           => $icons_map,
			'initial_notices' => $this->captured_notices,
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'cart_url'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
			'checkout_url'    => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
			'i18n'            => array(
				'success'                => __( 'Success', 'dynamic-notice-toasts-for-woocommerce' ),
				'error'                  => __( 'Error', 'dynamic-notice-toasts-for-woocommerce' ),
				'notice'                 => __( 'Notice', 'dynamic-notice-toasts-for-woocommerce' ),
				'store_notice'           => __( 'Store Notice', 'dynamic-notice-toasts-for-woocommerce' ),
				'notice_alert'           => __( 'Notice Alert', 'dynamic-notice-toasts-for-woocommerce' ),
				'information'            => __( 'Information', 'dynamic-notice-toasts-for-woocommerce' ),
				'checkout_alert'         => __( 'Checkout Alert', 'dynamic-notice-toasts-for-woocommerce' ),
				'alert'                  => __( 'Alert', 'dynamic-notice-toasts-for-woocommerce' ),
				'added_to_cart'          => __( 'Added to Cart', 'dynamic-notice-toasts-for-woocommerce' ),
				'coupon_applied'         => __( 'Coupon Applied', 'dynamic-notice-toasts-for-woocommerce' ),
				'coupon_removed'         => __( 'Coupon Removed', 'dynamic-notice-toasts-for-woocommerce' ),
				/* translators: %s: Coupon code. */
				'coupon_applied_message' => __( 'Coupon code "%s" applied successfully.', 'dynamic-notice-toasts-for-woocommerce' ),
				/* translators: %s: Coupon code. */
				'coupon_removed_message' => __( 'Coupon code "%s" has been removed.', 'dynamic-notice-toasts-for-woocommerce' ),
				'close'                  => __( 'Close notice', 'dynamic-notice-toasts-for-woocommerce' ),
				'view_cart'              => __( 'View cart', 'dynamic-notice-toasts-for-woocommerce' ),
				'checkout'               => __( 'Checkout', 'dynamic-notice-toasts-for-woocommerce' ),
				'undo'                   => __( 'Undo', 'dynamic-notice-toasts-for-woocommerce' ),
				'added_notice'           => __( 'has been added to your cart.', 'dynamic-notice-toasts-for-woocommerce' ),
			),
		);

		wp_localize_script( 'wc-toasts-frontend', 'wc_toasts_frontend_params', apply_filters( 'wc_toasts_frontend_params', $params ) );
		$this->delivered_notice_count = count( $this->captured_notices );
	}

	/**
	 * Late fallback that hands notices queued during page rendering to the frontend script.
	 */
	public function output_late_footer_notices() {
		// Run a late capture in case something queued a notice during template execution.
		$this->capture_server_notices();

		$late_notices = array_slice( $this->captured_notices, $this->delivered_notice_count );
		if ( empty( $late_notices ) ) {
			return;
		}

		// wp_localize_script already ran, so queue late notices for the frontend script to read on init.
		wp_add_inline_script(
			'wc-toasts-frontend',
			'window.__wc_toasts_pending_notices = (window.__wc_toasts_pending_notices || []).concat(' . wp_json_encode( $late_notices ) . ');',
			'before'
		);
		$this->delivered_notice_count = count( $this->captured_notices );
	}

	/**
	 * Get captured notices array.
	 *
	 * @return array
	 */
	public function get_captured_notices() {
		return $this->captured_notices;
	}
}

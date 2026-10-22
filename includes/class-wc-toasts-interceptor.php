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
	 * Constructor.
	 */
	public function __construct() {
		if ( ! WC_Toasts_Settings::get( 'enabled', 1 ) ) {
			return;
		}

		// Inject hide-default CSS early into wp_head to prevent layout shifts.
		add_action( 'wp_head', array( $this, 'inject_suppression_styles' ), 1 );

		// Capture server notices at multiple hook points to ensure none are missed.
		add_action( 'template_redirect', array( $this, 'capture_server_notices' ), 99 );
		add_action( 'woocommerce_output_all_notices', array( $this, 'capture_server_notices' ), 1 );
		add_action( 'wp_footer', array( $this, 'capture_server_notices' ), 5 );

		// Filter notice templates to return empty markup if default notices are hidden.
		add_filter( 'wc_get_template', array( $this, 'filter_wc_notice_templates' ), 10, 2 );

		// Enqueue scripts & styles and localize captured notices.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ), 20 );

		// Late footer fallback for notices generated during footer rendering.
		add_action( 'wp_footer', array( $this, 'output_late_footer_notices' ), 99 );
	}

	/**
	 * Output early CSS styles to prevent default WooCommerce notices from flashing or shifting layout.
	 */
	public function inject_suppression_styles() {
		if ( ! WC_Toasts_Settings::get( 'hide_default_notices', 1 ) ) {
			return;
		}

		// Clean, scoped CSS hiding standard notice containers.
		echo '<style id="wc-toasts-suppress-default">' . "\n";
		echo '.woocommerce-notices-wrapper .woocommerce-message,' . "\n";
		echo '.woocommerce-notices-wrapper .woocommerce-error,' . "\n";
		echo '.woocommerce-notices-wrapper .woocommerce-info,' . "\n";
		echo '.woocommerce-NoticeGroup .woocommerce-error,' . "\n";
		echo '.woocommerce-NoticeGroup .woocommerce-message,' . "\n";
		echo 'div.woocommerce-message,' . "\n";
		echo 'ul.woocommerce-error,' . "\n";
		echo 'div.woocommerce-info {' . "\n";
		echo '    display: none !important;' . "\n";
		echo '}' . "\n";

		// Custom CSS if set by store owner.
		$custom_css = WC_Toasts_Settings::get( 'custom_css', '' );
		if ( ! empty( $custom_css ) ) {
			echo wp_strip_all_tags( $custom_css ) . "\n";
		}

		echo '</style>' . "\n";
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
		wp_enqueue_script(
			'wc-toasts-frontend',
			WC_TOASTS_PLUGIN_URL . 'assets/js/wc-toasts-frontend.js',
			array( 'jquery' ),
			WC_TOASTS_VERSION,
			true
		);

		// Localize frontend parameters and settings.
		$settings = WC_Toasts_Settings::get_all();

		// Add icons dictionary for frontend rendering.
		$icons_map = array();
		foreach ( array( 'check', 'cart', 'cart_check', 'cart_empty', 'trash', 'refresh', 'ticket', 'ticket_check', 'ticket_cross', 'ticket_alert', 'credit-card', 'card_error', 'archive', 'archive_alert', 'user', 'user_check', 'lock_alert', 'lock_check', 'bell', 'alert_triangle', 'alert_circle', 'info', 'truck', 'truck_alert', 'mail_check', 'mail_alert', 'phone_alert', 'edit_alert', 'map_pin', 'clock', 'shield_alert', 'close' ) as $icon_name ) {
			$icons_map[ $icon_name ] = WC_Toasts_Catalog::get_svg_icon( $icon_name );
		}

		$params = array(
			'settings'         => $settings,
			'icons'            => $icons_map,
			'initial_notices'  => $this->captured_notices,
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'cart_url'         => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
			'checkout_url'     => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
			'i18n'             => array(
				'close'        => __( 'Close notice', 'woocommerce-toasts' ),
				'view_cart'    => __( 'View cart', 'woocommerce-toasts' ),
				'checkout'     => __( 'Checkout', 'woocommerce-toasts' ),
				'undo'         => __( 'Undo', 'woocommerce-toasts' ),
				'added_notice' => __( 'has been added to your cart.', 'woocommerce-toasts' ),
			),
		);

		wp_localize_script( 'wc-toasts-frontend', 'wc_toasts_frontend_params', $params );
	}

	/**
	 * Late footer fallback to print any notices that were queued late during page rendering.
	 */
	public function output_late_footer_notices() {
		// Run a late capture in case something queued a notice during template execution.
		$this->capture_server_notices();

		if ( empty( $this->captured_notices ) ) {
			return;
		}

		// If wp_localize_script already fired before late notices, output inline JSON push.
		?>
		<script id="wc-toasts-late-notices">
		(function() {
			if (window.WCToasts && typeof window.WCToasts.pushNotices === 'function') {
				window.WCToasts.pushNotices(<?php echo wp_json_encode( $this->captured_notices ); ?>);
			} else {
				window.__wc_toasts_pending_notices = (window.__wc_toasts_pending_notices || []).concat(<?php echo wp_json_encode( $this->captured_notices ); ?>);
			}
		})();
		</script>
		<?php
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

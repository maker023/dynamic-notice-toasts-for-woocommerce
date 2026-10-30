<?php
/**
 * AJAX Handler for Dynamic Notice Toasts for WooCommerce
 *
 * Handles admin testing, live preview triggers, and settings management via AJAX.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts_Ajax
 */
class WC_Toasts_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wc_toasts_test_notice', array( $this, 'ajax_test_notice' ) );
		add_action( 'wp_ajax_wc_toasts_reset_settings', array( $this, 'ajax_reset_settings' ) );
	}

	/**
	 * Handle AJAX test notice request from admin settings live preview.
	 */
	public function ajax_test_notice() {
		// Nonce check.
		check_ajax_referer( 'wc_toasts_admin_nonce', 'security' );

		// Capability check.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dynamic-notice-toasts-for-woocommerce' ) ) );
		}

		$category = isset( $_POST['category'] ) ? sanitize_key( $_POST['category'] ) : 'cart';
		$type     = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'success';

		// Sample test notifications per category.
		$samples = array(
			'cart'     => array(
				'success' => array(
					'message' => __( '“Classic Hoodie” has been added to your cart.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Product Added to Cart', 'dynamic-notice-toasts-for-woocommerce' ),
					'action'  => array(
						'text'   => __( 'View cart', 'dynamic-notice-toasts-for-woocommerce' ),
						'url'    => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#',
						'target' => '_self',
					),
					'icon'    => 'cart_check',
				),
				'error'   => array(
					'message' => __( 'You must have an order with a minimum of $50.00 to place your order.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Minimum Order Amount Required', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'alert_triangle',
				),
				'notice'  => array(
					'message' => __( '“Classic Hoodie” removed.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Item Removed from Cart', 'dynamic-notice-toasts-for-woocommerce' ),
					'action'  => array(
						'text'   => __( 'Undo?', 'dynamic-notice-toasts-for-woocommerce' ),
						'url'    => '#undo',
						'target' => '_self',
					),
					'icon'    => 'trash',
				),
			),
			'coupon'   => array(
				'success' => array(
					'message' => __( 'Coupon code "FALL25" applied successfully! 25% discount activated.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Coupon Applied', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'ticket_check',
				),
				'error'   => array(
					'message' => __( 'Coupon "SUMMERSALE" has expired.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Coupon Expired', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'ticket_alert',
				),
				'notice'  => array(
					'message' => __( 'Coupon "FALL25" has been removed from your basket.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Coupon Removed', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'ticket_cross',
				),
			),
			'checkout' => array(
				'error'   => array(
					'message' => __( 'Billing Postcode / ZIP is a required field. Please enter a valid postcode to continue.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Required Field Missing', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'edit_alert',
				),
				'success' => array(
					'message' => __( 'Shipping costs updated based on your selected address.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Shipping Calculated', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'truck',
				),
			),
			'stock'    => array(
				'error' => array(
					'message' => __( 'You cannot add that amount to the cart — we have 2 in stock and you already have 2 in your cart.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Insufficient Stock', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'archive_alert',
				),
			),
			'account'  => array(
				'success' => array(
					'message' => __( 'Account details changed successfully.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Profile Updated', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'user_check',
				),
				'error'   => array(
					'message' => __( 'Error: The password you entered for the username admin is incorrect.', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Login Error', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'lock_alert',
				),
			),
			'system'   => array(
				'notice' => array(
					'message' => __( 'Free worldwide shipping is available for all orders over $75 today!', 'dynamic-notice-toasts-for-woocommerce' ),
					'title'   => __( 'Store Announcement', 'dynamic-notice-toasts-for-woocommerce' ),
					'icon'    => 'bell',
				),
			),
		);

		$sample_data = isset( $samples[ $category ][ $type ] )
			? $samples[ $category ][ $type ]
			: ( isset( $samples['cart']['success'] ) ? $samples['cart']['success'] : array() );

		$categories = WC_Toasts_Catalog::get_categories();
		$cat_info   = isset( $categories[ $category ] ) ? $categories[ $category ] : $categories['system'];

		$notice = array(
			'id'        => 'test_' . uniqid(),
			'case_id'   => 'admin_test_' . $category,
			'category'  => $category,
			'cat_name'  => $cat_info['name'],
			'type'      => $type,
			'title'     => ! empty( $sample_data['title'] ) ? $sample_data['title'] : __( 'Notification Alert', 'dynamic-notice-toasts-for-woocommerce' ),
			'message'   => ! empty( $sample_data['message'] ) ? $sample_data['message'] : __( 'This is a test notification toast.', 'dynamic-notice-toasts-for-woocommerce' ),
			'action'    => ! empty( $sample_data['action'] ) ? $sample_data['action'] : null,
			'icon'      => ! empty( $sample_data['icon'] ) ? $sample_data['icon'] : 'info',
			'accent'    => $cat_info['color'],
			'timestamp' => time(),
		);

		wp_send_json_success( array( 'notice' => $notice ) );
	}

	/**
	 * Reset all plugin settings to factory defaults.
	 */
	public function ajax_reset_settings() {
		// Nonce check.
		check_ajax_referer( 'wc_toasts_admin_nonce', 'security' );

		// Capability check.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dynamic-notice-toasts-for-woocommerce' ) ) );
		}

		$defaults = WC_Toasts_Settings::get_defaults();
		update_option( WC_Toasts_Settings::OPTION_KEY, $defaults );

		wp_send_json_success( array( 'message' => __( 'Settings successfully reset to factory defaults.', 'dynamic-notice-toasts-for-woocommerce' ) ) );
	}
}

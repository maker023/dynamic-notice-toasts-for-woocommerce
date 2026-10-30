<?php
/**
 * WooCommerce Notification Catalog and Classifier
 *
 * Catalogs every known WooCommerce core notification case, groups them into
 * logical categories, and provides intelligent classification and action extraction.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts_Catalog
 */
class WC_Toasts_Catalog {

	/**
	 * Category constants.
	 */
	const CAT_CART     = 'cart';
	const CAT_COUPON   = 'coupon';
	const CAT_CHECKOUT = 'checkout';
	const CAT_STOCK    = 'stock';
	const CAT_ACCOUNT  = 'account';
	const CAT_SYSTEM   = 'system';

	/**
	 * Notice types constants.
	 */
	const TYPE_SUCCESS = 'success';
	const TYPE_ERROR   = 'error';
	const TYPE_INFO    = 'notice';
	const TYPE_WARNING = 'warning';

	/**
	 * Get all categories with metadata.
	 *
	 * @return array
	 */
	public static function get_categories() {
		$categories = array(
			self::CAT_CART     => array(
				'id'          => self::CAT_CART,
				'name'        => __( 'Cart & Basket', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Item additions, removals, quantity updates, and cart notices.', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'        => 'cart',
				'color'       => '#10b981', // Emerald green.
				'priority'    => 10,
			),
			self::CAT_COUPON   => array(
				'id'          => self::CAT_COUPON,
				'name'        => __( 'Coupons & Discounts', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Coupon application, removal, restrictions, and expiration alerts.', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'        => 'ticket',
				'color'       => '#8b5cf6', // Violet.
				'priority'    => 20,
			),
			self::CAT_CHECKOUT => array(
				'id'          => self::CAT_CHECKOUT,
				'name'        => __( 'Checkout & Orders', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Field validation, terms acceptance, shipping selection, and payment errors.', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'        => 'credit-card',
				'color'       => '#ef4444', // Red.
				'priority'    => 30,
			),
			self::CAT_STOCK    => array(
				'id'          => self::CAT_STOCK,
				'name'        => __( 'Stock & Inventory', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Out-of-stock items, purchase limits, and backorder notifications.', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'        => 'archive',
				'color'       => '#f59e0b', // Amber.
				'priority'    => 40,
			),
			self::CAT_ACCOUNT  => array(
				'id'          => self::CAT_ACCOUNT,
				'name'        => __( 'Customer & Account', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Login errors, registration, password resets, and profile updates.', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'        => 'user',
				'color'       => '#3b82f6', // Blue.
				'priority'    => 50,
			),
			self::CAT_SYSTEM   => array(
				'id'          => self::CAT_SYSTEM,
				'name'        => __( 'System & Store Notices', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'General alerts, store maintenance, downloads, and uncategorized notices.', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'        => 'bell',
				'color'       => '#6b7280', // Gray.
				'priority'    => 60,
			),
		);

		return apply_filters( 'wc_toasts_categories', $categories );
	}

	/**
	 * Get comprehensive catalog of EVERY known WooCommerce notification case.
	 *
	 * @return array
	 */
	public static function get_cases() {
		$cases = array(
			// ==========================================
			// 1. CART NOTIFICATIONS
			// ==========================================
			'cart_item_added'                => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Product Added to Cart', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/has been added to your cart/i',
					'/have been added to your cart/i',
					'/added to your cart/i',
				),
				'icon'     => 'cart_check',
			),
			'cart_item_removed'              => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Item Removed from Cart', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/removed\.\s*(Undo\?)?/i',
					'/item removed/i',
				),
				'icon'     => 'trash',
			),
			'cart_item_restored'             => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Item Restored to Cart', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/restored to cart/i',
					'/item restored/i',
				),
				'icon'     => 'refresh',
			),
			'cart_updated'                   => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Cart Updated', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/cart updated/i',
					'/basket updated/i',
				),
				'icon'     => 'refresh',
			),
			'cart_empty'                     => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Cart is Empty', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/cart is currently empty/i',
					'/basket is currently empty/i',
				),
				'icon'     => 'cart_empty',
			),
			'cart_minimum_order'             => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Minimum Order Amount Required', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/minimum of.*to place your order/i',
					'/minimum order amount/i',
				),
				'icon'     => 'alert_triangle',
			),
			'cart_maximum_order'             => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Maximum Order Amount Exceeded', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/exceeds the maximum allowed amount/i',
					'/maximum order amount/i',
				),
				'icon'     => 'alert_triangle',
			),
			'cart_quantity_invalid'          => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Item Quantity', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/minimum quantity for/i',
					'/maximum quantity for/i',
					'/increments of/i',
				),
				'icon'     => 'alert_circle',
			),

			// ==========================================
			// 2. COUPON NOTIFICATIONS
			// ==========================================
			'coupon_applied'                 => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Coupon Code Applied', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/coupon code applied successfully/i',
					'/coupon applied/i',
				),
				'icon'     => 'ticket_check',
			),
			'coupon_removed'                 => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Coupon Code Removed', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/coupon code has been removed/i',
					'/coupon removed/i',
				),
				'icon'     => 'ticket_cross',
			),
			'coupon_not_exist'               => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Does Not Exist', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/coupon.*does not exist/i',
					'/invalid coupon/i',
				),
				'icon'     => 'ticket_alert',
			),
			'coupon_already_applied'         => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Already Applied', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/coupon code already applied/i',
					'/already been applied/i',
				),
				'icon'     => 'ticket_alert',
			),
			'coupon_expired'                 => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Has Expired', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/this coupon has expired/i',
					'/coupon has expired/i',
				),
				'icon'     => 'clock',
			),
			'coupon_usage_limit_reached'     => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Usage Limit Reached', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/coupon usage limit has been reached/i',
					'/usage limit reached/i',
				),
				'icon'     => 'shield_alert',
			),
			'coupon_min_spend'               => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Minimum Spend Not Met', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/minimum spend for this coupon is/i',
				),
				'icon'     => 'ticket_alert',
			),
			'coupon_max_spend'               => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Maximum Spend Exceeded', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/maximum spend for this coupon is/i',
				),
				'icon'     => 'ticket_alert',
			),
			'coupon_individual_use'          => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Cannot Combine Coupons', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/cannot be used in conjunction with other coupons/i',
					'/individual use only/i',
				),
				'icon'     => 'ticket_alert',
			),
			'coupon_excluded_items'          => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Excluded From Items', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/not applicable to selected products/i',
					'/not applicable to selected categories/i',
					'/not applicable to sale items/i',
				),
				'icon'     => 'ticket_alert',
			),

			// ==========================================
			// 3. CHECKOUT NOTIFICATIONS
			// ==========================================
			'checkout_field_required'        => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Required Field Missing', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/is a required field/i',
					'/please enter a.*to continue/i',
				),
				'icon'     => 'edit_alert',
			),
			'checkout_invalid_email'         => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Email Address', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/please provide a valid email address/i',
					'/enter a valid email/i',
				),
				'icon'     => 'mail_alert',
			),
			'checkout_invalid_postcode'      => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Postcode / ZIP', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/please enter a valid postcode/i',
					'/valid zip/i',
				),
				'icon'     => 'map_pin',
			),
			'checkout_invalid_phone'         => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Phone Number', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/please enter a valid phone number/i',
					'/valid phone/i',
				),
				'icon'     => 'phone_alert',
			),
			'checkout_terms_required'        => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Terms Acceptance Required', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/read and accept the terms and conditions/i',
					'/terms and conditions/i',
				),
				'icon'     => 'shield_alert',
			),
			'checkout_no_shipping_method'    => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Shipping Method Required', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/no shipping method has been selected/i',
					'/choose a shipping method/i',
				),
				'icon'     => 'truck',
			),
			'checkout_no_shipping_available' => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'No Shipping Options Available', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/no shipping options available/i',
					'/cannot be delivered to/i',
				),
				'icon'     => 'truck_alert',
			),
			'checkout_payment_failed'        => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Payment Failed', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/payment failed/i',
					'/could not process your payment/i',
					'/declined/i',
				),
				'icon'     => 'card_error',
			),
			'checkout_shipping_updated'      => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Shipping Costs Updated', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/shipping costs updated/i',
					'/shipping calculated/i',
				),
				'icon'     => 'truck',
			),

			// ==========================================
			// 4. STOCK & INVENTORY NOTIFICATIONS
			// ==========================================
			'stock_out_of_stock'             => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Out of Stock', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/product is out of stock/i',
					'/out of stock/i',
				),
				'icon'     => 'archive_alert',
			),
			'stock_insufficient'             => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Insufficient Stock', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/cannot add that amount to the cart.*in stock/i',
					'/not enough stock/i',
				),
				'icon'     => 'archive_alert',
			),
			'stock_sold_individually'        => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Item Sold Individually', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/cannot add another.*to your cart/i',
					'/sold individually/i',
				),
				'icon'     => 'alert_circle',
			),
			'stock_not_purchasable'          => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Product Not Purchasable', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/cannot be purchased/i',
					'/not currently available/i',
				),
				'icon'     => 'alert_circle',
			),
			'stock_backordered'              => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Item on Backorder', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/item is on backorder/i',
					'/available on backorder/i',
				),
				'icon'     => 'clock',
			),

			// ==========================================
			// 5. CUSTOMER & ACCOUNT NOTIFICATIONS
			// ==========================================
			'account_login_failed'           => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Login Error', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/password you entered for the username.*is incorrect/i',
					'/unknown username/i',
					'/unknown email/i',
					'/invalid username or email/i',
				),
				'icon'     => 'lock_alert',
			),
			'account_registration_success'   => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Registration Successful', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/account was created successfully/i',
					'/registration complete/i',
				),
				'icon'     => 'user_check',
			),
			'account_password_reset_sent'    => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Password Reset Email Sent', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/password reset email has been sent/i',
					'/check your email for the confirmation link/i',
				),
				'icon'     => 'mail_check',
			),
			'account_password_reset_success' => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Password Reset Complete', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/your password has been reset/i',
				),
				'icon'     => 'lock_check',
			),
			'account_details_saved'          => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Account Details Updated', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/account details changed successfully/i',
					'/profile updated/i',
				),
				'icon'     => 'user_check',
			),
			'account_address_saved'          => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Address Updated', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/address changed successfully/i',
				),
				'icon'     => 'map_pin',
			),

			// ==========================================
			// 6. SYSTEM & STORE NOTIFICATIONS
			// ==========================================
			'system_download_limit'          => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Download Limit Reached', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/reached your download limit/i',
				),
				'icon'     => 'download_alert',
			),
			'system_order_cancelled'         => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Order Cancelled', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(
					'/order has been cancelled/i',
				),
				'icon'     => 'alert_circle',
			),
			'system_generic_success'         => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Action Completed', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(),
				'icon'     => 'check',
			),
			'system_generic_error'           => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Action Failed', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(),
				'icon'     => 'alert_triangle',
			),
			'system_generic_notice'          => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Store Notice', 'dynamic-notice-toasts-for-woocommerce' ),
				'patterns' => array(),
				'icon'     => 'info',
			),
		);

		return apply_filters( 'wc_toasts_cases', $cases );
	}

	/**
	 * Extract any action link (e.g. <a href="..." class="button wc-forward">View cart</a> or <a class="restore-item">Undo?</a>)
	 * from a raw HTML notice.
	 *
	 * @param string $html Raw HTML notice.
	 * @return array Array containing 'clean_text', 'action' (or null), and 'html'.
	 */
	public static function parse_notice_action( $html ) {
		$action = null;

		// Regex to find <a> tags inside the notice.
		if ( preg_match( '/<a\s+([^>]*?)>(.*?)<\/a>/is', $html, $matches ) ) {
			$raw_attrs   = $matches[1];
			$action_text = wp_strip_all_tags( $matches[2] );
			$action_url  = '';
			$target      = '_self';

			// Extract href.
			if ( preg_match( '/href=[\'"]([^\'"]+)[\'"]/i', $raw_attrs, $href_match ) ) {
				$action_url = esc_url_raw( $href_match[1] );
			}
			// Extract target.
			if ( preg_match( '/target=[\'"]([^\'"]+)[\'"]/i', $raw_attrs, $target_match ) ) {
				$target = sanitize_text_field( $target_match[1] );
			}

			if ( ! empty( $action_url ) && ! empty( $action_text ) ) {
				$action = array(
					'text'   => trim( $action_text ),
					'url'    => $action_url,
					'target' => $target,
				);
			}

			// Remove the <a> tag from the message string.
			$clean_html = trim( str_replace( $matches[0], '', $html ) );
		} else {
			$clean_html = $html;
		}

		// Convert HTML entities and trim unwanted quotes or trailing spaces.
		$clean_text = trim( wp_strip_all_tags( html_entity_decode( $clean_html, ENT_QUOTES, 'UTF-8' ) ) );

		return array(
			'clean_text' => $clean_text,
			'clean_html' => wp_kses_post( $clean_html ),
			'action'     => $action,
		);
	}

	/**
	 * Intelligently classify a notice into a case and category.
	 *
	 * @param string $raw_notice Raw notice string or HTML.
	 * @param string $type       Notice type ('success', 'error', 'notice').
	 * @return array Classified notice payload.
	 */
	public static function classify_notice( $raw_notice, $type = 'notice' ) {
		// Normalize type.
		if ( 'error' === $type ) {
			$normalized_type = self::TYPE_ERROR;
		} elseif ( 'success' === $type ) {
			$normalized_type = self::TYPE_SUCCESS;
		} else {
			$normalized_type = self::TYPE_INFO;
		}

		$parsed     = self::parse_notice_action( $raw_notice );
		$text       = $parsed['clean_text'];
		$cases      = self::get_cases();
		$categories = self::get_categories();

		$matched_case_id = '';
		$matched_case    = null;

		// Loop through catalog cases with patterns.
		foreach ( $cases as $case_id => $case_def ) {
			if ( empty( $case_def['patterns'] ) ) {
				continue;
			}
			foreach ( $case_def['patterns'] as $pattern ) {
				if ( preg_match( $pattern, $text ) || preg_match( $pattern, $raw_notice ) ) {
					$matched_case_id = $case_id;
					$matched_case    = $case_def;
					break 2;
				}
			}
		}

		// If no specific case matched, fall back to generic case for the given notice type.
		if ( ! $matched_case ) {
			if ( self::TYPE_SUCCESS === $normalized_type ) {
				$matched_case_id = 'system_generic_success';
			} elseif ( self::TYPE_ERROR === $normalized_type ) {
				$matched_case_id = 'system_generic_error';
			} else {
				$matched_case_id = 'system_generic_notice';
			}
			$matched_case = $cases[ $matched_case_id ];
		}

		$category_id = isset( $matched_case['category'] ) ? $matched_case['category'] : self::CAT_SYSTEM;
		$category    = isset( $categories[ $category_id ] ) ? $categories[ $category_id ] : $categories[ self::CAT_SYSTEM ];

		$payload = array(
			'id'        => uniqid( 'wct_' ),
			'case_id'   => $matched_case_id,
			'category'  => $category_id,
			'cat_name'  => $category['name'],
			'type'      => ! empty( $matched_case['type'] ) ? $matched_case['type'] : $normalized_type,
			'title'     => $matched_case['title'],
			'message'   => $text,
			'raw_html'  => $parsed['clean_html'],
			'action'    => $parsed['action'],
			'icon'      => ! empty( $matched_case['icon'] ) ? $matched_case['icon'] : 'info',
			'accent'    => $category['color'],
			'timestamp' => time(),
		);

		return apply_filters( 'wc_toasts_classified_notice', $payload, $raw_notice, $type );
	}

	/**
	 * Get inline SVG icons for notice categories and toast rendering.
	 *
	 * @param string $icon_name Icon identifier.
	 * @return string Safe inline SVG string.
	 */
	public static function get_svg_icon( $icon_name ) {
		static $cache = array();
		$files        = array(
			'check'          => 'check',
			'cart'           => 'shopping-bag',
			'cart_check'     => 'shopping-bag-check',
			'cart_empty'     => 'shopping-bag-minus',
			'trash'          => 'trash',
			'refresh'        => 'refresh-double',
			'ticket'         => 'shopping-code',
			'ticket_check'   => 'shopping-code-check',
			'ticket_cross'   => 'shopping-code-xmark',
			'ticket_alert'   => 'shopping-code-xmark',
			'credit-card'    => 'credit-card',
			'card_error'     => 'credit-card-slash',
			'archive'        => 'box',
			'archive_alert'  => 'shopping-bag-warning',
			'user'           => 'user',
			'user_check'     => 'user-badge-check',
			'lock_alert'     => 'lock',
			'lock_check'     => 'password-check',
			'bell'           => 'bell-notification',
			'alert_triangle' => 'warning-triangle',
			'alert_circle'   => 'warning-circle',
			'info'           => 'info-circle',
			'truck'          => 'delivery-truck',
			'truck_alert'    => 'delivery-truck',
			'mail_check'     => 'mail-in',
			'mail_alert'     => 'mail',
			'phone_alert'    => 'phone-xmark',
			'edit_alert'     => 'edit-pencil',
			'map_pin'        => 'map-pin',
			'clock'          => 'clock',
			'shield_alert'   => 'shield-alert',
			'download_alert' => 'download-circle',
			'close'          => 'xmark',
		);
		$key          = isset( $files[ $icon_name ] ) ? $icon_name : 'info';
		if ( ! array_key_exists( $key, $cache ) ) {
			$path = dirname( __DIR__ ) . '/assets/icons/' . $files[ $key ] . '.svg';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Bundled local SVG from the fixed filename allowlist above.
			$svg           = is_readable( $path ) ? file_get_contents( $path ) : false;
			$cache[ $key ] = is_string( $svg ) ? $svg : '';
		}
		return $cache[ $key ];
	}
}

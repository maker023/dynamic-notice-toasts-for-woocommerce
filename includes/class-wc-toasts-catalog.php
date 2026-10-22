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
				'name'        => __( 'Cart & Basket', 'woocommerce-toasts' ),
				'description' => __( 'Item additions, removals, quantity updates, and cart notices.', 'woocommerce-toasts' ),
				'icon'        => 'cart',
				'color'       => '#10b981', // Emerald green.
				'priority'    => 10,
			),
			self::CAT_COUPON   => array(
				'id'          => self::CAT_COUPON,
				'name'        => __( 'Coupons & Discounts', 'woocommerce-toasts' ),
				'description' => __( 'Coupon application, removal, restrictions, and expiration alerts.', 'woocommerce-toasts' ),
				'icon'        => 'ticket',
				'color'       => '#8b5cf6', // Violet.
				'priority'    => 20,
			),
			self::CAT_CHECKOUT => array(
				'id'          => self::CAT_CHECKOUT,
				'name'        => __( 'Checkout & Orders', 'woocommerce-toasts' ),
				'description' => __( 'Field validation, terms acceptance, shipping selection, and payment errors.', 'woocommerce-toasts' ),
				'icon'        => 'credit-card',
				'color'       => '#ef4444', // Red.
				'priority'    => 30,
			),
			self::CAT_STOCK    => array(
				'id'          => self::CAT_STOCK,
				'name'        => __( 'Stock & Inventory', 'woocommerce-toasts' ),
				'description' => __( 'Out-of-stock items, purchase limits, and backorder notifications.', 'woocommerce-toasts' ),
				'icon'        => 'archive',
				'color'       => '#f59e0b', // Amber.
				'priority'    => 40,
			),
			self::CAT_ACCOUNT  => array(
				'id'          => self::CAT_ACCOUNT,
				'name'        => __( 'Customer & Account', 'woocommerce-toasts' ),
				'description' => __( 'Login errors, registration, password resets, and profile updates.', 'woocommerce-toasts' ),
				'icon'        => 'user',
				'color'       => '#3b82f6', // Blue.
				'priority'    => 50,
			),
			self::CAT_SYSTEM   => array(
				'id'          => self::CAT_SYSTEM,
				'name'        => __( 'System & Store Notices', 'woocommerce-toasts' ),
				'description' => __( 'General alerts, store maintenance, downloads, and uncategorized notices.', 'woocommerce-toasts' ),
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
			'cart_item_added'                  => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Product Added to Cart', 'woocommerce-toasts' ),
				'patterns' => array(
					'/has been added to your cart/i',
					'/have been added to your cart/i',
					'/added to your cart/i',
				),
				'sound'    => 'success_chime',
				'icon'     => 'cart_check',
			),
			'cart_item_removed'                => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Item Removed from Cart', 'woocommerce-toasts' ),
				'patterns' => array(
					'/removed\.\s*(Undo\?)?/i',
					'/item removed/i',
				),
				'sound'    => 'pop',
				'icon'     => 'trash',
			),
			'cart_item_restored'               => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Item Restored to Cart', 'woocommerce-toasts' ),
				'patterns' => array(
					'/restored to cart/i',
					'/item restored/i',
				),
				'sound'    => 'success_chime',
				'icon'     => 'refresh',
			),
			'cart_updated'                     => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Cart Updated', 'woocommerce-toasts' ),
				'patterns' => array(
					'/cart updated/i',
					'/basket updated/i',
				),
				'sound'    => 'gentle_ping',
				'icon'     => 'refresh',
			),
			'cart_empty'                       => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Cart is Empty', 'woocommerce-toasts' ),
				'patterns' => array(
					'/cart is currently empty/i',
					'/basket is currently empty/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'cart_empty',
			),
			'cart_minimum_order'               => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Minimum Order Amount Required', 'woocommerce-toasts' ),
				'patterns' => array(
					'/minimum of.*to place your order/i',
					'/minimum order amount/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'alert_triangle',
			),
			'cart_maximum_order'               => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Maximum Order Amount Exceeded', 'woocommerce-toasts' ),
				'patterns' => array(
					'/exceeds the maximum allowed amount/i',
					'/maximum order amount/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'alert_triangle',
			),
			'cart_quantity_invalid'            => array(
				'category' => self::CAT_CART,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Item Quantity', 'woocommerce-toasts' ),
				'patterns' => array(
					'/minimum quantity for/i',
					'/maximum quantity for/i',
					'/increments of/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'alert_circle',
			),

			// ==========================================
			// 2. COUPON NOTIFICATIONS
			// ==========================================
			'coupon_applied'                   => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Coupon Code Applied', 'woocommerce-toasts' ),
				'patterns' => array(
					'/coupon code applied successfully/i',
					'/coupon applied/i',
				),
				'sound'    => 'celebration',
				'icon'     => 'ticket_check',
			),
			'coupon_removed'                   => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Coupon Code Removed', 'woocommerce-toasts' ),
				'patterns' => array(
					'/coupon code has been removed/i',
					'/coupon removed/i',
				),
				'sound'    => 'pop',
				'icon'     => 'ticket_cross',
			),
			'coupon_not_exist'                 => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Does Not Exist', 'woocommerce-toasts' ),
				'patterns' => array(
					'/coupon.*does not exist/i',
					'/invalid coupon/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'ticket_alert',
			),
			'coupon_already_applied'           => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Already Applied', 'woocommerce-toasts' ),
				'patterns' => array(
					'/coupon code already applied/i',
					'/already been applied/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'ticket_alert',
			),
			'coupon_expired'                   => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Has Expired', 'woocommerce-toasts' ),
				'patterns' => array(
					'/this coupon has expired/i',
					'/coupon has expired/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'clock',
			),
			'coupon_usage_limit_reached'       => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Usage Limit Reached', 'woocommerce-toasts' ),
				'patterns' => array(
					'/coupon usage limit has been reached/i',
					'/usage limit reached/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'shield_alert',
			),
			'coupon_min_spend'                 => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Minimum Spend Not Met', 'woocommerce-toasts' ),
				'patterns' => array(
					'/minimum spend for this coupon is/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'ticket_alert',
			),
			'coupon_max_spend'                 => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Maximum Spend Exceeded', 'woocommerce-toasts' ),
				'patterns' => array(
					'/maximum spend for this coupon is/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'ticket_alert',
			),
			'coupon_individual_use'            => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Cannot Combine Coupons', 'woocommerce-toasts' ),
				'patterns' => array(
					'/cannot be used in conjunction with other coupons/i',
					'/individual use only/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'ticket_alert',
			),
			'coupon_excluded_items'            => array(
				'category' => self::CAT_COUPON,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Coupon Excluded From Items', 'woocommerce-toasts' ),
				'patterns' => array(
					'/not applicable to selected products/i',
					'/not applicable to selected categories/i',
					'/not applicable to sale items/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'ticket_alert',
			),

			// ==========================================
			// 3. CHECKOUT NOTIFICATIONS
			// ==========================================
			'checkout_field_required'          => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Required Field Missing', 'woocommerce-toasts' ),
				'patterns' => array(
					'/is a required field/i',
					'/please enter a.*to continue/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'edit_alert',
			),
			'checkout_invalid_email'           => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Email Address', 'woocommerce-toasts' ),
				'patterns' => array(
					'/please provide a valid email address/i',
					'/enter a valid email/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'mail_alert',
			),
			'checkout_invalid_postcode'        => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Postcode / ZIP', 'woocommerce-toasts' ),
				'patterns' => array(
					'/please enter a valid postcode/i',
					'/valid zip/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'map_pin',
			),
			'checkout_invalid_phone'           => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Invalid Phone Number', 'woocommerce-toasts' ),
				'patterns' => array(
					'/please enter a valid phone number/i',
					'/valid phone/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'phone_alert',
			),
			'checkout_terms_required'          => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Terms Acceptance Required', 'woocommerce-toasts' ),
				'patterns' => array(
					'/read and accept the terms and conditions/i',
					'/terms and conditions/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'shield_alert',
			),
			'checkout_no_shipping_method'      => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Shipping Method Required', 'woocommerce-toasts' ),
				'patterns' => array(
					'/no shipping method has been selected/i',
					'/choose a shipping method/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'truck',
			),
			'checkout_no_shipping_available'   => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'No Shipping Options Available', 'woocommerce-toasts' ),
				'patterns' => array(
					'/no shipping options available/i',
					'/cannot be delivered to/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'truck_alert',
			),
			'checkout_payment_failed'          => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Payment Failed', 'woocommerce-toasts' ),
				'patterns' => array(
					'/payment failed/i',
					'/could not process your payment/i',
					'/declined/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'card_error',
			),
			'checkout_shipping_updated'        => array(
				'category' => self::CAT_CHECKOUT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Shipping Costs Updated', 'woocommerce-toasts' ),
				'patterns' => array(
					'/shipping costs updated/i',
					'/shipping calculated/i',
				),
				'sound'    => 'gentle_ping',
				'icon'     => 'truck',
			),

			// ==========================================
			// 4. STOCK & INVENTORY NOTIFICATIONS
			// ==========================================
			'stock_out_of_stock'               => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Out of Stock', 'woocommerce-toasts' ),
				'patterns' => array(
					'/product is out of stock/i',
					'/out of stock/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'archive_alert',
			),
			'stock_insufficient'               => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Insufficient Stock', 'woocommerce-toasts' ),
				'patterns' => array(
					'/cannot add that amount to the cart.*in stock/i',
					'/not enough stock/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'archive_alert',
			),
			'stock_sold_individually'          => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Item Sold Individually', 'woocommerce-toasts' ),
				'patterns' => array(
					'/cannot add another.*to your cart/i',
					'/sold individually/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'alert_circle',
			),
			'stock_not_purchasable'            => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Product Not Purchasable', 'woocommerce-toasts' ),
				'patterns' => array(
					'/cannot be purchased/i',
					'/not currently available/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'alert_circle',
			),
			'stock_backordered'                => array(
				'category' => self::CAT_STOCK,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Item on Backorder', 'woocommerce-toasts' ),
				'patterns' => array(
					'/item is on backorder/i',
					'/available on backorder/i',
				),
				'sound'    => 'gentle_ping',
				'icon'     => 'clock',
			),

			// ==========================================
			// 5. CUSTOMER & ACCOUNT NOTIFICATIONS
			// ==========================================
			'account_login_failed'             => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Login Error', 'woocommerce-toasts' ),
				'patterns' => array(
					'/password you entered for the username.*is incorrect/i',
					'/unknown username/i',
					'/unknown email/i',
					'/invalid username or email/i',
				),
				'sound'    => 'error_alert',
				'icon'     => 'lock_alert',
			),
			'account_registration_success'     => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Registration Successful', 'woocommerce-toasts' ),
				'patterns' => array(
					'/account was created successfully/i',
					'/registration complete/i',
				),
				'sound'    => 'celebration',
				'icon'     => 'user_check',
			),
			'account_password_reset_sent'      => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Password Reset Email Sent', 'woocommerce-toasts' ),
				'patterns' => array(
					'/password reset email has been sent/i',
					'/check your email for the confirmation link/i',
				),
				'sound'    => 'gentle_ping',
				'icon'     => 'mail_check',
			),
			'account_password_reset_success'   => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Password Reset Complete', 'woocommerce-toasts' ),
				'patterns' => array(
					'/your password has been reset/i',
				),
				'sound'    => 'celebration',
				'icon'     => 'lock_check',
			),
			'account_details_saved'            => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Account Details Updated', 'woocommerce-toasts' ),
				'patterns' => array(
					'/account details changed successfully/i',
					'/profile updated/i',
				),
				'sound'    => 'success_chime',
				'icon'     => 'user_check',
			),
			'account_address_saved'            => array(
				'category' => self::CAT_ACCOUNT,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Address Updated', 'woocommerce-toasts' ),
				'patterns' => array(
					'/address changed successfully/i',
				),
				'sound'    => 'success_chime',
				'icon'     => 'map_pin',
			),

			// ==========================================
			// 6. SYSTEM & STORE NOTIFICATIONS
			// ==========================================
			'system_download_limit'            => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Download Limit Reached', 'woocommerce-toasts' ),
				'patterns' => array(
					'/reached your download limit/i',
				),
				'sound'    => 'warning_soft',
				'icon'     => 'download_alert',
			),
			'system_order_cancelled'           => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Order Cancelled', 'woocommerce-toasts' ),
				'patterns' => array(
					'/order has been cancelled/i',
				),
				'sound'    => 'pop',
				'icon'     => 'alert_circle',
			),
			'system_generic_success'           => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_SUCCESS,
				'title'    => __( 'Action Completed', 'woocommerce-toasts' ),
				'patterns' => array(),
				'sound'    => 'success_chime',
				'icon'     => 'check',
			),
			'system_generic_error'             => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_ERROR,
				'title'    => __( 'Action Failed', 'woocommerce-toasts' ),
				'patterns' => array(),
				'sound'    => 'error_alert',
				'icon'     => 'alert_triangle',
			),
			'system_generic_notice'            => array(
				'category' => self::CAT_SYSTEM,
				'type'     => self::TYPE_INFO,
				'title'    => __( 'Store Notice', 'woocommerce-toasts' ),
				'patterns' => array(),
				'sound'    => 'gentle_ping',
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
			'clean_html' => $clean_html,
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
			'id'          => uniqid( 'wct_' ),
			'case_id'     => $matched_case_id,
			'category'    => $category_id,
			'cat_name'    => $category['name'],
			'type'        => ! empty( $matched_case['type'] ) ? $matched_case['type'] : $normalized_type,
			'title'       => $matched_case['title'],
			'message'     => $text,
			'raw_html'    => $parsed['clean_html'],
			'action'      => $parsed['action'],
			'icon'        => ! empty( $matched_case['icon'] ) ? $matched_case['icon'] : 'info',
			'sound'       => ! empty( $matched_case['sound'] ) ? $matched_case['sound'] : 'gentle_ping',
			'accent'      => $category['color'],
			'timestamp'   => time(),
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
		$icons = array(
			'check'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
			'cart'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
			'cart_check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><path d="m9 11 2 2 4-4"/></svg>',
			'cart_empty'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><path d="m11 9 4 4m0-4-4 4"/></svg>',
			'trash'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
			'refresh'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>',
			'ticket'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>',
			'ticket_check'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="m9 12 2 2 4-4"/></svg>',
			'ticket_cross'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="m10 10 4 4m0-4-4 4"/></svg>',
			'ticket_alert'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
			'credit-card'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
			'card_error'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/><line x1="16" y1="14" x2="16" y2="16"/></svg>',
			'archive'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect width="22" height="5" x="1" y="3" rx="1"/><line x1="10" x2="14" y1="12" y2="12"/></svg>',
			'archive_alert'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect width="22" height="5" x="1" y="3" rx="1"/><line x1="12" y1="11" x2="12" y2="15"/><circle cx="12" cy="18" r="0.5"/></svg>',
			'user'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
			'user_check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>',
			'lock_alert'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><line x1="12" y1="15" x2="12" y2="17"/></svg>',
			'lock_check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/><path d="m16 19 2 2 4-4"/></svg>',
			'bell'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
			'alert_triangle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
			'alert_circle'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
			'info'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
			'truck'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',
			'truck_alert'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/><line x1="9" y1="9" x2="9" y2="13"/><circle cx="9" cy="15" r="0.5"/></svg>',
			'mail_check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h9"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><path d="m16 19 2 2 4-4"/></svg>',
			'mail_alert'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h9"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/><line x1="18" y1="15" x2="18" y2="19"/><circle cx="18" cy="21" r="0.5"/></svg>',
			'phone_alert'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/><line x1="18" y1="2" x2="18" y2="6"/><circle cx="18" cy="8" r="0.5"/></svg>',
			'edit_alert'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
			'map_pin'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
			'clock'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
			'shield_alert'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
			'close'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
		);

		return isset( $icons[ $icon_name ] ) ? $icons[ $icon_name ] : $icons['info'];
	}
}

<?php
/**
 * Pro Features Bridge and Extensibility API
 *
 * Provides extension hooks and bridges for the future Dynamic Notice Toasts for WooCommerce Pro add-on.
 * Designed to be 100% compliant with WordPress.org guidelines.
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts_Pro_Bridge
 */
class WC_Toasts_Pro_Bridge {

	/**
	 * Check whether the Pro version is currently active.
	 *
	 * @return bool
	 */
	public static function is_pro_active() {
		return apply_filters( 'wc_toasts_is_pro_active', false );
	}

	/**
	 * Get list of available themes (Free + Pro extensible).
	 *
	 * @return array
	 */
	public static function get_themes() {
		$themes = array(
			'editorial-monolith' => array(
				'label'       => __( 'Warm Paper', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Warm white surface, dark text, and a restrained status color.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'swiss-basalt'       => array(
				'label'       => __( 'Ink', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Deep charcoal surface with clear, soft white typography.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'nordic-gallery'     => array(
				'label'       => __( 'Gallery White', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Cool white surface and quiet blue-gray text.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'modern-light'       => array(
				'label'       => __( 'Modern Light', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Simple white surface with the shared notice layout.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'glassmorphism'      => array(
				'label'       => __( 'Soft Gray', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Muted gray surface with restrained contrast.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'dark-slate'         => array(
				'label'       => __( 'Dark', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Charcoal surface with accessible text and status colors.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'minimal'            => array(
				'label'       => __( 'Minimal', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'White surface and a lighter shadow.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'woocommerce-purple' => array(
				'label'       => __( 'Plum', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'A subtle purple tint inspired by WooCommerce.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
			'custom'             => array(
				'label'       => __( 'Custom Color Palette', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Full control over background, text, accent, and border colors.', 'dynamic-notice-toasts-for-woocommerce' ),
				'pro'         => false,
			),
		);

		return apply_filters( 'wc_toasts_themes', $themes );
	}

	/**
	 * Get pro features list for admin presentation.
	 *
	 * @return array
	 */
	public static function get_pro_features() {
		return array(
			array(
				'title'       => __( 'In-Toast Cross-Sells & Recommendations', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Display a "Frequently Bought Together" mini item right inside the "Added to Cart" toast!', 'dynamic-notice-toasts-for-woocommerce' ),
				'badge'       => __( 'Revenue Booster', 'dynamic-notice-toasts-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Toast Analytics & Conversion Tracking', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Track toast impressions, action button click-through rates, and checkout recovery metrics.', 'dynamic-notice-toasts-for-woocommerce' ),
				'badge'       => __( 'Analytics', 'dynamic-notice-toasts-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Free Shipping Progress Meter', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Show a live bar in the toast: "Add $15 more to unlock Free Shipping!" on every add-to-cart.', 'dynamic-notice-toasts-for-woocommerce' ),
				'badge'       => __( 'High AOV', 'dynamic-notice-toasts-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Per-Category & Per-Role Display Rules', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Trigger exclusive toast styles for Wholesale / B2B buyers or VIP customer roles.', 'dynamic-notice-toasts-for-woocommerce' ),
				'badge'       => __( 'Targeting', 'dynamic-notice-toasts-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Polylang & WPML Multilingual Mapping', 'dynamic-notice-toasts-for-woocommerce' ),
				'description' => __( 'Translate custom toast button texts and titles automatically across multi-currency and multi-language stores.', 'dynamic-notice-toasts-for-woocommerce' ),
				'badge'       => __( 'Global', 'dynamic-notice-toasts-for-woocommerce' ),
			),
		);
	}
}

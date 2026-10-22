<?php
/**
 * Pro Features Bridge and Extensibility API
 *
 * Provides extension hooks and bridges for the future WooCommerce Dynamic Notice Toasts Pro add-on.
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
			'modern-light'       => array(
				'label'       => __( 'Modern Light (Default)', 'woocommerce-toasts' ),
				'description' => __( 'Clean white background, subtle drop shadow, sleek border accent.', 'woocommerce-toasts' ),
				'pro'         => false,
			),
			'glassmorphism'      => array(
				'label'       => __( 'Frosted Glass (Glassmorphism)', 'woocommerce-toasts' ),
				'description' => __( 'Translucent backdrop blur with subtle high-contrast borders.', 'woocommerce-toasts' ),
				'pro'         => false,
			),
			'dark-slate'         => array(
				'label'       => __( 'Dark Slate', 'woocommerce-toasts' ),
				'description' => __( 'Deep modern slate background, high-contrast text and glowing badges.', 'woocommerce-toasts' ),
				'pro'         => false,
			),
			'minimal'            => array(
				'label'       => __( 'Minimalist Clean', 'woocommerce-toasts' ),
				'description' => __( 'Flat design with crisp typography and subtle left-accent line.', 'woocommerce-toasts' ),
				'pro'         => false,
			),
			'woocommerce-purple' => array(
				'label'       => __( 'WooCommerce Purple', 'woocommerce-toasts' ),
				'description' => __( 'Vibrant WooCommerce brand palette with distinct action styling.', 'woocommerce-toasts' ),
				'pro'         => false,
			),
			'custom'             => array(
				'label'       => __( 'Custom Color Palette', 'woocommerce-toasts' ),
				'description' => __( 'Full control over background, text, accent, and border colors.', 'woocommerce-toasts' ),
				'pro'         => false,
			),
			// Pro preview themes.
			'neon-glow'          => array(
				'label'       => __( 'Cyberpunk Neon Glow (PRO)', 'woocommerce-toasts' ),
				'description' => __( 'Vibrant glowing border pulses with dark ambient lighting.', 'woocommerce-toasts' ),
				'pro'         => true,
			),
			'luxury-gold'        => array(
				'label'       => __( 'Luxury Obsidian & Gold (PRO)', 'woocommerce-toasts' ),
				'description' => __( 'Elegance for premium jewelry, watches, and fashion boutiques.', 'woocommerce-toasts' ),
				'pro'         => true,
			),
		);

		return apply_filters( 'wc_toasts_themes', $themes );
	}

	/**
	 * Get list of available sounds.
	 *
	 * @return array
	 */
	public static function get_sounds() {
		$sounds = array(
			'gentle_chime' => array(
				'label' => __( 'Gentle Chime (Web Audio)', 'woocommerce-toasts' ),
				'pro'   => false,
			),
			'modern_ping'  => array(
				'label' => __( 'Modern Ping (Web Audio)', 'woocommerce-toasts' ),
				'pro'   => false,
			),
			'crisp_pop'    => array(
				'label' => __( 'Crisp Pop (Web Audio)', 'woocommerce-toasts' ),
				'pro'   => false,
			),
			'bell_ding'    => array(
				'label' => __( 'Bell Ding (Web Audio)', 'woocommerce-toasts' ),
				'pro'   => false,
			),
			'subtle_click' => array(
				'label' => __( 'Subtle Haptic Click (Web Audio)', 'woocommerce-toasts' ),
				'pro'   => false,
			),
			// Pro preview sounds.
			'custom_upload' => array(
				'label' => __( 'Upload Custom MP3 / WAV Audio (PRO)', 'woocommerce-toasts' ),
				'pro'   => true,
			),
		);

		return apply_filters( 'wc_toasts_sounds', $sounds );
	}

	/**
	 * Get pro features list for admin presentation.
	 *
	 * @return array
	 */
	public static function get_pro_features() {
		return array(
			array(
				'title'       => __( 'Custom MP3 / WAV Audio Uploads', 'woocommerce-toasts' ),
				'description' => __( 'Upload your brand\'s custom sound effects or pick from 25+ studio recorded audio chimes.', 'woocommerce-toasts' ),
				'badge'       => __( 'Audio Studio', 'woocommerce-toasts' ),
			),
			array(
				'title'       => __( 'In-Toast Cross-Sells & Recommendations', 'woocommerce-toasts' ),
				'description' => __( 'Display a "Frequently Bought Together" mini item right inside the "Added to Cart" toast!', 'woocommerce-toasts' ),
				'badge'       => __( 'Revenue Booster', 'woocommerce-toasts' ),
			),
			array(
				'title'       => __( 'Toast Analytics & Conversion Tracking', 'woocommerce-toasts' ),
				'description' => __( 'Track toast impressions, action button click-through rates, and checkout recovery metrics.', 'woocommerce-toasts' ),
				'badge'       => __( 'Analytics', 'woocommerce-toasts' ),
			),
			array(
				'title'       => __( 'Free Shipping Progress Meter', 'woocommerce-toasts' ),
				'description' => __( 'Show a live bar in the toast: "Add $15 more to unlock Free Shipping!" on every add-to-cart.', 'woocommerce-toasts' ),
				'badge'       => __( 'High AOV', 'woocommerce-toasts' ),
			),
			array(
				'title'       => __( 'Per-Category & Per-Role Display Rules', 'woocommerce-toasts' ),
				'description' => __( 'Trigger exclusive toast styles for Wholesale / B2B buyers or VIP customer roles.', 'woocommerce-toasts' ),
				'badge'       => __( 'Targeting', 'woocommerce-toasts' ),
			),
			array(
				'title'       => __( 'Polylang & WPML Multilingual Mapping', 'woocommerce-toasts' ),
				'description' => __( 'Translate custom toast button texts and titles automatically across multi-currency and multi-language stores.', 'woocommerce-toasts' ),
				'badge'       => __( 'Global', 'woocommerce-toasts' ),
			),
		);
	}
}

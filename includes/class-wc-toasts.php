<?php
/**
 * Main WooCommerce Dynamic Notice Toasts Orchestrator Class
 *
 * @package WooCommerce_Toasts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Toasts
 */
class WC_Toasts {

	/**
	 * Single instance of the class.
	 *
	 * @var WC_Toasts|null
	 */
	protected static $instance = null;

	/**
	 * Interceptor instance.
	 *
	 * @var WC_Toasts_Interceptor
	 */
	public $interceptor;

	/**
	 * AJAX handler instance.
	 *
	 * @var WC_Toasts_Ajax
	 */
	public $ajax;

	/**
	 * Main instance accessor.
	 *
	 * @return WC_Toasts
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-i18n.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-catalog.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-settings.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-pro-bridge.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-interceptor.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-ajax.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Internationalization.
		$i18n = new WC_Toasts_I18n();
		add_action( 'plugins_loaded', array( $i18n, 'load_plugin_textdomain' ) );

		// Instantiate notice interceptor and AJAX handler.
		$this->interceptor = new WC_Toasts_Interceptor();
		$this->ajax        = new WC_Toasts_Ajax();

		// Admin menus and settings.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 65 );
			add_action( 'admin_init', array( $this, 'save_admin_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_filter( 'plugin_action_links_' . WC_TOASTS_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
		}
	}

	/**
	 * Register WooCommerce submenu for Notice Toasts.
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Notice Toasts', 'woocommerce-toasts' ),
			__( 'Notice Toasts', 'woocommerce-toasts' ),
			'manage_woocommerce',
			'wc-toasts-settings',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Add direct links to the Plugins page.
	 *
	 * @param array $links Array of plugin links.
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		$settings_url  = admin_url( 'admin.php?page=wc-toasts-settings' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'woocommerce-toasts' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Enqueue admin scripts and stylesheets on the plugin settings screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'woocommerce_page_wc-toasts-settings' !== $hook ) {
			return;
		}

		// WP Color Picker.
		wp_enqueue_style( 'wp-color-picker' );

		// Enqueue frontend styles for accurate live preview in stage.
		wp_enqueue_style(
			'wc-toasts-frontend',
			WC_TOASTS_PLUGIN_URL . 'assets/css/wc-toasts-frontend.css',
			array(),
			WC_TOASTS_VERSION
		);

		// Admin styles.
		wp_enqueue_style(
			'wc-toasts-admin',
			WC_TOASTS_PLUGIN_URL . 'assets/css/wc-toasts-admin.css',
			array( 'wp-color-picker', 'wc-toasts-frontend' ),
			WC_TOASTS_VERSION
		);

		// Admin script.
		wp_enqueue_script(
			'wc-toasts-admin',
			WC_TOASTS_PLUGIN_URL . 'assets/js/wc-toasts-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			WC_TOASTS_VERSION,
			true
		);

		// Prepare icons dictionary for the admin live preview.
		$icons_map = array();
		foreach ( array( 'check', 'cart', 'cart_check', 'cart_empty', 'trash', 'refresh', 'ticket', 'ticket_check', 'ticket_cross', 'ticket_alert', 'credit-card', 'card_error', 'archive', 'archive_alert', 'user', 'user_check', 'lock_alert', 'lock_check', 'bell', 'alert_triangle', 'alert_circle', 'info', 'truck', 'truck_alert', 'mail_check', 'mail_alert', 'phone_alert', 'edit_alert', 'map_pin', 'clock', 'shield_alert', 'close' ) as $icon_name ) {
			$icons_map[ $icon_name ] = WC_Toasts_Catalog::get_svg_icon( $icon_name );
		}

		// Localize admin data.
		wp_localize_script(
			'wc-toasts-admin',
			'wc_toasts_admin_params',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wc_toasts_admin_nonce' ),
				'settings'    => WC_Toasts_Settings::get_all(),
				'categories'  => WC_Toasts_Catalog::get_categories(),
				'icons'       => $icons_map,
				'i18n'        => array(
					'saved'         => __( 'Settings saved successfully!', 'woocommerce-toasts' ),
					'reset_confirm' => __( 'Are you sure you want to reset all toast settings to factory defaults?', 'woocommerce-toasts' ),
					'reset_done'    => __( 'Settings have been reset to default values.', 'woocommerce-toasts' ),
					'testing'       => __( 'Triggering test toast...', 'woocommerce-toasts' ),
				),
			)
		);
	}

	/**
	 * Process settings form submission with security verification and sanitization.
	 */
	public function save_admin_settings() {
		if ( ! isset( $_POST['wc_toasts_save_settings'] ) ) {
			return;
		}

		check_admin_referer( 'wc_toasts_save_settings_action', 'wc_toasts_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woocommerce-toasts' ) );
		}

		$raw_input         = isset( $_POST['wc_toasts'] ) ? (array) $_POST['wc_toasts'] : array();
		$sanitized_settings = WC_Toasts_Settings::sanitize_settings( $raw_input );

		update_option( WC_Toasts_Settings::OPTION_KEY, $sanitized_settings );

		// Redirect with success notice.
		$redirect_url = add_query_arg(
			array(
				'page'    => 'wc-toasts-settings',
				'updated' => 'true',
				'tab'     => isset( $_POST['current_tab'] ) ? sanitize_key( $_POST['current_tab'] ) : 'general',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render the modern admin settings dashboard.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings    = WC_Toasts_Settings::get_all();
		$categories  = WC_Toasts_Catalog::get_categories();
		$cases       = WC_Toasts_Catalog::get_cases();
		$themes      = WC_Toasts_Pro_Bridge::get_themes();
		$sounds      = WC_Toasts_Pro_Bridge::get_sounds();
		$pro_features = WC_Toasts_Pro_Bridge::get_pro_features();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		// Count cases per category.
		$category_counts = array();
		foreach ( $cases as $c_def ) {
			$cat = isset( $c_def['category'] ) ? $c_def['category'] : 'system';
			$category_counts[ $cat ] = ( isset( $category_counts[ $cat ] ) ? $category_counts[ $cat ] : 0 ) + 1;
		}

		$tabs = array(
			'general'    => array(
				'title' => __( 'General Settings', 'woocommerce-toasts' ),
				'icon'  => 'dashicons-admin-generic',
			),
			'appearance' => array(
				'title' => __( 'Appearance & Styling', 'woocommerce-toasts' ),
				'icon'  => 'dashicons-art',
			),
			'behavior'   => array(
				'title' => __( 'Behavior & Sound', 'woocommerce-toasts' ),
				'icon'  => 'dashicons-controls-volumeon',
			),
			'catalog'    => array(
				'title' => __( 'Notice Catalog (' . count( $cases ) . ' Cases)', 'woocommerce-toasts' ),
				'icon'  => 'dashicons-list-view',
			),
			'advanced'   => array(
				'title' => __( 'Advanced & Custom CSS', 'woocommerce-toasts' ),
				'icon'  => 'dashicons-admin-tools',
			),
			'pro'        => array(
				'title' => __( 'Pro Features', 'woocommerce-toasts' ),
				'icon'  => 'dashicons-star-filled',
			),
		);
		?>
		<div class="wrap wc-toasts-admin-wrap">
			<!-- Top Header -->
			<div class="wc-toasts-header">
				<div class="wc-toasts-header-brand">
					<div class="wc-toasts-logo-icon">
						<?php echo WC_Toasts_Catalog::get_svg_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<div>
						<h1><?php esc_html_e( 'WooCommerce Dynamic Notice Toasts', 'woocommerce-toasts' ); ?></h1>
						<p class="wc-toasts-subtitle">
							<?php esc_html_e( 'Transform default static WooCommerce notices into modern, animated dynamic toasts.', 'woocommerce-toasts' ); ?>
							<span class="wc-toasts-version-badge">v<?php echo esc_html( WC_TOASTS_VERSION ); ?></span>
						</p>
					</div>
				</div>
				<div class="wc-toasts-header-actions">
					<button type="button" class="button button-secondary" id="wc-toasts-btn-test-chime">
						<span class="dashicons dashicons-controls-volumeon"></span>
						<?php esc_html_e( 'Test Sound', 'woocommerce-toasts' ); ?>
					</button>
					<button type="button" class="button button-primary" id="wc-toasts-btn-quick-preview">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Test Toast', 'woocommerce-toasts' ); ?>
					</button>
				</div>
			</div>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible wc-toasts-admin-alert">
					<p><strong><?php esc_html_e( 'Success:', 'woocommerce-toasts' ); ?></strong> <?php esc_html_e( 'Settings saved successfully.', 'woocommerce-toasts' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="wc-toasts-layout">
				<!-- Left Main Settings Area -->
				<div class="wc-toasts-main">
					<!-- Nav Tabs -->
					<nav class="nav-tab-wrapper wc-toasts-nav-tabs">
						<?php foreach ( $tabs as $tab_id => $tab_data ) : ?>
							<a href="#<?php echo esc_attr( $tab_id ); ?>"
							   class="nav-tab wc-toasts-tab-link <?php echo ( $current_tab === $tab_id ) ? 'nav-tab-active' : ''; ?>"
							   data-tab="<?php echo esc_attr( $tab_id ); ?>">
								<span class="dashicons <?php echo esc_attr( $tab_data['icon'] ); ?>"></span>
								<?php echo esc_html( $tab_data['title'] ); ?>
							</a>
						<?php endforeach; ?>
					</nav>

					<form method="post" action="" id="wc-toasts-settings-form">
						<?php wp_nonce_field( 'wc_toasts_save_settings_action', 'wc_toasts_nonce' ); ?>
						<input type="hidden" name="current_tab" id="wc-toasts-current-tab" value="<?php echo esc_attr( $current_tab ); ?>">

						<!-- Tab 1: General -->
						<div class="wc-toasts-tab-content <?php echo ( 'general' === $current_tab ) ? 'active' : ''; ?>" id="tab-general">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Core Engine Settings', 'woocommerce-toasts' ); ?></h2>
								<p class="description"><?php esc_html_e( 'Control how WooCommerce notifications are intercepted and replaced.', 'woocommerce-toasts' ); ?></p>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Enable Toast System', 'woocommerce-toasts' ); ?></th>
										<td>
											<label class="wc-toasts-switch">
												<input type="checkbox" name="wc_toasts[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?>>
												<span class="slider"></span>
											</label>
											<p class="description"><?php esc_html_e( 'Turn dynamic notice toasts on or off across your store.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Hide Default WooCommerce Banners', 'woocommerce-toasts' ); ?></th>
										<td>
											<label class="wc-toasts-switch">
												<input type="checkbox" name="wc_toasts[hide_default_notices]" value="1" <?php checked( $settings['hide_default_notices'], 1 ); ?>>
												<span class="slider"></span>
											</label>
											<p class="description"><?php esc_html_e( 'Suppresses the default static alert boxes (.woocommerce-message, .woocommerce-error, .woocommerce-info) to eliminate duplicate notices and layout jumps.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Screen Position', 'woocommerce-toasts' ); ?></th>
										<td>
											<div class="wc-toasts-position-grid">
												<?php
												$positions = array(
													'top-left'      => __( 'Top Left', 'woocommerce-toasts' ),
													'top-center'    => __( 'Top Center', 'woocommerce-toasts' ),
													'top-right'     => __( 'Top Right (Recommended)', 'woocommerce-toasts' ),
													'bottom-left'   => __( 'Bottom Left', 'woocommerce-toasts' ),
													'bottom-center' => __( 'Bottom Center', 'woocommerce-toasts' ),
													'bottom-right'  => __( 'Bottom Right', 'woocommerce-toasts' ),
												);
												foreach ( $positions as $pos_key => $pos_label ) :
													?>
													<label class="wc-toasts-pos-card <?php echo ( $settings['position'] === $pos_key ) ? 'selected' : ''; ?>">
														<input type="radio" name="wc_toasts[position]" value="<?php echo esc_attr( $pos_key ); ?>" <?php checked( $settings['position'], $pos_key ); ?>>
														<span class="pos-dot <?php echo esc_attr( $pos_key ); ?>"></span>
														<span class="pos-label"><?php echo esc_html( $pos_label ); ?></span>
													</label>
												<?php endforeach; ?>
											</div>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Design Theme / Preset', 'woocommerce-toasts' ); ?></th>
										<td>
											<select name="wc_toasts[theme]" id="wc-toasts-theme-select" class="regular-text">
												<?php foreach ( $themes as $t_key => $t_info ) : ?>
													<option value="<?php echo esc_attr( $t_key ); ?>" <?php selected( $settings['theme'], $t_key ); ?> <?php echo ( ! empty( $t_info['pro'] ) && ! WC_Toasts_Pro_Bridge::is_pro_active() ) ? 'disabled' : ''; ?>>
														<?php echo esc_html( $t_info['label'] ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description"><?php esc_html_e( 'Select a pre-designed modern aesthetic or choose Custom for your own store branding.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Max Visible Toasts', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[max_toasts]" min="1" max="10" step="1" value="<?php echo esc_attr( $settings['max_toasts'] ); ?>" class="small-text">
											<p class="description"><?php esc_html_e( 'Limits the number of simultaneous toasts on screen before older ones are pushed off.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Newest on Top', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[newest_on_top]" value="1" <?php checked( $settings['newest_on_top'], 1 ); ?>>
												<?php esc_html_e( 'Insert new toasts at the top of the stack instead of appending to the bottom', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Prevent Duplicate Spam', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[deduplication]" value="1" <?php checked( $settings['deduplication'], 1 ); ?>>
												<?php esc_html_e( 'Prevents rapid repeated clicks on "Add to Cart" from flooding the screen with duplicate toasts', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<!-- Tab 2: Appearance -->
						<div class="wc-toasts-tab-content <?php echo ( 'appearance' === $current_tab ) ? 'active' : ''; ?>" id="tab-appearance">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Styling, Dimensions & Animations', 'woocommerce-toasts' ); ?></h2>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Corner Radius (px)', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="range" name="wc_toasts[border_radius]" id="wc-toasts-radius-range" min="0" max="28" step="2" value="<?php echo esc_attr( $settings['border_radius'] ); ?>">
											<span id="wc-toasts-radius-val"><?php echo esc_html( $settings['border_radius'] ); ?>px</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Drop Shadow', 'woocommerce-toasts' ); ?></th>
										<td>
											<select name="wc_toasts[box_shadow]" id="wc-toasts-shadow-select">
												<option value="none" <?php selected( $settings['box_shadow'], 'none' ); ?>><?php esc_html_e( 'None (Flat)', 'woocommerce-toasts' ); ?></option>
												<option value="subtle" <?php selected( $settings['box_shadow'], 'subtle' ); ?>><?php esc_html_e( 'Subtle & Soft', 'woocommerce-toasts' ); ?></option>
												<option value="medium" <?php selected( $settings['box_shadow'], 'medium' ); ?>><?php esc_html_e( 'Medium Modern (Default)', 'woocommerce-toasts' ); ?></option>
												<option value="elevated" <?php selected( $settings['box_shadow'], 'elevated' ); ?>><?php esc_html_e( 'Elevated Float', 'woocommerce-toasts' ); ?></option>
												<option value="glow" <?php selected( $settings['box_shadow'], 'glow' ); ?>><?php esc_html_e( 'Ambient Colored Glow', 'woocommerce-toasts' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Toast Width (px)', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[toast_width]" min="280" max="550" step="10" value="<?php echo esc_attr( $settings['toast_width'] ); ?>" class="small-text"> px
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Entrance Animation', 'woocommerce-toasts' ); ?></th>
										<td>
											<select name="wc_toasts[animation_in]" id="wc-toasts-anim-in">
												<option value="slide-in" <?php selected( $settings['animation_in'], 'slide-in' ); ?>><?php esc_html_e( 'Smooth Slide In', 'woocommerce-toasts' ); ?></option>
												<option value="fade-in" <?php selected( $settings['animation_in'], 'fade-in' ); ?>><?php esc_html_e( 'Fade In', 'woocommerce-toasts' ); ?></option>
												<option value="bounce-in" <?php selected( $settings['animation_in'], 'bounce-in' ); ?>><?php esc_html_e( 'Dynamic Bounce', 'woocommerce-toasts' ); ?></option>
												<option value="zoom-in" <?php selected( $settings['animation_in'], 'zoom-in' ); ?>><?php esc_html_e( 'Zoom Pop In', 'woocommerce-toasts' ); ?></option>
												<option value="flip-in" <?php selected( $settings['animation_in'], 'flip-in' ); ?>><?php esc_html_e( '3D Flip In', 'woocommerce-toasts' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Exit Animation', 'woocommerce-toasts' ); ?></th>
										<td>
											<select name="wc_toasts[animation_out]" id="wc-toasts-anim-out">
												<option value="fade-out" <?php selected( $settings['animation_out'], 'fade-out' ); ?>><?php esc_html_e( 'Fade Out', 'woocommerce-toasts' ); ?></option>
												<option value="slide-out" <?php selected( $settings['animation_out'], 'slide-out' ); ?>><?php esc_html_e( 'Slide Out', 'woocommerce-toasts' ); ?></option>
												<option value="zoom-out" <?php selected( $settings['animation_out'], 'zoom-out' ); ?>><?php esc_html_e( 'Zoom Shrink', 'woocommerce-toasts' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Show Status & Category Icons', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[show_icons]" value="1" <?php checked( $settings['show_icons'], 1 ); ?>>
												<?php esc_html_e( 'Display clean SVG vector icons inside toasts', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
								</table>

								<h3 style="margin-top:30px;"><?php esc_html_e( 'Custom Color Accents', 'woocommerce-toasts' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Active when Theme is set to "Custom Color Palette" or for accent highlights.', 'woocommerce-toasts' ); ?></p>

								<div class="wc-toasts-colors-grid">
									<div class="wc-toasts-color-box">
										<h4><?php esc_html_e( 'Success Notices', 'woocommerce-toasts' ); ?></h4>
										<label><?php esc_html_e( 'Accent Color', 'woocommerce-toasts' ); ?></label>
										<input type="text" name="wc_toasts[color_success_accent]" value="<?php echo esc_attr( $settings['color_success_accent'] ); ?>" class="wc-toasts-color-picker">
										<label><?php esc_html_e( 'Background', 'woocommerce-toasts' ); ?></label>
										<input type="text" name="wc_toasts[color_success_bg]" value="<?php echo esc_attr( $settings['color_success_bg'] ); ?>" class="wc-toasts-color-picker">
									</div>
									<div class="wc-toasts-color-box">
										<h4><?php esc_html_e( 'Error Notices', 'woocommerce-toasts' ); ?></h4>
										<label><?php esc_html_e( 'Accent Color', 'woocommerce-toasts' ); ?></label>
										<input type="text" name="wc_toasts[color_error_accent]" value="<?php echo esc_attr( $settings['color_error_accent'] ); ?>" class="wc-toasts-color-picker">
										<label><?php esc_html_e( 'Background', 'woocommerce-toasts' ); ?></label>
										<input type="text" name="wc_toasts[color_error_bg]" value="<?php echo esc_attr( $settings['color_error_bg'] ); ?>" class="wc-toasts-color-picker">
									</div>
									<div class="wc-toasts-color-box">
										<h4><?php esc_html_e( 'Info / Store Notices', 'woocommerce-toasts' ); ?></h4>
										<label><?php esc_html_e( 'Accent Color', 'woocommerce-toasts' ); ?></label>
										<input type="text" name="wc_toasts[color_info_accent]" value="<?php echo esc_attr( $settings['color_info_accent'] ); ?>" class="wc-toasts-color-picker">
										<label><?php esc_html_e( 'Background', 'woocommerce-toasts' ); ?></label>
										<input type="text" name="wc_toasts[color_info_bg]" value="<?php echo esc_attr( $settings['color_info_bg'] ); ?>" class="wc-toasts-color-picker">
									</div>
								</div>
							</div>
						</div>

						<!-- Tab 3: Behavior & Sound -->
						<div class="wc-toasts-tab-content <?php echo ( 'behavior' === $current_tab ) ? 'active' : ''; ?>" id="tab-behavior">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Timing, Interactions & Web Audio Synthesizer', 'woocommerce-toasts' ); ?></h2>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Success Toast Duration (ms)', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[duration_success]" step="500" min="1000" max="30000" value="<?php echo esc_attr( $settings['duration_success'] ); ?>" class="regular-text">
											<p class="description"><?php esc_html_e( 'Duration in milliseconds before success toasts auto-dismiss (e.g. 4500 = 4.5 seconds).', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Error Toast Duration (ms)', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[duration_error]" step="500" min="0" max="30000" value="<?php echo esc_attr( $settings['duration_error'] ); ?>" class="regular-text">
											<p class="description"><?php esc_html_e( 'Set to 0 if you want error notices (e.g. checkout issues) to stay persistent until user manually clicks close.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Info Notice Duration (ms)', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[duration_info]" step="500" min="1000" max="30000" value="<?php echo esc_attr( $settings['duration_info'] ); ?>" class="regular-text">
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Countdown Progress Bar', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[show_progress_bar]" value="1" <?php checked( $settings['show_progress_bar'], 1 ); ?>>
												<?php esc_html_e( 'Display animated progress line indicating remaining visible time', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Pause on Hover', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[pause_on_hover]" value="1" <?php checked( $settings['pause_on_hover'], 1 ); ?>>
												<?php esc_html_e( 'Pause the auto-dismiss timer whenever user hovers mouse over the toast', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Close "X" Button', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[show_close_button]" value="1" <?php checked( $settings['show_close_button'], 1 ); ?>>
												<?php esc_html_e( 'Show clickable dismiss button on every toast', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Audio Notifications', 'woocommerce-toasts' ); ?></th>
										<td>
											<label class="wc-toasts-switch">
												<input type="checkbox" name="wc_toasts[sound_enabled]" value="1" <?php checked( $settings['sound_enabled'], 1 ); ?>>
												<span class="slider"></span>
											</label>
											<p class="description"><?php esc_html_e( 'Synthesizes clean, high-fidelity notification chimes using standard HTML5 Web Audio API. 100% reliable with zero external audio assets to load.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Sound Theme', 'woocommerce-toasts' ); ?></th>
										<td>
											<select name="wc_toasts[sound_theme]" id="wc-toasts-sound-theme">
												<?php foreach ( $sounds as $s_key => $s_data ) : ?>
													<option value="<?php echo esc_attr( $s_key ); ?>" <?php selected( $settings['sound_theme'], $s_key ); ?> <?php echo ( ! empty( $s_data['pro'] ) && ! WC_Toasts_Pro_Bridge::is_pro_active() ) ? 'disabled' : ''; ?>>
														<?php echo esc_html( $s_data['label'] ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Volume (%)', 'woocommerce-toasts' ); ?></th>
										<td>
											<input type="range" name="wc_toasts[sound_volume]" id="wc-toasts-vol-range" min="5" max="100" step="5" value="<?php echo esc_attr( $settings['sound_volume'] ); ?>">
											<span id="wc-toasts-vol-val"><?php echo esc_html( $settings['sound_volume'] ); ?>%</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Mobile Bottom Dock', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[mobile_bottom]" value="1" <?php checked( $settings['mobile_bottom'], 1 ); ?>>
												<?php esc_html_e( 'Automatically dock toasts at the bottom on mobile screens (< 640px) for optimal thumb reachability', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<!-- Tab 4: Notice Catalog & Categories -->
						<div class="wc-toasts-tab-content <?php echo ( 'catalog' === $current_tab ) ? 'active' : ''; ?>" id="tab-catalog">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'WooCommerce Notification Catalog', 'woocommerce-toasts' ); ?></h2>
								<p class="description">
									<?php
									/* translators: %d: number of cases */
									printf( esc_html__( 'Our intelligent classifier inspects incoming notices and categorizes them across %d specific WooCommerce cases.', 'woocommerce-toasts' ), count( $cases ) );
									?>
								</p>

								<div class="wc-toasts-cat-list">
									<?php foreach ( $categories as $cat_id => $cat_data ) : ?>
										<div class="wc-toasts-cat-item">
											<div class="cat-header">
												<div class="cat-info">
													<div class="cat-badge" style="background-color: <?php echo esc_attr( $cat_data['color'] ); ?>;">
														<?php echo WC_Toasts_Catalog::get_svg_icon( $cat_data['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													</div>
													<div>
														<h4><?php echo esc_html( $cat_data['name'] ); ?></h4>
														<p><?php echo esc_html( $cat_data['description'] ); ?></p>
													</div>
												</div>
												<div class="cat-meta">
													<span class="cat-case-count">
														<?php echo isset( $category_counts[ $cat_id ] ) ? esc_html( $category_counts[ $cat_id ] ) : 0; ?> <?php esc_html_e( 'Cases', 'woocommerce-toasts' ); ?>
													</span>
													<label class="wc-toasts-switch">
														<input type="checkbox" name="wc_toasts[enable_cat_<?php echo esc_attr( $cat_id ); ?>]" value="1" <?php checked( $settings[ 'enable_cat_' . $cat_id ], 1 ); ?>>
														<span class="slider"></span>
													</label>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="wc-toasts-catalog-details">
									<h3><?php esc_html_e( 'Catalog Cases Breakdown', 'woocommerce-toasts' ); ?></h3>
									<div class="wc-toasts-table-scroll">
										<table class="widefat striped">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Category', 'woocommerce-toasts' ); ?></th>
													<th><?php esc_html_e( 'Case Identifier', 'woocommerce-toasts' ); ?></th>
													<th><?php esc_html_e( 'Type', 'woocommerce-toasts' ); ?></th>
													<th><?php esc_html_e( 'Notification Title', 'woocommerce-toasts' ); ?></th>
													<th><?php esc_html_e( 'Default Icon', 'woocommerce-toasts' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $cases as $case_id => $case_def ) : ?>
													<tr>
														<td>
															<span class="wc-toasts-tag cat-<?php echo esc_attr( $case_def['category'] ); ?>">
																<?php echo esc_html( ucfirst( $case_def['category'] ) ); ?>
															</span>
														</td>
														<td><code><?php echo esc_html( $case_id ); ?></code></td>
														<td>
															<span class="wc-toasts-badge badge-<?php echo esc_attr( $case_def['type'] ); ?>">
																<?php echo esc_html( ucfirst( $case_def['type'] ) ); ?>
															</span>
														</td>
														<td><strong><?php echo esc_html( $case_def['title'] ); ?></strong></td>
														<td><code><?php echo esc_html( $case_def['icon'] ); ?></code></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>

						<!-- Tab 5: Advanced & Custom CSS -->
						<div class="wc-toasts-tab-content <?php echo ( 'advanced' === $current_tab ) ? 'active' : ''; ?>" id="tab-advanced">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Advanced Integration & Custom CSS', 'woocommerce-toasts' ); ?></h2>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'AJAX Interception Mode', 'woocommerce-toasts' ); ?></th>
										<td>
											<select name="wc_toasts[ajax_interception]">
												<option value="full" <?php selected( $settings['ajax_interception'], 'full' ); ?>>
													<?php esc_html_e( 'Full Interception (jQuery Events + Fetch/XHR + DOM Observer) - Recommended', 'woocommerce-toasts' ); ?>
												</option>
												<option value="standard" <?php selected( $settings['ajax_interception'], 'standard' ); ?>>
													<?php esc_html_e( 'Standard Only (WooCommerce Core Events)', 'woocommerce-toasts' ); ?>
												</option>
											</select>
											<p class="description"><?php esc_html_e( 'Full mode also intercepts 3rd party plugins that dynamically inject notices via raw DOM insertion or custom AJAX endpoints.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Custom CSS Styles', 'woocommerce-toasts' ); ?></th>
										<td>
											<textarea name="wc_toasts[custom_css]" rows="6" class="large-text code" placeholder=".wc-toast { /* custom styles */ }"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
											<p class="description"><?php esc_html_e( 'Add custom CSS rules. These are automatically scoped and output safely on the frontend.', 'woocommerce-toasts' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Data Cleanup on Uninstall', 'woocommerce-toasts' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?>>
												<?php esc_html_e( 'Delete all plugin settings from database when this plugin is deleted', 'woocommerce-toasts' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<!-- Tab 6: Pro Features -->
						<div class="wc-toasts-tab-content <?php echo ( 'pro' === $current_tab ) ? 'active' : ''; ?>" id="tab-pro">
							<div class="wc-toasts-card wc-toasts-pro-showcase">
								<div class="pro-banner-head">
									<span class="pro-tag"><?php esc_html_e( 'PRO UPGRADE READY', 'woocommerce-toasts' ); ?></span>
									<h2><?php esc_html_e( 'Elevate Your Store With Advanced Toast Capabilities', 'woocommerce-toasts' ); ?></h2>
									<p><?php esc_html_e( 'WooCommerce Dynamic Notice Toasts Pro includes high-conversion features designed to boost average order value and delight shoppers.', 'woocommerce-toasts' ); ?></p>
								</div>

								<div class="wc-toasts-pro-grid">
									<?php foreach ( $pro_features as $feature ) : ?>
										<div class="wc-toasts-pro-feature">
											<span class="feature-badge"><?php echo esc_html( $feature['badge'] ); ?></span>
											<h4><?php echo esc_html( $feature['title'] ); ?></h4>
											<p><?php echo esc_html( $feature['description'] ); ?></p>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="pro-cta-box">
									<h3><?php esc_html_e( 'Interested in Pro or Custom Features?', 'woocommerce-toasts' ); ?></h3>
									<p><?php esc_html_e( 'This plugin architecture includes an extensible Pro Bridge with full filter hooks. All Pro features hook seamlessly into this free version.', 'woocommerce-toasts' ); ?></p>
									<a href="https://github.com/thanos/woocommerce-toasts" target="_blank" class="button button-primary button-hero">
										<?php esc_html_e( 'View Pro Documentation & Roadmap', 'woocommerce-toasts' ); ?> &rarr;
									</a>
								</div>
							</div>
						</div>

						<!-- Sticky Bottom Action Bar -->
						<div class="wc-toasts-submit-bar">
							<button type="submit" name="wc_toasts_save_settings" class="button button-primary button-large">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Save All Settings', 'woocommerce-toasts' ); ?>
							</button>
							<button type="button" class="button button-secondary button-large" id="wc-toasts-btn-reset-defaults">
								<span class="dashicons dashicons-undo"></span>
								<?php esc_html_e( 'Reset to Defaults', 'woocommerce-toasts' ); ?>
							</button>
						</div>
					</form>
				</div>

				<!-- Right Sticky Live Preview Area -->
				<div class="wc-toasts-sidebar">
					<div class="wc-toasts-preview-card" id="wc-toasts-preview-card">
						<div class="preview-header">
							<h3>
								<span class="dashicons dashicons-laptop"></span>
								<?php esc_html_e( 'Live Toast Preview', 'woocommerce-toasts' ); ?>
							</h3>
							<span class="preview-badge"><?php esc_html_e( 'Interactive', 'woocommerce-toasts' ); ?></span>
						</div>
						<p class="preview-note"><?php esc_html_e( 'Click any button below to preview how toasts render with your current styling.', 'woocommerce-toasts' ); ?></p>

						<div class="preview-actions">
							<button type="button" class="preview-btn btn-cart" data-cat="cart" data-type="success">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'cart_check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Add to Cart (Success)', 'woocommerce-toasts' ); ?>
							</button>
							<button type="button" class="preview-btn btn-coupon" data-cat="coupon" data-type="success">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'ticket_check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Coupon Applied (Success)', 'woocommerce-toasts' ); ?>
							</button>
							<button type="button" class="preview-btn btn-error" data-cat="checkout" data-type="error">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'alert_triangle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Checkout Error (Required Field)', 'woocommerce-toasts' ); ?>
							</button>
							<button type="button" class="preview-btn btn-stock" data-cat="stock" data-type="error">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'archive_alert' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Stock Warning (Insufficient)', 'woocommerce-toasts' ); ?>
							</button>
							<button type="button" class="preview-btn btn-info" data-cat="cart" data-type="notice">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Item Removed (With Undo)', 'woocommerce-toasts' ); ?>
							</button>
						</div>

						<!-- Mini Simulator Screen -->
						<div class="preview-screen-stage" id="preview-stage">
							<div class="stage-nav-dots">
								<span></span><span></span><span></span>
							</div>
							<div class="stage-mock-content">
								<div class="stage-mock-line line-header"></div>
								<div class="stage-mock-line line-body"></div>
								<div class="stage-mock-line line-body short"></div>
							</div>
							<!-- Mini Live Container Inside Simulator -->
							<div class="stage-toast-slot pos-<?php echo esc_attr( $settings['position'] ); ?> theme-<?php echo esc_attr( $settings['theme'] ); ?>" id="stage-toast-slot">
								<!-- Rendered in JS dynamically -->
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

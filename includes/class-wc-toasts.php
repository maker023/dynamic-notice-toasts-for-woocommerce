<?php
/**
 * Main Dynamic Notice Toasts for WooCommerce Orchestrator Class
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
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-catalog.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-styling.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-settings.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-pro-bridge.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-interceptor.php';
		require_once WC_TOASTS_PLUGIN_DIR . 'includes/class-wc-toasts-ajax.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
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
			__( 'Notice Toasts', 'dynamic-notice-toasts-for-woocommerce' ),
			__( 'Notice Toasts', 'dynamic-notice-toasts-for-woocommerce' ),
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
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'dynamic-notice-toasts-for-woocommerce' ) . '</a>';
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
		wp_enqueue_script( 'wc-toasts-styling', WC_TOASTS_PLUGIN_URL . 'assets/js/wc-toasts-styling.js', array(), WC_TOASTS_VERSION, true );
		wp_enqueue_script(
			'wc-toasts-admin',
			WC_TOASTS_PLUGIN_URL . 'assets/js/wc-toasts-admin.js',
			array( 'jquery', 'wp-color-picker', 'wc-toasts-styling' ),
			WC_TOASTS_VERSION,
			true
		);

		// Prepare icons dictionary for the admin live preview.
		$icons_map = array();
		foreach ( array( 'check', 'cart', 'cart_check', 'cart_empty', 'trash', 'refresh', 'ticket', 'ticket_check', 'ticket_cross', 'ticket_alert', 'credit-card', 'card_error', 'archive', 'archive_alert', 'user', 'user_check', 'lock_alert', 'lock_check', 'bell', 'alert_triangle', 'alert_circle', 'info', 'truck', 'truck_alert', 'mail_check', 'mail_alert', 'phone_alert', 'edit_alert', 'map_pin', 'clock', 'shield_alert', 'download_alert' ) as $icon_name ) {
			$icons_map[ $icon_name ] = WC_Toasts_Catalog::get_svg_icon( $icon_name );
		}

		// Localize admin data.
		wp_localize_script(
			'wc-toasts-admin',
			'wc_toasts_admin_params',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'wc_toasts_admin_nonce' ),
				'settings'   => WC_Toasts_Settings::get_all(),
				'styling'    => WC_Toasts_Styling::get_config(),
				'categories' => WC_Toasts_Catalog::get_categories(),
				'icons'      => $icons_map,
				'i18n'       => array(
					'close'            => __( 'Dismiss preview notice', 'dynamic-notice-toasts-for-woocommerce' ),
					'reset_error'      => __( 'Error resetting settings.', 'dynamic-notice-toasts-for-woocommerce' ),
					'connection_error' => __( 'Connection error occurred.', 'dynamic-notice-toasts-for-woocommerce' ),
					'saved'            => __( 'Settings saved successfully!', 'dynamic-notice-toasts-for-woocommerce' ),
					'reset_confirm'    => __( 'Are you sure you want to reset all toast settings to factory defaults?', 'dynamic-notice-toasts-for-woocommerce' ),
					'reset_done'       => __( 'Settings have been reset to default values.', 'dynamic-notice-toasts-for-woocommerce' ),
					'testing'          => __( 'Triggering test toast...', 'dynamic-notice-toasts-for-woocommerce' ),
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
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dynamic-notice-toasts-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The complete unslashed array is validated by sanitize_settings immediately below.
		$raw_input          = isset( $_POST['wc_toasts'] ) ? (array) wp_unslash( $_POST['wc_toasts'] ) : array();
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

		$settings   = WC_Toasts_Settings::get_all();
		$categories = WC_Toasts_Catalog::get_categories();
		$cases      = WC_Toasts_Catalog::get_cases();
		$themes     = WC_Toasts_Pro_Bridge::get_themes();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation; no settings are changed here.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		// Count cases per category.
		$category_counts = array();
		foreach ( $cases as $c_def ) {
			$cat                     = isset( $c_def['category'] ) ? $c_def['category'] : 'system';
			$category_counts[ $cat ] = ( isset( $category_counts[ $cat ] ) ? $category_counts[ $cat ] : 0 ) + 1;
		}

		$tabs = array(
			'general'    => array(
				'title' => __( 'General Settings', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'  => 'dashicons-admin-generic',
			),
			'appearance' => array(
				'title' => __( 'Appearance & Styling', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'  => 'dashicons-art',
			),
			'behavior'   => array(
				'title' => __( 'Behavior', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'  => 'dashicons-clock',
			),
			'catalog'    => array(
				/* translators: %d: Number of classified notification cases. */
				'title' => sprintf( __( 'Notice Catalog (%d Cases)', 'dynamic-notice-toasts-for-woocommerce' ), count( $cases ) ),
				'icon'  => 'dashicons-list-view',
			),
			'advanced'   => array(
				'title' => __( 'Advanced', 'dynamic-notice-toasts-for-woocommerce' ),
				'icon'  => 'dashicons-admin-tools',
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
						<h1><?php esc_html_e( 'Dynamic Notice Toasts for WooCommerce', 'dynamic-notice-toasts-for-woocommerce' ); ?></h1>
						<p class="wc-toasts-subtitle">
							<?php esc_html_e( 'Clear storefront notices with useful actions and considered typography.', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							<span class="wc-toasts-version-badge">v<?php echo esc_html( WC_TOASTS_VERSION ); ?></span>
						</p>
					</div>
				</div>
				<div class="wc-toasts-header-actions">
					<button type="button" class="button button-primary" id="wc-toasts-btn-quick-preview">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Test Toast', 'dynamic-notice-toasts-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success message after a nonce-verified save. ?>
			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible wc-toasts-admin-alert">
					<p><strong><?php esc_html_e( 'Success:', 'dynamic-notice-toasts-for-woocommerce' ); ?></strong> <?php esc_html_e( 'Settings saved successfully.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
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
						<?php foreach ( array( 'success', 'error', 'info' ) as $legacy_type ) : ?>
							<input type="hidden" name="wc_toasts[color_<?php echo esc_attr( $legacy_type ); ?>_text]" value="<?php echo esc_attr( $settings[ 'color_' . $legacy_type . '_text' ] ); ?>">
						<?php endforeach; ?>

						<!-- Tab 1: General -->
						<div class="wc-toasts-tab-content <?php echo ( 'general' === $current_tab ) ? 'active' : ''; ?>" id="tab-general">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Core Engine Settings', 'dynamic-notice-toasts-for-woocommerce' ); ?></h2>
								<p class="description"><?php esc_html_e( 'Control how WooCommerce notifications are intercepted and replaced.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Enable Toast System', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label class="wc-toasts-switch">
												<input type="checkbox" name="wc_toasts[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?>>
												<span class="slider"></span>
											</label>
											<p class="description"><?php esc_html_e( 'Turn dynamic notice toasts on or off across your store.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Hide Default WooCommerce Banners', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label class="wc-toasts-switch">
												<input type="checkbox" name="wc_toasts[hide_default_notices]" value="1" <?php checked( $settings['hide_default_notices'], 1 ); ?>>
												<span class="slider"></span>
											</label>
											<p class="description"><?php esc_html_e( 'Suppresses the default static alert boxes (.woocommerce-message, .woocommerce-error, .woocommerce-info) to eliminate duplicate notices and layout jumps.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Screen Position', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<div class="wc-toasts-position-grid">
												<?php
												$positions = array(
													'top-left'      => __( 'Top Left', 'dynamic-notice-toasts-for-woocommerce' ),
													'top-center'    => __( 'Top Center', 'dynamic-notice-toasts-for-woocommerce' ),
													'top-right'     => __( 'Top Right', 'dynamic-notice-toasts-for-woocommerce' ),
													'bottom-left'   => __( 'Bottom Left (Default)', 'dynamic-notice-toasts-for-woocommerce' ),
													'bottom-center' => __( 'Bottom Center', 'dynamic-notice-toasts-for-woocommerce' ),
													'bottom-right'  => __( 'Bottom Right', 'dynamic-notice-toasts-for-woocommerce' ),
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
									<tr hidden>
										<th scope="row"><?php esc_html_e( 'Design Theme / Preset', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<select name="wc_toasts[theme]" id="wc-toasts-theme-select" class="regular-text">
												<?php foreach ( $themes as $t_key => $t_info ) : ?>
													<option value="<?php echo esc_attr( $t_key ); ?>" <?php selected( $settings['theme'], $t_key ); ?> <?php echo ( ! empty( $t_info['pro'] ) && ! WC_Toasts_Pro_Bridge::is_pro_active() ) ? 'disabled' : ''; ?>>
														<?php echo esc_html( $t_info['label'] ); ?>
													</option>
												<?php endforeach; ?>
											</select>
											<p class="description"><?php esc_html_e( 'Select a pre-designed modern aesthetic or choose Custom for your own store branding.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Max Visible Toasts', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[max_toasts]" min="1" max="10" step="1" value="<?php echo esc_attr( $settings['max_toasts'] ); ?>" class="small-text">
											<p class="description"><?php esc_html_e( 'Limits the number of simultaneous toasts on screen before older ones are pushed off.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Newest on Top', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[newest_on_top]" value="1" <?php checked( $settings['newest_on_top'], 1 ); ?>>
												<?php esc_html_e( 'Insert new toasts at the top of the stack instead of appending to the bottom', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Prevent Duplicate Spam', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[deduplication]" value="1" <?php checked( $settings['deduplication'], 1 ); ?>>
												<?php esc_html_e( 'Prevents rapid repeated clicks on "Add to Cart" from flooding the screen with duplicate toasts', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<!-- Tab 2: Appearance -->
						<div class="wc-toasts-tab-content <?php echo ( 'appearance' === $current_tab ) ? 'active' : ''; ?>" id="tab-appearance">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Styling & Dimensions', 'dynamic-notice-toasts-for-woocommerce' ); ?></h2>
								<div class="wc-toasts-style-editor">
									<div class="wc-toasts-token-controls">
										<label for="wc-toasts-layout-select"><?php esc_html_e( 'Toast style', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<select id="wc-toasts-layout-select" name="wc_toasts[layout]">
											<?php foreach ( WC_Toasts_Styling::get_layouts() as $layout_key => $layout_label ) : ?>
												<option value="<?php echo esc_attr( $layout_key ); ?>" <?php selected( $settings['layout'], $layout_key ); ?>><?php echo esc_html( $layout_label ); ?></option>
											<?php endforeach; ?>
										</select>
										<label for="wc-toasts-palette-select"><?php esc_html_e( 'Color palette', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<select id="wc-toasts-palette-select" name="wc_toasts[palette]">
											<?php foreach ( WC_Toasts_Styling::get_palettes() as $palette_key => $palette_info ) : ?>
												<option value="<?php echo esc_attr( $palette_key ); ?>" <?php selected( $settings['palette'], $palette_key ); ?>><?php echo esc_html( $palette_info['label'] ); ?></option>
											<?php endforeach; ?>
										</select>
										<p class="description"><?php esc_html_e( 'Customize any color below. Leave a field empty to use its palette or automatic value.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										<div class="wc-toasts-token-grid">
											<?php foreach ( WC_Toasts_Styling::get_tokens() as $token_key => $token_label ) : ?>
												<div class="wc-toasts-token-field"><label for="wc-toasts-token-<?php echo esc_attr( $token_key ); ?>"><?php echo esc_html( $token_label ); ?></label>
													<input id="wc-toasts-token-<?php echo esc_attr( $token_key ); ?>" type="text" class="wc-toasts-color-picker wc-toasts-token-input" data-token="<?php echo esc_attr( $token_key ); ?>" name="wc_toasts[custom_tokens][<?php echo esc_attr( $token_key ); ?>]" value="<?php echo esc_attr( isset( $settings['custom_tokens'][ $token_key ] ) ? $settings['custom_tokens'][ $token_key ] : '' ); ?>" placeholder="<?php esc_attr_e( 'Automatic', 'dynamic-notice-toasts-for-woocommerce' ); ?>">
												</div>
											<?php endforeach; ?>
										</div>
										<button type="button" class="button" id="wc-toasts-reset-colors"><?php esc_html_e( 'Reset color overrides', 'dynamic-notice-toasts-for-woocommerce' ); ?></button>
									</div>
									<div class="wc-toasts-style-preview"><h3><?php esc_html_e( 'Your toast preview', 'dynamic-notice-toasts-for-woocommerce' ); ?></h3><div id="wc-toasts-editor-preview" class="stage-toast-slot"></div><p><?php esc_html_e( 'Changes appear here immediately. Save settings to apply them to your store.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p></div>
								</div>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Corner Radius (px)', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<input type="range" name="wc_toasts[border_radius]" id="wc-toasts-radius-range" min="0" max="28" step="2" value="<?php echo esc_attr( $settings['border_radius'] ); ?>">
											<span id="wc-toasts-radius-val"><?php echo esc_html( $settings['border_radius'] ); ?>px</span>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Drop Shadow', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<select name="wc_toasts[box_shadow]" id="wc-toasts-shadow-select">
												<option value="none" <?php selected( $settings['box_shadow'], 'none' ); ?>><?php esc_html_e( 'None (Flat)', 'dynamic-notice-toasts-for-woocommerce' ); ?></option>
												<option value="subtle" <?php selected( $settings['box_shadow'], 'subtle' ); ?>><?php esc_html_e( 'Subtle & Soft', 'dynamic-notice-toasts-for-woocommerce' ); ?></option>
												<option value="medium" <?php selected( $settings['box_shadow'], 'medium' ); ?>><?php esc_html_e( 'Medium Modern (Default)', 'dynamic-notice-toasts-for-woocommerce' ); ?></option>
												<option value="elevated" <?php selected( $settings['box_shadow'], 'elevated' ); ?>><?php esc_html_e( 'Elevated Float', 'dynamic-notice-toasts-for-woocommerce' ); ?></option>
												<option value="glow" <?php selected( $settings['box_shadow'], 'glow' ); ?>><?php esc_html_e( 'Ambient Colored Glow', 'dynamic-notice-toasts-for-woocommerce' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Toast Width (px)', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[toast_width]" min="280" max="550" step="10" value="<?php echo esc_attr( $settings['toast_width'] ); ?>" class="small-text"> px
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Show Status & Category Icons', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[show_icons]" value="1" <?php checked( $settings['show_icons'], 1 ); ?>>
												<?php esc_html_e( 'Display clean SVG vector icons inside toasts', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
								</table>

								<div hidden>
								<h3 style="margin-top:30px;"><?php esc_html_e( 'Custom Color Accents', 'dynamic-notice-toasts-for-woocommerce' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Active when Theme is set to "Custom Color Palette" or for accent highlights.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>

								<div class="wc-toasts-colors-grid">
									<div class="wc-toasts-color-box">
										<h4><?php esc_html_e( 'Success Notices', 'dynamic-notice-toasts-for-woocommerce' ); ?></h4>
										<label><?php esc_html_e( 'Accent Color', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<input type="text" name="wc_toasts[color_success_accent]" value="<?php echo esc_attr( $settings['color_success_accent'] ); ?>" class="wc-toasts-color-picker">
										<label><?php esc_html_e( 'Background', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<input type="text" name="wc_toasts[color_success_bg]" value="<?php echo esc_attr( $settings['color_success_bg'] ); ?>" class="wc-toasts-color-picker">
									</div>
									<div class="wc-toasts-color-box">
										<h4><?php esc_html_e( 'Error Notices', 'dynamic-notice-toasts-for-woocommerce' ); ?></h4>
										<label><?php esc_html_e( 'Accent Color', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<input type="text" name="wc_toasts[color_error_accent]" value="<?php echo esc_attr( $settings['color_error_accent'] ); ?>" class="wc-toasts-color-picker">
										<label><?php esc_html_e( 'Background', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<input type="text" name="wc_toasts[color_error_bg]" value="<?php echo esc_attr( $settings['color_error_bg'] ); ?>" class="wc-toasts-color-picker">
									</div>
									<div class="wc-toasts-color-box">
										<h4><?php esc_html_e( 'Info / Store Notices', 'dynamic-notice-toasts-for-woocommerce' ); ?></h4>
										<label><?php esc_html_e( 'Accent Color', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<input type="text" name="wc_toasts[color_info_accent]" value="<?php echo esc_attr( $settings['color_info_accent'] ); ?>" class="wc-toasts-color-picker">
										<label><?php esc_html_e( 'Background', 'dynamic-notice-toasts-for-woocommerce' ); ?></label>
										<input type="text" name="wc_toasts[color_info_bg]" value="<?php echo esc_attr( $settings['color_info_bg'] ); ?>" class="wc-toasts-color-picker">
									</div>
								</div>
							</div>
						</div>

						</div>
						<!-- Tab 3: Behavior -->
						<div class="wc-toasts-tab-content <?php echo ( 'behavior' === $current_tab ) ? 'active' : ''; ?>" id="tab-behavior">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Timing & Interactions', 'dynamic-notice-toasts-for-woocommerce' ); ?></h2>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Success Toast Duration (ms)', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[duration_success]" step="500" min="1000" max="30000" value="<?php echo esc_attr( $settings['duration_success'] ); ?>" class="regular-text">
											<p class="description"><?php esc_html_e( 'Duration in milliseconds before success toasts auto-dismiss (e.g. 4500 = 4.5 seconds).', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Error Toast Duration (ms)', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[duration_error]" step="500" min="0" max="30000" value="<?php echo esc_attr( $settings['duration_error'] ); ?>" class="regular-text">
											<p class="description"><?php esc_html_e( 'Set to 0 if you want error notices (e.g. checkout issues) to stay persistent until user manually clicks close.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Info Notice Duration (ms)', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<input type="number" name="wc_toasts[duration_info]" step="500" min="1000" max="30000" value="<?php echo esc_attr( $settings['duration_info'] ); ?>" class="regular-text">
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Countdown Progress Bar', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[show_progress_bar]" value="1" <?php checked( $settings['show_progress_bar'], 1 ); ?>>
												<?php esc_html_e( 'Display animated progress line indicating remaining visible time', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Pause on Hover', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[pause_on_hover]" value="1" <?php checked( $settings['pause_on_hover'], 1 ); ?>>
												<?php esc_html_e( 'Pause the auto-dismiss timer whenever user hovers mouse over the toast', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Close "X" Button', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[show_close_button]" value="1" <?php checked( $settings['show_close_button'], 1 ); ?>>
												<?php esc_html_e( 'Show clickable dismiss button on every toast', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Mobile Bottom Dock', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[mobile_bottom]" value="1" <?php checked( $settings['mobile_bottom'], 1 ); ?>>
												<?php esc_html_e( 'Automatically dock toasts at the bottom on mobile screens (< 640px) for optimal thumb reachability', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<!-- Tab 4: Notice Catalog & Categories -->
						<div class="wc-toasts-tab-content <?php echo ( 'catalog' === $current_tab ) ? 'active' : ''; ?>" id="tab-catalog">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'WooCommerce Notification Catalog', 'dynamic-notice-toasts-for-woocommerce' ); ?></h2>
								<p class="description">
									<?php
									/* translators: %d: number of cases */
									printf( esc_html__( 'Our intelligent classifier inspects incoming notices and categorizes them across %d specific WooCommerce cases.', 'dynamic-notice-toasts-for-woocommerce' ), count( $cases ) );
									?>
								</p>

								<div class="wc-toasts-cat-list">
									<?php foreach ( $categories as $cat_id => $cat_data ) : ?>
										<div class="wc-toasts-cat-item">
											<div class="cat-header">
												<div class="cat-info">
													<div class="cat-badge" aria-hidden="true">
														<?php echo WC_Toasts_Catalog::get_svg_icon( $cat_data['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													</div>
													<div>
														<h4><?php echo esc_html( $cat_data['name'] ); ?></h4>
														<p><?php echo esc_html( $cat_data['description'] ); ?></p>
													</div>
												</div>
												<div class="cat-meta">
													<span class="cat-case-count">
														<?php echo isset( $category_counts[ $cat_id ] ) ? esc_html( $category_counts[ $cat_id ] ) : 0; ?> <?php esc_html_e( 'Cases', 'dynamic-notice-toasts-for-woocommerce' ); ?>
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
									<h3><?php esc_html_e( 'Catalog Cases Breakdown', 'dynamic-notice-toasts-for-woocommerce' ); ?></h3>
									<div class="wc-toasts-table-scroll">
										<table class="widefat striped">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Category', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
													<th><?php esc_html_e( 'Case Identifier', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
													<th><?php esc_html_e( 'Type', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
													<th><?php esc_html_e( 'Notification Title', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
													<th><?php esc_html_e( 'Default Icon', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
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

						<!-- Tab 5: Advanced -->
						<div class="wc-toasts-tab-content <?php echo ( 'advanced' === $current_tab ) ? 'active' : ''; ?>" id="tab-advanced">
							<div class="wc-toasts-card">
								<h2><?php esc_html_e( 'Advanced Integration', 'dynamic-notice-toasts-for-woocommerce' ); ?></h2>

								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'AJAX Interception Mode', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<select name="wc_toasts[ajax_interception]">
												<option value="full" <?php selected( $settings['ajax_interception'], 'full' ); ?>>
													<?php esc_html_e( 'Full Interception (jQuery Events + Fetch/XHR + DOM Observer) - Recommended', 'dynamic-notice-toasts-for-woocommerce' ); ?>
												</option>
												<option value="standard" <?php selected( $settings['ajax_interception'], 'standard' ); ?>>
													<?php esc_html_e( 'Standard Only (WooCommerce Core Events)', 'dynamic-notice-toasts-for-woocommerce' ); ?>
												</option>
											</select>
											<p class="description"><?php esc_html_e( 'Full mode also intercepts 3rd party plugins that dynamically inject notices via raw DOM insertion or custom AJAX endpoints.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Data Cleanup on Uninstall', 'dynamic-notice-toasts-for-woocommerce' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wc_toasts[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?>>
												<?php esc_html_e( 'Delete all plugin settings from database when this plugin is deleted', 'dynamic-notice-toasts-for-woocommerce' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>
						</div>

						<!-- Sticky Bottom Action Bar -->
						<div class="wc-toasts-submit-bar">
							<button type="submit" name="wc_toasts_save_settings" class="button button-primary button-large">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Save All Settings', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							</button>
							<button type="button" class="button button-secondary button-large" id="wc-toasts-btn-reset-defaults">
								<span class="dashicons dashicons-undo"></span>
								<?php esc_html_e( 'Reset to Defaults', 'dynamic-notice-toasts-for-woocommerce' ); ?>
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
								<?php esc_html_e( 'Live Toast Preview', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							</h3>
							<span class="preview-badge"><?php esc_html_e( 'Interactive', 'dynamic-notice-toasts-for-woocommerce' ); ?></span>
						</div>
						<p class="preview-note"><?php esc_html_e( 'Click any button below to preview how toasts render with your current styling.', 'dynamic-notice-toasts-for-woocommerce' ); ?></p>

						<div class="preview-actions">
							<button type="button" class="preview-btn btn-cart" data-cat="cart" data-type="success">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'cart_check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Add to Cart (Success)', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							</button>
							<button type="button" class="preview-btn btn-coupon" data-cat="coupon" data-type="success">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'ticket_check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Coupon Applied (Success)', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							</button>
							<button type="button" class="preview-btn btn-error" data-cat="checkout" data-type="error">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'alert_triangle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Checkout Error (Required Field)', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							</button>
							<button type="button" class="preview-btn btn-stock" data-cat="stock" data-type="error">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'archive_alert' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Stock Warning (Insufficient)', 'dynamic-notice-toasts-for-woocommerce' ); ?>
							</button>
							<button type="button" class="preview-btn btn-info" data-cat="cart" data-type="notice">
								<?php echo WC_Toasts_Catalog::get_svg_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php esc_html_e( 'Item Removed (With Undo)', 'dynamic-notice-toasts-for-woocommerce' ); ?>
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

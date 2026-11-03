<?php
/**
 * Settings → Drift: Veneer.
 *
 * One form with three cards: Branding, Login screen and Admin menus. Saved
 * via admin-post.php (nonce + can_manage()), then redirected back.
 *
 * Once an agency user exists, only agency users can see the screen
 * (Drift_Veneer_Access::can_manage()), so clients can't rebrand the admin
 * or unhide their own menus.
 *
 * @package Drift_Veneer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings screen.
 */
final class Drift_Veneer_Admin {

	const SLUG = 'drift-veneer';

	/**
	 * Hook suffix of the settings page, set when the menu is added.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_drift_veneer_save', array( __CLASS__, 'handle_save' ) );
		// After Drift_Veneer_Branding::footer_text() (99), so the copyright wins on this screen.
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ), 100 );
		add_filter( 'plugin_action_links_' . plugin_basename( DRIFT_VENEER_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Add Settings → Drift: Veneer.
	 *
	 * @return void
	 */
	public static function add_menu() {
		if ( ! Drift_Veneer_Access::can_manage() ) {
			return;
		}

		self::$hook_suffix = (string) add_options_page(
			__( 'Drift: Veneer', 'drift-veneer' ),
			__( 'Drift: Veneer', 'drift-veneer' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * URL of the screen.
	 *
	 * @param array $args Extra query args.
	 * @return string
	 */
	public static function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'options-general.php' ) );
	}

	/**
	 * Whether the current admin screen is this one.
	 *
	 * @return bool
	 */
	private static function is_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && '' !== self::$hook_suffix && self::$hook_suffix === $screen->id;
	}

	/**
	 * "Settings" link on the plugin's row on the Plugins screen.
	 *
	 * @param array $links Action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		if ( Drift_Veneer_Access::can_manage() ) {
			array_unshift(
				$links,
				'<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'drift-veneer' ) . '</a>'
			);
		}

		return $links;
	}

	/**
	 * Styles and scripts, on this screen only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function assets( $hook_suffix ) {
		if ( '' === self::$hook_suffix || self::$hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'drift-veneer-admin', DRIFT_VENEER_URL . 'assets/admin.css', array(), DRIFT_VENEER_VERSION );
		wp_enqueue_script( 'drift-veneer-admin', DRIFT_VENEER_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), DRIFT_VENEER_VERSION, true );
		wp_localize_script(
			'drift-veneer-admin',
			'DRIFT_VENEER',
			array(
				'i18n' => array(
					'selectImage' => __( 'Select image', 'drift-veneer' ),
					'useImage'    => __( 'Use this image', 'drift-veneer' ),
					'change'      => __( 'Change image', 'drift-veneer' ),
				),
			)
		);
	}

	/**
	 * Copyright and company details in the admin footer, on this screen only.
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		if ( ! self::is_screen() ) {
			return $text;
		}

		return 'Copyright &copy; ' . esc_html( wp_date( 'Y' ) ) . ' Drift Creative Systems Ltd &middot; Registered in England and Wales, company number 17548220. Registered office: 167-169 Great Portland Street, 5th Floor, London W1W 5PF';
	}

	/**
	 * Drift monogram. Fixed markup; only the fill is variable.
	 *
	 * @param string $fill Fill colour.
	 * @param int    $size Width and height in px.
	 * @return string
	 */
	private static function mark_svg( $fill = 'currentColor', $size = 32 ) {
		$paths = array(
			'M17 12H54C77 12 91 29 91 50C91 72 76 88 54 88H45L55 76H54C70 76 79 64 79 50C79 35 69 24 54 24H29V44H17Z',
			'M17 55H29L17 67Z',
			'M41 55H54L25 88H12Z',
			'M55 76H70L59 88H43Z',
		);

		$svg = sprintf( '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 100 100" aria-hidden="true" focusable="false">', (int) $size );
		foreach ( $paths as $d ) {
			$svg .= '<path fill="' . esc_attr( $fill ) . '" d="' . esc_attr( $d ) . '"/>';
		}

		return $svg . '</svg>';
	}

	/**
	 * Print the screen.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! Drift_Veneer_Access::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'drift-veneer' ) );
		}

		$saved    = Drift_Veneer_Settings::saved();
		$defaults = Drift_Veneer_Settings::defaults();
		$current  = Drift_Veneer_Settings::get();
		?>
		<div class="wrap drift-veneer">
			<h1 class="drift-veneer__title">
				<span class="drift-veneer__mark"><?php echo self::mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed markup, attributes escaped in mark_svg(). ?></span>
				<?php echo esc_html( get_admin_page_title() ); ?>
			</h1>
			<p class="drift-veneer__lead"><?php esc_html_e( 'Admin branding, the login screen, and which menus non-agency users can see. Blank fields use the default.', 'drift-veneer' ); ?></p>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'drift-veneer' ); ?></p></div>
			<?php endif; ?>

			<?php if ( defined( 'WLCMS_VERSION' ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'White Label CMS is also active. Deactivate it once you\'re happy with these settings: both plugins change the login screen and admin bar and will conflict.', 'drift-veneer' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'drift_veneer_save', 'drift_veneer_nonce' ); ?>
				<input type="hidden" name="action" value="drift_veneer_save">

				<section class="drift-veneer__card" aria-labelledby="drift-veneer-branding-title">
					<h2 class="drift-veneer__card-title" id="drift-veneer-branding-title"><?php esc_html_e( 'Branding', 'drift-veneer' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'WordPress branding', 'drift-veneer' ); ?></th>
							<td>
								<input type="hidden" name="drift_veneer[hide_wp_branding]" value="0">
								<label>
									<input type="checkbox" name="drift_veneer[hide_wp_branding]" value="1" <?php checked( $current['hide_wp_branding'], '1' ); ?>>
									<?php esc_html_e( 'Hide the WordPress logo and links in the admin bar, the "Thank you for creating with WordPress" footer and the version number.', 'drift-veneer' ); ?>
								</label>
							</td>
						</tr>
						<?php
						self::image_row( 'admin_bar_logo', __( 'Admin bar logo', 'drift-veneer' ), $saved, $defaults, __( 'Shown at the top-left of the admin bar, about 20px tall.', 'drift-veneer' ) );
						self::text_row( 'admin_bar_logo_url', __( 'Admin bar logo link', 'drift-veneer' ), $saved, $defaults, 'url', __( 'Leave blank to link to the dashboard.', 'drift-veneer' ) );
						self::text_row( 'footer_text', __( 'Admin footer text', 'drift-veneer' ), $saved, $defaults );
						self::text_row( 'footer_url', __( 'Admin footer link', 'drift-veneer' ), $saved, $defaults, 'url' );
						?>
					</table>
				</section>

				<section class="drift-veneer__card" aria-labelledby="drift-veneer-login-title">
					<h2 class="drift-veneer__card-title" id="drift-veneer-login-title"><?php esc_html_e( 'Login screen', 'drift-veneer' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php
						self::image_row( 'login_logo', __( 'Logo', 'drift-veneer' ), $saved, $defaults, __( 'Links to this site\'s home page.', 'drift-veneer' ) );
						self::number_row( 'login_logo_width', __( 'Logo width (px)', 'drift-veneer' ), $saved, $defaults );
						self::number_row( 'login_logo_height', __( 'Logo height (px)', 'drift-veneer' ), $saved, $defaults );
						self::colour_row( 'login_bg_color', __( 'Background colour', 'drift-veneer' ), $saved, $defaults );
						self::image_row( 'login_bg_image', __( 'Background image', 'drift-veneer' ), $saved, $defaults, __( 'Optional. Covers the whole page.', 'drift-veneer' ) );
						self::colour_row( 'login_form_bg_color', __( 'Form background', 'drift-veneer' ), $saved, $defaults );
						self::colour_row( 'login_label_color', __( 'Form label colour', 'drift-veneer' ), $saved, $defaults );
						self::colour_row( 'login_button_color', __( 'Button colour', 'drift-veneer' ), $saved, $defaults );
						self::colour_row( 'login_button_text', __( 'Button text colour', 'drift-veneer' ), $saved, $defaults );
						self::colour_row( 'login_button_hover', __( 'Button hover colour', 'drift-veneer' ), $saved, $defaults );
						self::colour_row( 'login_link_color', __( 'Link colour', 'drift-veneer' ), $saved, $defaults );
						?>
					</table>
				</section>

				<?php Drift_Veneer_Access::render_settings_card(); ?>

				<?php submit_button( __( 'Save settings', 'drift-veneer' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * "Blank uses the default: …" hint under a field.
	 *
	 * @param string $key      Setting key.
	 * @param array  $defaults Defaults.
	 * @return void
	 */
	private static function default_hint( $key, $defaults ) {
		$default = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '';

		if ( '' === $default ) {
			printf( '<p class="description">%s</p>', esc_html__( 'Leave blank for none.', 'drift-veneer' ) );
			return;
		}

		printf(
			'<p class="description">%s <code>%s</code></p>',
			esc_html__( 'Blank uses the default:', 'drift-veneer' ),
			esc_html( $default )
		);
	}

	/**
	 * Text or URL field row.
	 *
	 * @param string $key      Setting key.
	 * @param string $label    Row label.
	 * @param array  $saved    Saved values.
	 * @param array  $defaults Defaults.
	 * @param string $type     Input type.
	 * @param string $help     Optional help text.
	 * @return void
	 */
	private static function text_row( $key, $label, $saved, $defaults, $type = 'text', $help = '' ) {
		$id = 'drift-veneer-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" class="regular-text" name="drift_veneer[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( isset( $saved[ $key ] ) ? $saved[ $key ] : '' ) ); ?>">
				<?php if ( $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
				<?php self::default_hint( $key, $defaults ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Number field row (logo sizes).
	 *
	 * @param string $key      Setting key.
	 * @param string $label    Row label.
	 * @param array  $saved    Saved values.
	 * @param array  $defaults Defaults.
	 * @return void
	 */
	private static function number_row( $key, $label, $saved, $defaults ) {
		$id    = 'drift-veneer-' . $key;
		$value = isset( $saved[ $key ] ) ? $saved[ $key ] : '';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="number" min="1" max="1000" step="1" id="<?php echo esc_attr( $id ); ?>" class="small-text" name="drift_veneer[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ? (string) absint( $value ) : '' ); ?>">
				<?php self::default_hint( $key, $defaults ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Colour picker row.
	 *
	 * @param string $key      Setting key.
	 * @param string $label    Row label.
	 * @param array  $saved    Saved values.
	 * @param array  $defaults Defaults.
	 * @return void
	 */
	private static function colour_row( $key, $label, $saved, $defaults ) {
		$id = 'drift-veneer-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" class="drift-veneer-colour" data-default-color="<?php echo esc_attr( isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '' ); ?>" name="drift_veneer[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) ( isset( $saved[ $key ] ) ? $saved[ $key ] : '' ) ); ?>">
				<?php self::default_hint( $key, $defaults ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Media Library image picker row.
	 *
	 * @param string $key      Setting key.
	 * @param string $label    Row label.
	 * @param array  $saved    Saved values.
	 * @param array  $defaults Defaults.
	 * @param string $help     Optional help text.
	 * @return void
	 */
	private static function image_row( $key, $label, $saved, $defaults, $help = '' ) {
		$id    = 'drift-veneer-' . $key;
		$value = (string) ( isset( $saved[ $key ] ) ? $saved[ $key ] : '' );
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<div class="drift-veneer-media">
					<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="drift_veneer[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>">
					<div class="drift-veneer-media__preview"<?php echo '' === $value ? ' hidden' : ''; ?>>
						<?php if ( '' !== $value ) : ?>
							<img src="<?php echo esc_url( $value ); ?>" alt="">
						<?php endif; ?>
					</div>
					<p>
						<button type="button" class="button drift-veneer-media__select"><?php echo '' !== $value ? esc_html__( 'Change image', 'drift-veneer' ) : esc_html__( 'Select image', 'drift-veneer' ); ?></button>
						<button type="button" class="button drift-veneer-media__remove"<?php echo '' === $value ? ' hidden' : ''; ?>><?php esc_html_e( 'Remove', 'drift-veneer' ); ?></button>
					</p>
				</div>
				<?php if ( $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
				<?php self::default_hint( $key, $defaults ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the settings screen.
	 *
	 * @return void
	 */
	public static function handle_save() {
		if ( ! Drift_Veneer_Access::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'drift-veneer' ) );
		}

		check_admin_referer( 'drift_veneer_save', 'drift_veneer_nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field in Drift_Veneer_Settings::save().
		Drift_Veneer_Settings::save( isset( $_POST['drift_veneer'] ) ? wp_unslash( $_POST['drift_veneer'] ) : array() );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in Drift_Veneer_Access::save_settings().
		Drift_Veneer_Access::save_settings( isset( $_POST['drift_veneer_access'] ) ? wp_unslash( $_POST['drift_veneer_access'] ) : array() );

		wp_safe_redirect( self::url( array( 'updated' => '1' ) ) );
		exit;
	}
}

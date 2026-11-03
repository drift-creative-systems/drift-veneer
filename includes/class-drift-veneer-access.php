<?php
/**
 * Agency users and admin menu hiding.
 *
 * Clients are sometimes Administrators, so role and capability checks can't
 * tell agency staff and clients apart. Instead:
 *
 *   - An "Agency user" checkbox on each user's profile (user meta
 *     self::META). Agency users see the full admin; everyone else, whatever
 *     their role, has the menus ticked on Settings → Drift: Veneer hidden.
 *   - Hidden menus (and their submenu pages) are also blocked by URL, not
 *     just removed from the sidebar.
 *
 * Lockout safety:
 *   - Until at least one agency user exists, nothing is hidden and any
 *     administrator can tick the checkbox. Once one exists, only agency
 *     users can change it or the plugin's settings.
 *   - If the last agency user unticks themselves, hiding switches off again.
 *   - The Dashboard and the user's own Profile are never hidden or blocked.
 *
 * @package Drift_Veneer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Agency users and menu hiding.
 */
final class Drift_Veneer_Access {

	const META   = 'drift_veneer_agency_user';
	const OPTION = 'drift_veneer_hidden_menus';

	/**
	 * Never hidden or blocked. See the lockout notes above.
	 */
	const PROTECTED_SLUGS = array( 'index.php', 'profile.php' );

	/**
	 * Whether any agency user exists. Cached per request.
	 *
	 * @var bool|null
	 */
	private static $agency_exists = null;

	/**
	 * Slugs blocked for this request, filled by hide_menus().
	 *
	 * @var string[]
	 */
	private static $blocked = array();

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'render_profile_field' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_profile_field' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_profile_field' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_field' ) );

		// Late, so every plugin has added its menus first.
		add_action( 'admin_menu', array( __CLASS__, 'hide_menus' ), 9999 );
		add_action( 'current_screen', array( __CLASS__, 'block_hidden_pages' ) );
	}

	/**
	 * Top-level menus hidden until someone saves a choice: Appearance,
	 * Plugins, Users, Tools, Settings and ACF.
	 *
	 * @return string[]
	 */
	public static function default_hidden_menus() {
		/**
		 * Filters the menus hidden from non-agency users before the settings
		 * screen has been saved.
		 *
		 * @param string[] $menus Top-level menu slugs.
		 */
		return (array) apply_filters(
			'drift_veneer_default_hidden_menus',
			array(
				'themes.php',
				'plugins.php',
				'users.php',
				'tools.php',
				'options-general.php',
				'edit.php?post_type=acf-field-group',
			)
		);
	}

	/**
	 * Menu slugs hidden from non-agency users: the saved list, or the
	 * defaults if the screen has never been saved.
	 *
	 * @return string[]
	 */
	public static function hidden_menus() {
		$saved = get_option( self::OPTION, null );
		$menus = is_array( $saved ) ? $saved : self::default_hidden_menus();

		return array_values( array_diff( array_map( 'strval', $menus ), self::PROTECTED_SLUGS ) );
	}

	/* ── Agency users ──────────────────────────────────────────────────── */

	/**
	 * Whether a user is an agency user.
	 *
	 * @param int $user_id User ID, or 0 for the current user.
	 * @return bool
	 */
	public static function is_agency( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		return $user_id && '1' === (string) get_user_meta( $user_id, self::META, true );
	}

	/**
	 * Whether at least one agency user exists.
	 *
	 * @return bool
	 */
	public static function agency_users_exist() {
		if ( null === self::$agency_exists ) {
			self::$agency_exists = (bool) get_users(
				array(
					'meta_key'    => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One lookup, cached per request.
					'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'number'      => 1,
					'fields'      => 'ID',
					'count_total' => false,
				)
			);
		}
		return self::$agency_exists;
	}

	/**
	 * Can the current user change agency status and the plugin's settings?
	 * Any administrator until an agency user exists; agency users after that.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return ! self::agency_users_exist() || self::is_agency();
	}

	/**
	 * Does the trimmed menu apply to the current user?
	 *
	 * @return bool
	 */
	public static function is_restricted() {
		return is_user_logged_in() && self::agency_users_exist() && ! self::is_agency();
	}

	/**
	 * "Agency user" checkbox on the profile screen.
	 *
	 * @param WP_User $user User being edited.
	 * @return void
	 */
	public static function render_profile_field( $user ) {
		if ( ! self::can_manage() || ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		?>
		<h2><?php esc_html_e( 'Drift: Veneer', 'drift-veneer' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Agency user', 'drift-veneer' ); ?></th>
				<td>
					<label for="drift-veneer-agency-user">
						<input type="checkbox" id="drift-veneer-agency-user" name="drift_veneer_agency_user" value="1" <?php checked( self::is_agency( $user->ID ) ); ?>>
						<?php esc_html_e( 'Sees the full admin menu and can change Settings → Drift: Veneer.', 'drift-veneer' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Everyone else gets the trimmed menu. Menu hiding only switches on once at least one agency user exists.', 'drift-veneer' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Saves the checkbox. Core has already checked the
	 * 'update-user_{$user_id}' nonce before these hooks fire
	 * (wp-admin/user-edit.php).
	 *
	 * @param int $user_id User being saved.
	 * @return void
	 */
	public static function save_profile_field( $user_id ) {
		if ( ! self::can_manage() || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by core, see above.
		if ( ! empty( $_POST['drift_veneer_agency_user'] ) ) {
			update_user_meta( $user_id, self::META, '1' );
		} else {
			delete_user_meta( $user_id, self::META );
		}

		self::$agency_exists = null;
	}

	/* ── Menu hiding ───────────────────────────────────────────────────── */

	/**
	 * Removes hidden top-level menus for restricted users, and records them
	 * plus all their submenu pages for block_hidden_pages().
	 *
	 * @return void
	 */
	public static function hide_menus() {
		if ( ! self::is_restricted() ) {
			return;
		}

		global $submenu;

		foreach ( self::hidden_menus() as $slug ) {
			self::$blocked[] = $slug;

			foreach ( (array) ( isset( $submenu[ $slug ] ) ? $submenu[ $slug ] : array() ) as $item ) {
				if ( ! empty( $item[2] ) && ! in_array( $item[2], self::PROTECTED_SLUGS, true ) ) {
					self::$blocked[] = (string) $item[2];
				}
			}

			remove_menu_page( $slug );
		}

		self::$blocked = array_values( array_unique( self::$blocked ) );
	}

	/**
	 * Stops restricted users reaching a hidden page by typing its URL.
	 * current_screen fires after the menu (and so hide_menus()) is built.
	 *
	 * @return void
	 */
	public static function block_hidden_pages() {
		if ( ! self::$blocked || ! self::is_restricted() ) {
			return;
		}

		global $pagenow, $plugin_page;

		foreach ( self::$blocked as $slug ) {
			if ( self::request_matches( $slug, (string) $pagenow, (string) $plugin_page ) ) {
				wp_die(
					esc_html__( 'Sorry, you are not allowed to access this page.', 'drift-veneer' ),
					esc_html__( 'Not allowed', 'drift-veneer' ),
					array(
						'response'  => 403,
						'back_link' => true,
					)
				);
			}
		}
	}

	/**
	 * Does the current request open this menu slug?
	 *   'my-plugin-page'                     → ?page=my-plugin-page
	 *   'themes.php'                         → themes.php
	 *   'edit.php?post_type=acf-field-group' → edit.php with that post_type
	 *
	 * @param string $slug        Menu slug.
	 * @param string $pagenow     Current admin file.
	 * @param string $plugin_page Current ?page= slug, if any.
	 * @return bool
	 */
	private static function request_matches( $slug, $pagenow, $plugin_page ) {
		if ( '' !== $plugin_page ) {
			return $slug === $plugin_page;
		}

		$path = (string) wp_parse_url( $slug, PHP_URL_PATH );
		if ( $path !== $pagenow ) {
			return false;
		}

		parse_str( (string) wp_parse_url( $slug, PHP_URL_QUERY ), $args );
		foreach ( $args as $key => $value ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only comparison.
			if ( ! isset( $_GET[ $key ] ) || (string) sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) !== (string) $value ) {
				return false;
			}
		}

		return true;
	}

	/* ── Settings screen card ──────────────────────────────────────────── */

	/**
	 * The "Admin menus" card on Settings → Drift: Veneer. Rendered inside the
	 * screen's form; saved by save_settings().
	 *
	 * @return void
	 */
	public static function render_settings_card() {
		global $menu;

		$hidden = self::hidden_menus();
		$items  = array();

		foreach ( (array) $menu as $item ) {
			$slug = isset( $item[2] ) ? (string) $item[2] : '';
			if ( '' === $slug || false !== strpos( isset( $item[4] ) ? (string) $item[4] : '', 'wp-menu-separator' ) || in_array( $slug, self::PROTECTED_SLUGS, true ) ) {
				continue;
			}
			// Drop update/comment count bubbles from the label.
			$label          = trim( wp_strip_all_tags( preg_replace( '/<span.*$/s', '', (string) $item[0] ) ) );
			$items[ $slug ] = '' !== $label ? $label : $slug;
		}

		// Keep saved choices for menus not currently registered (e.g. a
		// deactivated plugin), so saving doesn't silently drop them.
		foreach ( $hidden as $slug ) {
			if ( ! isset( $items[ $slug ] ) ) {
				/* translators: %s: admin menu slug. */
				$items[ $slug ] = sprintf( __( '%s (not currently in the menu)', 'drift-veneer' ), $slug );
			}
		}

		$agency = get_users(
			array(
				'meta_key'   => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => array( 'ID', 'display_name' ),
			)
		);
		?>
		<section class="drift-veneer__card" aria-labelledby="drift-veneer-menus-title">
			<h2 class="drift-veneer__card-title" id="drift-veneer-menus-title"><?php esc_html_e( 'Admin menus', 'drift-veneer' ); ?></h2>
			<p class="drift-veneer__intro"><?php esc_html_e( 'Ticked menus are hidden from everyone who isn\'t an agency user, whatever their role, and their pages are blocked if someone types the address. The Dashboard and each user\'s own Profile are always available.', 'drift-veneer' ); ?></p>

			<?php if ( ! $agency ) : ?>
				<div class="notice notice-warning inline"><p>
					<?php esc_html_e( 'Menu hiding is off: no agency users yet.', 'drift-veneer' ); ?>
					<a href="<?php echo esc_url( admin_url( 'profile.php#drift-veneer-agency-user' ) ); ?>"><?php esc_html_e( 'Tick "Agency user" on your profile', 'drift-veneer' ); ?></a>
				</p></div>
			<?php else : ?>
				<p>
					<strong><?php esc_html_e( 'Agency users:', 'drift-veneer' ); ?></strong>
					<?php
					echo wp_kses(
						implode(
							', ',
							array_map(
								static function ( $u ) {
									return sprintf( '<a href="%s">%s</a>', esc_url( get_edit_user_link( (int) $u->ID ) ), esc_html( $u->display_name ) );
								},
								$agency
							)
						),
						array( 'a' => array( 'href' => array() ) )
					);
					?>
				</p>
			<?php endif; ?>

			<input type="hidden" name="drift_veneer_access[submitted]" value="1">
			<div class="drift-veneer__menus">
				<?php foreach ( $items as $slug => $label ) : ?>
					<label class="drift-veneer__menu">
						<input type="checkbox" name="drift_veneer_access[hidden_menus][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $hidden, true ) ); ?>>
						<span><?php echo esc_html( $label ); ?><small><?php echo esc_html( $slug ); ?></small></span>
					</label>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Saves the ticked menus. Called from the screen's save handler, which
	 * has already checked the nonce and can_manage().
	 *
	 * @param mixed $input Unslashed $_POST['drift_veneer_access'].
	 * @return void
	 */
	public static function save_settings( $input ) {
		if ( ! is_array( $input ) || empty( $input['submitted'] ) ) {
			return;
		}

		$slugs = array();
		foreach ( (array) ( isset( $input['hidden_menus'] ) ? $input['hidden_menus'] : array() ) as $slug ) {
			// Menu slugs are file names, query strings or plugin page slugs.
			$slug = preg_replace( '/[^A-Za-z0-9._\-?=&\/]/', '', (string) $slug );
			if ( '' !== $slug && ! in_array( $slug, self::PROTECTED_SLUGS, true ) ) {
				$slugs[] = $slug;
			}
		}

		update_option( self::OPTION, array_values( array_unique( $slugs ) ), false );
	}
}

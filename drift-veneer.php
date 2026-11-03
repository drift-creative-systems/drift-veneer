<?php
/**
 * Plugin Name:       Drift: Veneer
 * Plugin URI:        https://driftcreativesystems.co.uk/
 * Description:       White label wp-admin for your clients. Hide WordPress branding, add your logo to the admin bar and login screen, set the login colours and admin footer, and hide admin menus from everyone except your agency users.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Drift Creative Systems
 * Author URI:        https://driftcreativesystems.co.uk/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       drift-veneer
 *
 * Copyright © 2026 Drift Creative Systems Ltd
 *
 * @package Drift_Veneer
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| Duplicate install guard
|--------------------------------------------------------------------------
| A second copy (e.g. a GitHub "Source code" zip unpacked alongside the real
| folder) would fatal on redeclared classes. Bail and tell the admin instead.
*/
if ( defined( 'DRIFT_VENEER_VERSION' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Drift: Veneer is installed more than once. Please delete the duplicate plugin folder.', 'drift-veneer' ) . '</p></div>';
		}
	);
	return;
}

define( 'DRIFT_VENEER_VERSION', '1.0.0' );
define( 'DRIFT_VENEER_FILE', __FILE__ );
define( 'DRIFT_VENEER_PATH', plugin_dir_path( __FILE__ ) );
define( 'DRIFT_VENEER_URL', plugin_dir_url( __FILE__ ) );

require_once DRIFT_VENEER_PATH . 'includes/class-drift-veneer-settings.php';
require_once DRIFT_VENEER_PATH . 'includes/class-drift-veneer-access.php';
require_once DRIFT_VENEER_PATH . 'includes/class-drift-veneer-branding.php';
require_once DRIFT_VENEER_PATH . 'includes/class-drift-veneer-login.php';
require_once DRIFT_VENEER_PATH . 'includes/class-drift-veneer-admin.php';

add_action(
	'plugins_loaded',
	function () {
		Drift_Veneer_Access::init();
		Drift_Veneer_Branding::init();
		Drift_Veneer_Login::init();
		Drift_Veneer_Admin::init();
	}
);

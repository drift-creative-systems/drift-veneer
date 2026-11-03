<?php
/**
 * Removes the plugin's settings and agency user flags when it is deleted.
 *
 * @package Drift_Veneer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Removes the options stored on the current site.
 *
 * @return void
 */
function drift_veneer_uninstall_site() {
	delete_option( 'drift_veneer_settings' );
	delete_option( 'drift_veneer_hidden_menus' );
}

if ( is_multisite() ) {
	$drift_veneer_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $drift_veneer_site_ids as $drift_veneer_site_id ) {
		switch_to_blog( $drift_veneer_site_id );
		drift_veneer_uninstall_site();
		restore_current_blog();
	}
} else {
	drift_veneer_uninstall_site();
}

// User meta is shared by the whole network, so it only needs removing once.
delete_metadata( 'user', 0, 'drift_veneer_agency_user', '', true );

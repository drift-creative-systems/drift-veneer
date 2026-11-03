<?php
/**
 * Admin bar logo, WordPress branding and the admin footer.
 *
 * @package Drift_Veneer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin branding.
 */
final class Drift_Veneer_Branding {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		// Logo added early so it sits at the far left; the WordPress logo
		// (added by core at priority 10) removed late.
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_logo' ), 5 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'remove_wp_logo' ), 999 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_bar_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'admin_bar_styles' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ), 99 );
		add_filter( 'update_footer', array( __CLASS__, 'footer_version' ), 99 );
	}

	/**
	 * Removes the WordPress logo menu (About, WordPress.org, Support…).
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public static function remove_wp_logo( $bar ) {
		if ( '1' === Drift_Veneer_Settings::get()['hide_wp_branding'] ) {
			$bar->remove_node( 'wp-logo' );
		}
	}

	/**
	 * Adds the custom logo at the far left of the admin bar.
	 *
	 * @param WP_Admin_Bar $bar Admin bar.
	 * @return void
	 */
	public static function admin_bar_logo( $bar ) {
		$s = Drift_Veneer_Settings::get();

		if ( '' === $s['admin_bar_logo'] ) {
			return;
		}

		$bar->add_node(
			array(
				'id'    => 'drift-veneer-logo',
				'title' => sprintf( '<img src="%s" alt="%s">', esc_url( $s['admin_bar_logo'] ), esc_attr( get_bloginfo( 'name' ) ) ),
				'href'  => $s['admin_bar_logo_url'] ? esc_url( $s['admin_bar_logo_url'] ) : admin_url(),
				'meta'  => array( 'class' => 'drift-veneer-logo' ),
			)
		);
	}

	/**
	 * Sizes the admin bar logo, added to core's admin-bar stylesheet. Only
	 * when there's a logo and a bar.
	 *
	 * @return void
	 */
	public static function admin_bar_styles() {
		if ( ! is_admin_bar_showing() || '' === Drift_Veneer_Settings::get()['admin_bar_logo'] ) {
			return;
		}

		wp_add_inline_style(
			'admin-bar',
			'#wpadminbar .drift-veneer-logo > .ab-item{display:flex;align-items:center;}#wpadminbar .drift-veneer-logo img{display:block;height:20px;width:auto;max-width:160px;}'
		);
	}

	/**
	 * Admin footer text, replacing "Thank you for creating with WordPress".
	 *
	 * @param string $text Core footer text.
	 * @return string
	 */
	public static function footer_text( $text ) {
		$s = Drift_Veneer_Settings::get();

		if ( '' === $s['footer_text'] ) {
			return '1' === $s['hide_wp_branding'] ? '' : $text;
		}

		return $s['footer_url']
			? sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $s['footer_url'] ), esc_html( $s['footer_text'] ) )
			: esc_html( $s['footer_text'] );
	}

	/**
	 * Hides the WordPress version in the admin footer.
	 *
	 * @param string $text Core version text.
	 * @return string
	 */
	public static function footer_version( $text ) {
		return '1' === Drift_Veneer_Settings::get()['hide_wp_branding'] ? '' : $text;
	}
}

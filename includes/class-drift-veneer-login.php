<?php
/**
 * Branded login screen.
 *
 * Adds CSS to core's own 'login' stylesheet (no <style> block of our own)
 * and points the logo at the site instead of wordpress.org.
 *
 * @package Drift_Veneer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Login screen.
 */
final class Drift_Veneer_Login {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'styles' ) );
		add_filter( 'login_headerurl', array( __CLASS__, 'logo_url' ) );
		add_filter( 'login_headertext', array( __CLASS__, 'logo_text' ) );
	}

	/**
	 * Logo links to the site's home page.
	 *
	 * @return string
	 */
	public static function logo_url() {
		return home_url( '/' );
	}

	/**
	 * Logo text is the site name.
	 *
	 * @return string
	 */
	public static function logo_text() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Login screen CSS. Every value is sanitised on save (hex colours, URLs,
	 * integers) and escaped again here, so nothing raw reaches the CSS.
	 *
	 * @return void
	 */
	public static function styles() {
		$s   = Drift_Veneer_Settings::get();
		$css = '';

		if ( $s['login_logo'] ) {
			$w    = absint( $s['login_logo_width'] ) ? absint( $s['login_logo_width'] ) : 320;
			$h    = absint( $s['login_logo_height'] ) ? absint( $s['login_logo_height'] ) : 84;
			$css .= sprintf(
				'.login h1 a{background-image:url("%1$s");background-size:contain;background-position:center;width:%2$dpx;height:%3$dpx;max-width:100%%;}',
				esc_url( $s['login_logo'] ),
				$w,
				$h
			);
		}

		$bg_color = sanitize_hex_color( $s['login_bg_color'] );
		if ( $bg_color ) {
			$css .= 'body.login{background-color:' . $bg_color . ';}';
		}
		if ( $s['login_bg_image'] ) {
			$css .= sprintf( 'body.login{background-image:url("%s");background-size:cover;background-position:center;background-repeat:no-repeat;}', esc_url( $s['login_bg_image'] ) );
		}

		$colour_rules = array(
			'login_form_bg_color' => '.login form{background-color:%s;}',
			'login_label_color'   => '.login form label,.login form .forgetmenot label{color:%s;}',
			'login_button_color'  => '.login .button-primary{background-color:%1$s;border-color:%1$s;}',
			'login_button_text'   => '.login .button-primary{color:%s;}',
			'login_button_hover'  => '.login .button-primary:hover,.login .button-primary:focus{background-color:%1$s;border-color:%1$s;}',
			'login_link_color'    => '.login #nav a,.login #backtoblog a,.login .privacy-policy-link{color:%s;}',
		);

		foreach ( $colour_rules as $key => $rule ) {
			$colour = sanitize_hex_color( $s[ $key ] );
			if ( $colour ) {
				$css .= sprintf( $rule, $colour );
			}
		}

		if ( '' !== $css ) {
			wp_add_inline_style( 'login', $css );
		}
	}
}

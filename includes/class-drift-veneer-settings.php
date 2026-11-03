<?php
/**
 * The plugin's stored settings and their defaults.
 *
 * One option (self::OPTION) holds the branding and login settings. Anything
 * left blank falls back to self::defaults(). Out of the box the only default
 * that changes anything is "hide WordPress branding"; every logo, colour and
 * footer credit is opt-in.
 *
 * Defaults are filterable (`drift_veneer_defaults`) so an agency can ship its
 * own brand to every client site from a theme or mu-plugin, without anyone
 * opening the settings screen.
 *
 * @package Drift_Veneer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings storage.
 */
final class Drift_Veneer_Settings {

	const OPTION = 'drift_veneer_settings';

	/**
	 * Default values, used for any field left blank.
	 *
	 * @return array
	 */
	public static function defaults() {
		$defaults = array(
			// Branding.
			'hide_wp_branding'    => '1',
			'admin_bar_logo'      => '',
			'admin_bar_logo_url'  => '',
			'footer_text'         => '',
			'footer_url'          => '',

			// Login screen.
			'login_logo'          => '',
			'login_logo_width'    => 320,
			'login_logo_height'   => 84,
			'login_bg_color'      => '',
			'login_bg_image'      => '',
			'login_form_bg_color' => '',
			'login_label_color'   => '',
			'login_button_color'  => '',
			'login_button_text'   => '',
			'login_button_hover'  => '',
			'login_link_color'    => '',
		);

		/**
		 * Filters the default settings.
		 *
		 * Only keys already in the array are used; new keys are ignored.
		 *
		 * @param array $defaults Default settings.
		 */
		$filtered = apply_filters( 'drift_veneer_defaults', $defaults );

		return is_array( $filtered ) ? array_merge( $defaults, array_intersect_key( $filtered, $defaults ) ) : $defaults;
	}

	/**
	 * Raw saved values, no defaults applied. This is what the form fields
	 * show, so a blank field visibly means "using the default".
	 *
	 * @return array
	 */
	public static function saved() {
		$saved = get_option( self::OPTION, array() );
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * Effective settings: saved values with blanks filled from defaults().
	 * hide_wp_branding is a checkbox, so a saved '0' is kept, not defaulted.
	 *
	 * @return array
	 */
	public static function get() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$defaults = self::defaults();
		$saved    = self::saved();
		$out      = $defaults;

		foreach ( $defaults as $key => $default ) {
			if ( 'hide_wp_branding' === $key ) {
				$out[ $key ] = isset( $saved[ $key ] ) ? (string) $saved[ $key ] : $default;
				continue;
			}
			if ( isset( $saved[ $key ] ) && '' !== $saved[ $key ] && 0 !== $saved[ $key ] ) {
				$out[ $key ] = $saved[ $key ];
			}
		}

		$cache = $out;
		return $out;
	}

	/**
	 * Sanitises and saves submitted settings.
	 *
	 * @param array $input Unslashed $_POST['drift_veneer'].
	 * @return void
	 */
	public static function save( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			$raw = isset( $input[ $key ] ) ? $input[ $key ] : '';

			switch ( $key ) {
				case 'hide_wp_branding':
					$out[ $key ] = ! empty( $raw ) ? '1' : '0';
					break;

				case 'login_logo_width':
				case 'login_logo_height':
					$out[ $key ] = '' === $raw ? '' : min( 1000, absint( $raw ) );
					break;

				case 'admin_bar_logo':
				case 'admin_bar_logo_url':
				case 'footer_url':
				case 'login_logo':
				case 'login_bg_image':
					$out[ $key ] = esc_url_raw( (string) $raw );
					break;

				case 'footer_text':
					$out[ $key ] = sanitize_text_field( (string) $raw );
					break;

				default:
					// Every remaining field is a colour.
					$out[ $key ] = (string) sanitize_hex_color( (string) $raw );
			}
		}

		update_option( self::OPTION, $out, false );
	}
}

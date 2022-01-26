<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/estevao90/learndash-extend-expiry
 * @since      1.0.0
 *
 * @package    Learndash_Extend_Expiry
 * @subpackage Learndash_Extend_Expiry/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Learndash_Extend_Expiry
 * @subpackage Learndash_Extend_Expiry/includes
 * @author     Estevão Costa <estevao90@gmail.com>
 */
class Learndash_Extend_Expiry_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'learndash-extend-expiry',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

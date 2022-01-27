<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/estevao90/learndash-extend-expiry
 * @since      1.0.0
 *
 * @package    Learndash_Extend_Expiry
 * @subpackage Learndash_Extend_Expiry/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks related to the admin-specific functions.
 *
 * @package    Learndash_Extend_Expiry
 * @subpackage Learndash_Extend_Expiry/admin
 * @author     Estevão Costa <estevao90@gmail.com>
 */
class Learndash_Extend_Expiry_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// load LearnDash dependents functions
		add_action( 'plugins_loaded', array( $this, 'require_learndash_dependents' ) );
	}

	public function show_requirements() {
		if ( ! defined( 'LEARNDASH_VERSION' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' .
				sprintf(
					/* translators: %s: LearnDash URL */
					esc_html__( 'LearnDash LMS - Extend Expiry requires LearnDash to be installed and active. Please download and install %s.', 'learndash-extend-expiry' ),
					'<a href="https://www.learndash.com/" target="_blank">LearnDash</a>'
				) .
				'</strong></p></div>';
		}
	}

	public function require_learndash_dependents() {
		if ( defined( 'LEARNDASH_LMS_PLUGIN_DIR' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-ld-extend-expiry-helper.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-ld-extend-expiry-control.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/settings/class-ld-extend-expiry-settings.php';

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/payments/class-ld-extend-expiry-payment-integration.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/payments/paypal/class-ld-extend-expiry-paypal.php';
		}
	}
}

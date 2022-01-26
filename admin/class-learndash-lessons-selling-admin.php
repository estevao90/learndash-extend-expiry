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
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Learndash_Extend_Expiry
 * @subpackage Learndash_Extend_Expiry/admin
 * @author     Estevão Costa <estevao90@gmail.com>
 */
class Learndash_Lessons_Selling_Admin {

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

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Learndash_Lessons_Selling_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Learndash_Lessons_Selling_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/learndash-lessons-selling-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/learndash-lessons-selling-admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);

	}

	public function show_requirements() {
		if ( ! defined( 'LEARNDASH_VERSION' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>' .
				sprintf(
					/* translators: %s: LearnDash URL */
					esc_html__( 'Sell Lessons for LearnDash requires LearnDash to be installed and active. Please download and install %s.', 'learndash-lessons-selling' ),
					'<a href="https://www.learndash.com/" target="_blank">LearnDash</a>'
				) .
				'</strong></p></div>';
		}
	}

	public function require_learndash_dependents() {
		if ( defined( 'LEARNDASH_LMS_PLUGIN_DIR' ) ) {
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/class-ld-settings-metaboxes.php';

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-imm-learndash-ls-access-control.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-imm-learndash-ls-template-manager.php';

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/settings/class-imm-learndash-settings-helper.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/settings/class-imm-learndash-sell-settings.php';

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ld-sell/class-imm-learndash-lesson-sell.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ld-sell/class-imm-learndash-quiz-sell.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ld-sell/class-imm-learndash-topic-sell.php';

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/payments/class-imm-learndash-ls-payment-integration.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/payments/paypal/class-imm-learndash-ls-paypal.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/payments/stripe/class-imm-learndash-ls-stripe.php';
		}
	}
}

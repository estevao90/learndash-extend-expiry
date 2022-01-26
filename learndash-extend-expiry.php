<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/estevao90/learndash-extend-expiry
 * @since             1.0.0
 * @package           Learndash_Extend_Expiry
 *
 * @wordpress-plugin
 * Plugin Name:       LearnDash LMS - Extend Expiry
 * Plugin URI:        https://github.com/estevao90/learndash-extend-expiry
 * Description:       Allow user purchase a course extension for a LearnDash course with a fixed expiry date.
 * Version:           1.0.0
 * Author:            Estevão Costa
 * Author URI:        https://github.com/estevao90/learndash-extend-expiry
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       learndash-extend-expiry
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'LEARNDASH_EXTEND_EXPIRY_VERSION', '1.0.0' );

if ( ! defined( 'LEARNDASH_EXTEND_EXPIRY_PLUGIN_DIR' ) ) {
	/**
	 * Define LearnDash Extend Expiry plugin - Set the plugin install path.
	 *
	 * Will be set based on the WordPress define `WP_PLUGIN_DIR`.
	 *
	 * @since 1.0.0
	 * @uses WP_PLUGIN_DIR
	 *
	 * @var string Directory path to plugin install directory.
	 */
	define( 'LEARNDASH_EXTEND_EXPIRY_PLUGIN_DIR', trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) . '/' . basename( dirname( __FILE__ ) ) ) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-learndash-extend-expiry-activator.php
 */
function activate_learndash_extend_expiry() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-learndash-extend-expiry-activator.php';
	Learndash_Extend_Expiry_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-learndash-extend-expiry-deactivator.php
 */
function deactivate_learndash_extend_expiry() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-learndash-extend-expiry-deactivator.php';
	Learndash_Extend_Expiry_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_learndash_extend_expiry' );
register_deactivation_hook( __FILE__, 'deactivate_learndash_extend_expiry' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-learndash-extend-expiry.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_learndash_extend_expiry() {

	$plugin = new Learndash_Extend_Expiry();
	$plugin->run();

}
run_learndash_extend_expiry();

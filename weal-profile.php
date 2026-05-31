<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * Plugin Name:       Weal Profile
 * Plugin URI:        https://weal.cloud
 * Description:       Creates a personal account page where logged-in users can manage their profile information and review their site activity. Client-side caching for improved performance.
 * Version:           1.3.0
 * Author:            leouix
 * Author URI:        https://github.com/Leouix/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       weal-profile
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WEAL_PROFILE_VERSION', '1.3.0' );
define( 'WEAL_PROFILE_DB_VERSION', '1.2.0' );
define( 'WEAL_PROFILE_PLUGIN_FILE', __FILE__ );
define( 'WEAL_PROFILE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEAL_PROFILE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-weal-profile-activator.php
 */
function weal_profile_activate() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-weal-profile-activator.php';
	Weal_Profile_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-weal-profile-deactivator.php
 */
function weal_profile_deactivate() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-weal-profile-deactivator.php';
	Weal_Profile_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'weal_profile_activate' );
register_deactivation_hook( __FILE__, 'weal_profile_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-weal-profile.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function weal_profile_run() {
	$plugin = new Weal_Profile();
	$plugin->run();
}
weal_profile_run();

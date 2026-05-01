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
 * Fired during plugin activation
 *
 * @link  https://weal.cloud
 * @since 1.0.0
 *
 * @package    Weal_Profile
 * @subpackage Weal_Profile/includes
 */

require_once plugin_dir_path( __FILE__ ) . 'class-settings-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'class-public-page-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'class-activation-manager.php';

use MyAccountPage\Includes\Activation_Manager;

/**
 * Activator class.
 */
class Weal_Profile_Activator {



	/**
	 * Activate plugin.
	 */
	public static function activate() {
		( new Activation_Manager() )->activate();
	}
}

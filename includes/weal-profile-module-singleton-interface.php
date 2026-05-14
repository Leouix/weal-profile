<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Weal_Profile_Module_Singleton_Interface {

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance();

	/**
	 * Prevent serialization.
	 *
	 * @return void
	 */
	public function __wakeup();
}

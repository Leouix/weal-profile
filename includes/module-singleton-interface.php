<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ModuleSingletonInterface {

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

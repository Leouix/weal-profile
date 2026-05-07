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

	public static function instance();
	public function __wakeup();
}

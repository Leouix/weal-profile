<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation manager class.
 */
class Activation_Manager {


	/**
	 * Settings manager.
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;
	/**
	 * Page manager.
	 *
	 * @var Public_Page_Manager
	 */
	private $page_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_manager = new Settings_Manager();
		$this->page_manager     = new Public_Page_Manager();
	}

	/**
	 * Activate plugin.
	 */
	public function activate() {
		$this->create_database_table();
		\WealProfile\Includes\Comment_Votes\Comment_Votes::create_table();
		$this->settings_manager->clear_cache();
		$this->initialize_settings();
	}

	/**
	 * Create database table.
	 */
	private function create_database_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'weal_profile_plugin';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_page_url varchar(255) NOT NULL,
            fields_allowed_json varchar(255) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Initialize settings.
	 */
	private function initialize_settings() {
		$existing_url = $this->settings_manager->get_user_page_url();

		if ( $existing_url ) {
			return;
		}

		$slug = $this->page_manager->find_available_slug();
		$this->page_manager->create_page( $slug );

		$default_fields = $this->settings_manager->get_default_fields();
		$this->settings_manager->save_settings( $slug, $default_fields );
	}
}

<?php
/**
 * Database Creator.
 *
 * Creates the plugin database table.
 *
 * @package weal-profile
 */

namespace WealProfile\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DB Creator Class.
 *
 * @package weal-profile
 */
class DB_Creator {




	/**
	 * Create database table.
	 *
	 * @return void
	 */
	public function create_db_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'my_account_page_plugin';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_page_url varchar(255) NOT NULL,
			fields_allowed_json varchar(255) NULL,
			is_comments_allowed tinyint NULL,
			is_users_allowed tinyint NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$this->insert_empty_data();
	}

	/**
	 * Insert empty data.
	 *
	 * @return void
	 */
	private function insert_empty_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'my_account_page_plugin';
		$wpdb->insert(
			$table_name,
			array(
				'user_page_url'       => 'my-account',
				'fields_allowed_json' => wp_json_encode(
					array(
						'display_name',
						'nickname',
						'first_name',
						'last_name',
						'description',
						'user_url',
					)
				),
			),
			array( '%s', '%s' )
		);
	}
}

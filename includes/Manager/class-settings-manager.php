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
 * Settings manager class.
 */
class Settings_Manager {


	private const TABLE_NAME         = 'weal_profile_plugin';
	private const CACHE_GROUP        = 'weal_profile_plugin';
	private const CACHE_KEY_SETTINGS = 'weal_profile_plugin_settings';
	private const CACHE_KEY_URL      = 'plugin_user_page_url';
	private const SETTINGS_ID        = 1;

	/**
	 * Default fields.
	 *
	 * @var array
	 */
	private $default_fields = array(
		'display_name',
		'user_url',
		'nickname',
		'description',
	);

	/**
	 * Get plugin settings.
	 */
	public function get_settings() {
		if ( ! $this->is_table_exists() ) {
			return array(
				'user_page_url'  => null,
				'fields_allowed' => $this->default_fields,
			);
		}

		$cached = wp_cache_get( self::CACHE_KEY_SETTINGS, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				$table,
				self::SETTINGS_ID
			)
		);

		if ( ! $row ) {
			return array(
				'user_page_url'  => null,
				'fields_allowed' => $this->default_fields,
			);
		}

		$settings = array(
			'user_page_url'  => $row->user_page_url,
			'fields_allowed' => json_decode( $row->fields_allowed_json, true ) ? json_decode( $row->fields_allowed_json, true ) : $this->default_fields,
		);

		wp_cache_set( self::CACHE_KEY_SETTINGS, $settings, self::CACHE_GROUP, 3600 );

		return $settings;
	}

	/**
	 * Get user page URL.
	 */
	public function get_user_page_url() {
		$cached = wp_cache_get( self::CACHE_KEY_URL, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$settings = $this->get_settings();
		$url      = $settings['user_page_url'] ?? null;

		wp_cache_set( self::CACHE_KEY_URL, $url, self::CACHE_GROUP, 3600 );

		return $url;
	}

	/**
	 * Save plugin settings.
	 *
	 * @param string|null $user_page_url User page URL.
	 * @param array       $fields_allowed Allowed fields.
	 */
	public function save_settings( $user_page_url, $fields_allowed ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;

		$data = array();

		if ( null !== $user_page_url ) {
			$data['user_page_url'] = sanitize_text_field( $user_page_url );
		}

		$data['fields_allowed_json'] = wp_json_encode( array_map( 'sanitize_text_field', $fields_allowed ) );

		$format = array( '%s', '%s' );

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE id = %d',
				$table,
				self::SETTINGS_ID
			)
		);

		if ( $existing ) {
			$result = $wpdb->update(
				$table,
				$data,
				array( 'id' => self::SETTINGS_ID ),
				$format,
				array( '%d' )
			);
		} else {
			$data['id'] = self::SETTINGS_ID;
			$result     = $wpdb->insert(
				$table,
				$data,
				array_merge( $format, array( '%d' ) )
			);
		}

		$this->clear_cache();

		return false !== $result;
	}

	/**
	 * Check if table exists.
	 */
	public function is_table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$cache_key = 'is_table_exists';
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );

		$exists = ( $result === $table_name );
		wp_cache_set( $cache_key, $exists, self::CACHE_GROUP, 3600 );

		return $exists;
	}

	/**
	 * Clear plugin cache.
	 */
	public function clear_cache() {
		wp_cache_delete( self::CACHE_KEY_SETTINGS, self::CACHE_GROUP );
		wp_cache_delete( self::CACHE_KEY_URL, self::CACHE_GROUP );
	}

	/**
	 * Get default fields.
	 */
	public function get_default_fields() {
		return $this->default_fields;
	}
}

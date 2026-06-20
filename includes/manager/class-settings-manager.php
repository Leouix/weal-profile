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

	private const OPTION_NAME              = 'weal_profile_settings';
	private const ACHIEVEMENTS_OPTION_NAME = 'weal_profile_achievements_settings';

	/**
	 * Request-level settings cache.
	 *
	 * @var array|null
	 */
	private static $settings_cache = null;

	/**
	 * Request-level achievements settings cache.
	 *
	 * @var array|null
	 */
	private static $achievements_settings_cache = null;

	/**
	 * Default fields for the profile.
	 *
	 * @var array
	 */
	private $default_fields = array(
		'display_name',
		'user_url',
		'nickname',
		'description',
		'avatar',
	);

	/**
	 * Get plugin settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( null === self::$settings_cache ) {
			self::$settings_cache = get_option( self::OPTION_NAME, array() );
		}

		$settings = self::$settings_cache;

		return array(
			'user_page_url'         => $settings['user_page_url'] ?? null,
			'user_page_id'          => isset( $settings['user_page_id'] ) ? (int) $settings['user_page_id'] : 0,
			'fields_allowed'        => $settings['fields_allowed'] ?? $this->default_fields,
			'comment_votes_enabled' => $settings['comment_votes_enabled'] ?? true,
		);
	}

	/**
	 * Get user page URL.
	 *
	 * @return string|null
	 */
	public function get_user_page_url() {
		$settings = $this->get_settings();
		return $settings['user_page_url'] ?? null;
	}

	/**
	 * Save plugin settings.
	 *
	 * @param string $user_page_url        User page URL.
	 * @param array  $fields_allowed       Allowed fields.
	 * @param bool   $comment_votes_enabled Enable comment votes.
	 * @param int    $user_page_id         Page ID of the profile page.
	 * @return bool
	 */
	public function save_settings( string $user_page_url, $fields_allowed, $comment_votes_enabled = true, $user_page_id = 0 ) {

		$data = array(
			'user_page_url'         => sanitize_text_field( $user_page_url ),
			'user_page_id'          => absint( $user_page_id ),
			'fields_allowed'        => array_map( 'sanitize_text_field', $fields_allowed ),
			'comment_votes_enabled' => (bool) $comment_votes_enabled,
		);

		$updated              = update_option( self::OPTION_NAME, $data );
		self::$settings_cache = $data;

		return $updated;
	}

	/**
	 * Save achievements settings.
	 *
	 * @param array $data Achievements settings data.
	 * @return bool
	 */
	public function save_achievements_settings( array $data ) {
		$updated                           = update_option( self::ACHIEVEMENTS_OPTION_NAME, $data );
		self::$achievements_settings_cache = $data;

		return $updated;
	}

	/**
	 * Get achievements settings.
	 *
	 * @return array
	 */
	public function get_achievements_settings() {
		if ( null === self::$achievements_settings_cache ) {
			self::$achievements_settings_cache = get_option( self::ACHIEVEMENTS_OPTION_NAME, array() );
		}

		return self::$achievements_settings_cache;
	}

	/**
	 * Get default fields.
	 *
	 * @return array
	 */
	public function get_default_fields() {
		return $this->default_fields;
	}
}

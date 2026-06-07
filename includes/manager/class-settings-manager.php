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
		$settings = get_option( self::OPTION_NAME, array() );

		return array(
			'user_page_url'         => $settings['user_page_url'] ?? null,
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
	 * @return bool
	 */
	public function save_settings( string $user_page_url, $fields_allowed, $comment_votes_enabled = true ) {

		$data = array(
			'user_page_url'         => sanitize_text_field( $user_page_url ),
			'fields_allowed'        => array_map( 'sanitize_text_field', $fields_allowed ),
			'comment_votes_enabled' => (bool) $comment_votes_enabled,
		);

		return update_option( self::OPTION_NAME, $data );
	}

	/**
	 * Save achievements settings.
	 *
	 * @param array $data Achievements settings data.
	 * @return bool
	 */
	public function save_achievements_settings( array $data ) {
		return update_option( self::ACHIEVEMENTS_OPTION_NAME, $data );
	}

	/**
	 * Get achievements settings.
	 *
	 * @return array
	 */
	public function get_achievements_settings() {
		return get_option( self::ACHIEVEMENTS_OPTION_NAME, array() );
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

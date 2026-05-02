<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Exception;
use WealProfile\Includes\Manager\Public_Page_Manager;
use WealProfile\Includes\Manager\Settings_Manager;

/**
 * Admin Settings Class.
 *
 * @package weal-profile
 */
class Admin_Settings {

	/**
	 * Text domain.
	 *
	 * @var string
	 */
	private const TEXT_DOMAIN = 'weal-profile';
	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'my_account_admin_save';
	/**
	 * Nonce field.
	 *
	 * @var string
	 */
	private const NONCE_FIELD = 'my_account_nonce';

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
	 * Current settings.
	 *
	 * @var array
	 */
	private $current_settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_manager = new Settings_Manager();
		$this->page_manager     = new Public_Page_Manager();
		$this->current_settings = $this->settings_manager->get_settings();
	}

	/**
	 * Handle saving settings.
	 *
	 * @param  array $post_data Post data.
	 * @return void
	 * @throws Exception On error.
	 */
	public function handle_saving( $post_data ) {
		$this->verify_nonce( $post_data );

		$fields       = $this->validate_fields( $post_data );
		$validatedUrl = $this->validate_url( $post_data );

		if ( ! empty( $validatedUrl ) ) {
			$this->process_url_change( $validatedUrl );
		}

		$requiredUrlForUpdate = $validatedUrl ?? $this->current_settings['user_page_url'];
		$this->settings_manager->save_settings( $requiredUrlForUpdate, $fields );
	}

	/**
	 * Verify nonce.
	 *
	 * @param  array $post_data Post data.
	 * @return void
	 * @throws Exception On verification failure.
	 */
	private function verify_nonce( $post_data ) {
		$nonce = $post_data[ self::NONCE_FIELD ] ?? '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			throw new Exception( esc_html__( 'Security check failed', 'weal-profile' ) );
		}
	}

	/**
	 * Validate fields.
	 *
	 * @param  array $post_data Post data.
	 * @return array Validated fields.
	 * @throws Exception On validation failure.
	 */
	private function validate_fields( $post_data ) {
		$expected_fields = array(
			'display_name',
			'user_url',
			'nickname',
			'first_name',
			'last_name',
			'description',
			'avatar',
		);

		if ( ! isset( $post_data['show_user_fields_checkbox'] ) || ! is_array( $post_data['show_user_fields_checkbox'] ) ) {
			throw new Exception( esc_html__( 'Invalid checkbox data', 'weal-profile' ) );
		}

		$fields = array_map( 'sanitize_text_field', $post_data['show_user_fields_checkbox'] );

		foreach ( $fields as $field ) {
			if ( ! in_array( $field, $expected_fields, true ) ) {
				throw new Exception(
					// translators: %s: The invalid checkbox field name.
					sprintf( esc_html__( 'Invalid checkbox field: %s', 'weal-profile' ), esc_html( $field ) )
				);
			}
		}

		return $fields;
	}

	/**
	 * Validate URL.
	 *
	 * @param  array $post_data Post data.
	 * @return string|null Validated URL.
	 * @throws Exception If URL is invalid.
	 */
	private function validate_url( $post_data ) {
		if ( ! isset( $post_data['mya_url'] ) || ! is_string( $post_data['mya_url'] ) ) {
			return null;
		}

		$new_url = sanitize_text_field( $post_data['mya_url'] );

		if ( $this->page_manager->slug_exists( $new_url ) ) {
			return null;
		}

		return $new_url;
	}

	/**
	 * Process URL change.
	 *
	 * @param  string $new_url New URL.
	 * @return void
	 * @throws Exception On error.
	 */
	private function process_url_change( $new_url ) {
		$current_url = $this->current_settings['user_page_url'] ?? null;
		$this->page_manager->update_page_url( $current_url, $new_url );
	}

	/**
	 * Get user page URL.
	 *
	 * @return string User page URL.
	 */
	public function get_user_page_url() {
		return $this->current_settings['user_page_url'] ?? '';
	}

	/**
	 * Get fields allowed.
	 *
	 * @return array Fields allowed.
	 */
	public function get_fields_allowed() {
		return $this->current_settings['fields_allowed'] ?? array();
	}

	/**
	 * Get admin settings page.
	 *
	 * @return void
	 */
	public function get_my_account_settings_page() {
		$user_page_url        = $this->get_user_page_url();
		$fields_allowed_array = $this->get_fields_allowed();

		include __DIR__ . '/partials/admin-settings-page.php';
	}
}

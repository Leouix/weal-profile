<?php
/**
 * Activation manager class.
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
	 *
	 * @throws \Exception If activation fails.
	 */
	public function activate() {

		$this->initialize_settings();
		$this->create_comment_votes_table();

		flush_rewrite_rules();
	}

	/**
	 * Create comment votes table.
	 */
	private function create_comment_votes_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once WEAL_PROFILE_PLUGIN_DIR . 'includes/comment-votes/class-comment-votes.php';
		\WealProfile\Includes\Comment_Votes\Comment_Votes::create_table();
		update_option( 'weal_profile_db_version', WEAL_PROFILE_DB_VERSION );
	}

	/**
	 * Initialize settings.
	 *
	 * @throws \Exception If settings initialization fails.
	 */
	private function initialize_settings() {
		$existing_url = $this->settings_manager->get_user_page_url();

		// Если настройка уже есть в wp_options, ничего не делаем.
		if ( $existing_url ) {
			return;
		}

		// 1. Находим свободный slug для страницы (например, 'my-profile')
		$slug = $this->page_manager->find_available_slug();

		// 2. Создаем физическую страницу в WordPress
		$this->page_manager->create_page( $slug );

		// 3. Сохраняем настройки в таблицу wp_options
		$default_fields = $this->settings_manager->get_default_fields();
		$this->settings_manager->save_settings( $slug, $default_fields );
	}
}

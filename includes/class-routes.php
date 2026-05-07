<?php
/**
 * REST API Routes.
 *
 * @package weal-profile
 */

namespace WealProfile\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ModuleSingletonInterface;
use WealProfile\Admin\Admin_Settings;
use WealProfile\Includes\Manager\Settings_Manager;
use WealProfile\Public\Info_Tab_Manager;
use WP_REST_Request;
use WP_REST_Response;
use Exception;

/**
 * Routes Class.
 *
 * @package weal-profile
 */
class Routes implements ModuleSingletonInterface {

	/**
	 * The single instance of the class.
	 *
	 * @var Routes|null
	 */
	private static $instance = null;

	/**
	 * Current user.
	 *
	 * @var mixed
	 */
	private $current_user;

	/**
	 * Check user permission.
	 *
	 * @return true|\WP_Error
	 */
	public static function check_user_permission() {
		return is_user_logged_in()
			? true
			: new \WP_Error( 'rest_not_logged_in', esc_html__( 'Login required', 'weal-profile' ), array( 'status' => 401 ) );
	}
	/**
	 * Admin settings.
	 *
	 * @var mixed
	 */
	private $admin_settings;

	/**
	 * Returns the main instance of the class.
	 *
	 * @param mixed $admin_settings Admin settings (used only on first call).
	 * @return Routes
	 */
	public static function instance( $admin_settings = null ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $admin_settings );
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent creating a new instance via 'new'.
	 *
	 * @param mixed $admin_settings Admin settings.
	 */
	private function __construct( $admin_settings ) {
		$this->admin_settings = $admin_settings;
	}

	/**
	 * Private clone method to prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Private wakeup method to prevent unserializing of the instance.
	 *
	 * @throws \Exception If attempting to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function route_reg() {

		register_rest_route(
			'my-account/v1',
			'/switch-tab-ajax/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'switch_tab_ajax' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'my-account/v1',
			'/info-tab/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_public_user_info' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'my-account/v1',
			'/admin-save-page-settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'admin_save_page_settings' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);
	}

	/**
	 * Admin save page settings.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 * @throws Exception On error.
	 */
	public function admin_save_page_settings( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new Exception( esc_html__( 'Access denied', 'weal-profile' ), 403 );
		}

		$this->current_user = get_current_user_id();

		if ( ! $this->current_user ) {
			throw new Exception( esc_html__( 'Login User ID is required.', 'weal-profile' ) );
		}

		$post_data     = $request->get_params();
		$admin_manager = new Admin_Settings();
		$admin_manager->handle_saving( $post_data );

		return new WP_REST_Response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Save public user info.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 * @throws Exception On error.
	 */
	public function save_public_user_info( $request ) {
		$this->verify_nonce( $request );
		$this->current_user = get_current_user_id();

		if ( ! $this->current_user ) {
			throw new Exception( esc_html__( 'User not logged in', 'weal-profile' ) );
		}

		$post_data      = $request->get_params();
		$info_tab_class = new Info_Tab_Manager( $this->current_user, $this->admin_settings );
		$info_tab_class->handle_user_saving( $post_data );

		return new WP_REST_Response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Verify nonce.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return void
	 * @throws Exception On error.
	 */
	private function verify_nonce( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			throw new Exception( esc_html__( 'Invalid nonce', 'weal-profile' ), 401 );
		}
	}

	/**
	 * Switch tab AJAX.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 * @throws Exception On error.
	 */
	public function switch_tab_ajax( $request ) {
		$this->verify_nonce( $request );
		$this->current_user = get_current_user_id();

		if ( ! $this->current_user ) {
			throw new Exception( esc_html__( 'User not logged in', 'weal-profile' ) );
		}

		$post_data = $request->get_params();

		if ( empty( $post_data['tabName'] ) ) {
			throw new Exception( esc_html__( 'Tab Name is required.', 'weal-profile' ) );
		}

		switch ( $post_data['tabName'] ) {
			case 'users':
				$html = $this->users_tab();
				break;
			case 'activity':
				$html = $this->my_comments_tab();
				break;
			case 'info':
				$html = $this->info_tab();
				break;
			default:
				return new WP_REST_Response(
					array( 'error' => esc_html__( 'Invalid tab', 'weal-profile' ) ),
					400
				);
		}

		return new WP_REST_Response(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * Users tab.
	 *
	 * @return string
	 */
	private function users_tab() {
		ob_start();
		include WEAL_PROFILE_PLUGIN_DIR . 'public/partials/tab-users.php';
		return ob_get_clean();
	}

	/**
	 * My comments tab.
	 *
	 * @return string
	 */
	private function my_comments_tab() {
		$args          = array(
			'user_id' => $this->current_user,
			'status'  => 'approve',
		);
		$user_comments = get_comments( $args );

		ob_start();
		include WEAL_PROFILE_PLUGIN_DIR . 'public/partials/tab-my-comments.php';
		return ob_get_clean();
	}

	/**
	 * Info tab.
	 *
	 * @return string
	 */
	private function info_tab() {
		$info_tab_class = new Info_Tab_Manager( $this->current_user, $this->admin_settings );
		ob_start();
		$info_tab_class->get_user_data();
		return ob_get_clean();
	}
}

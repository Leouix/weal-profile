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
use WealProfile\Includes\Comment_Votes\Comment_Votes;
use WealProfile\Includes\Comment_Votes\Likes_Vote_Service;
use WealProfile\Includes\Manager\Settings_Manager;
use WealProfile\Public\Info_Tab_Manager;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
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

		register_rest_route(
			'weal-profile/v1',
			'/comment-vote',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_vote' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
				'args'                => array(
					'comment_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'vote_type'  => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array( 'like', 'dislike' ),
					),
				),
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
		include plugin_dir_path( __DIR__ ) . 'public/partials/tab-users.php';
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

		$settings            = ( new Settings_Manager() )->get_settings();
		$comment_votes_enabled = $settings['comment_votes_enabled'] ?? true;

		if ( $comment_votes_enabled ) {
			$likes_service = new Likes_Vote_Service();
			$vote_data     = $likes_service->get_user_vote_data( $this->current_user );
		} else {
			$vote_data = array(
				'total_likes'    => 0,
				'total_dislikes' => 0,
				'top_comments'   => array(),
			);
		}

		$total_likes    = $vote_data['total_likes'] ?? 0;
		$total_dislikes = $vote_data['total_dislikes'] ?? 0;
		$top_comments   = $vote_data['top_comments'] ?? array();

		ob_start();
		include plugin_dir_path( __DIR__ ) . 'public/partials/tab-my-comments.php';
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

	/**
	 * Handle vote request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_vote( $request ) {
		global $wpdb;

		$settings = ( new Settings_Manager() )->get_settings();
		if ( empty( $settings['comment_votes_enabled'] ) ) {
			return new WP_Error(
				'votes_disabled',
				esc_html__( 'Comment votes are disabled', 'weal-profile' ),
				array( 'status' => 403 )
			);
		}

		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'invalid_nonce',
				esc_html__( 'Invalid nonce', 'weal-profile' ),
				array( 'status' => 403 )
			);
		}

		$comment_id = $request->get_param( 'comment_id' );
		$vote_type  = $request->get_param( 'vote_type' );
		$user_id    = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_Error(
				'user_not_found',
				esc_html__( 'User not found', 'weal-profile' ),
				array( 'status' => 401 )
			);
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return new WP_Error(
				'comment_not_found',
				esc_html__( 'Comment not found', 'weal-profile' ),
				array( 'status' => 404 )
			);
		}

		$table_name = $wpdb->prefix . Comment_Votes::TABLE_NAME;
		$is_liked   = ( 'like' === $vote_type ) ? 1 : 0;

		$existing_vote = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT id, is_liked FROM %i WHERE comment_id = %d AND user_id = %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comment_id,
				$user_id
			)
		);

		if ( ! $existing_vote ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table_name,
				array(
					'comment_id' => $comment_id,
					'user_id'    => $user_id,
					'is_liked'   => $is_liked,
				),
				array( '%d', '%d', '%d' )
			);
			$user_status = $is_liked ? 'liked' : 'disliked';
		} elseif ( (int) $existing_vote->is_liked === $is_liked ) {
			$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table_name,
				array(
					'id' => $existing_vote->id,
				),
				array( '%d' )
			);
			$user_status = 'neutral';
		} else {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table_name,
				array( 'is_liked' => $is_liked ),
				array(
					'id' => $existing_vote->id,
				),
				array( '%d' ),
				array( '%d' )
			);
			$user_status = $is_liked ? 'liked' : 'disliked';
		}

		$counts = Comment_Votes::update_vote_counts( $comment_id );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'likes'       => $counts['likes'],
				'dislikes'    => $counts['dislikes'],
				'user_status' => $user_status,
			)
		);
	}
}

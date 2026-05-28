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

use Exception;
use WealProfile\Admin\Admin_Settings;
use WealProfile\Includes\Comment_Votes\Comments_Service;
use WealProfile\Includes\Comment_Votes\Likes_Vote_Service;
use WealProfile\Includes\Manager\Settings_Manager;
use WealProfile\Public\Info_Tab_Manager;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Routes Class.
 *
 * @package weal-profile
 */
class Routes implements Weal_Profile_Module_Singleton_Interface {

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
	 * Check admin permission.
	 *
	 * @return true|\WP_Error
	 */
	public static function check_admin_permission() {
		return current_user_can( 'manage_options' )
			? true
			: new \WP_Error( 'rest_forbidden', esc_html__( 'Access denied', 'weal-profile' ), array( 'status' => 403 ) );
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
			'weal-profile/v1',
			'/switch-tab-ajax/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'switch_tab_ajax' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'weal-profile/v1',
			'/info-tab/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_public_user_info' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'weal-profile/v1',
			'/admin-save-page-settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'admin_save_page_settings' ),
				'permission_callback' => array( __CLASS__, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			'weal-profile/v1',
			'/my-account/posts/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'my_account_posts' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'weal-profile/v1',
			'/my-account/comments/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'my_account_comments' ),
				'permission_callback' => array( __CLASS__, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'weal-profile/v1',
			'/comment-vote',
			array(
				'methods'             => 'POST',
				'callback'            => array( new Likes_Vote_Service(), 'handle_vote' ),
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

		$page      = isset( $post_data['page'] ) ? max( 1, intval( $post_data['page'] ) ) : 1;
		$load_more = ! empty( $post_data['load_more'] );

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
	 * My comments tab — renders the activity container with subtabs,
	 * or directly shows comments if the user has no posts.
	 *
	 * @return string
	 */
	private function my_comments_tab() {
		$current_user_id = $this->current_user ? (int) $this->current_user : get_current_user_id();
		$has_posts       = count_user_posts( $current_user_id ) > 0;

		if ( ! $has_posts ) {
			return $this->get_comments_html( 1 );
		}

		$active_subtab = isset( $_GET['b'] ) && 'c' === $_GET['b'] ? 'comments' : 'posts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		ob_start();
		require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/tab-my-activity.php';
		return ob_get_clean();
	}

	/**
	 * Render comments HTML for a given page.
	 *
	 * @param  int $page        Page number.
	 * @param  int $total_pages Optional. Pre-computed total pages to avoid duplicate count query.
	 * @return string
	 */
	private function get_comments_html( $page = 1, $total_pages = null ) {
		$per_page = 10;
		$offset   = ( $page - 1 ) * $per_page;

		$comment_query = new \WP_Comment_Query();
		$user_comments = $comment_query->query(
			array(
				'user_id' => $this->current_user,
				'status'  => 'approve',
				'number'  => $per_page,
				'offset'  => $offset,
			)
		);

		if ( null === $total_pages ) {
			$total_pages = (int) ceil( $comment_query->found_comments / $per_page );
		}

		$settings              = ( new Settings_Manager() )->get_settings();
		$comment_votes_enabled = $settings['comment_votes_enabled'] ?? true;

		if ( $comment_votes_enabled ) {
			$likes_service = new Likes_Vote_Service();
			$vote_data     = $likes_service->get_user_vote_data( $this->current_user );
		} else {
			$comments_service = new Comments_Service();
			$vote_data        = array(
				'total_likes'    => 0,
				'total_dislikes' => 0,
				'top_comments'   => $comments_service->get_user_comments_data( $this->current_user ),
			);
		}

		$total_likes    = $vote_data['total_likes'] ?? 0;
		$total_dislikes = $vote_data['total_dislikes'] ?? 0;
		$top_comments   = $vote_data['top_comments'] ?? array();

		$pagination_html = '';
		if ( $total_pages > 1 ) {
			$pagination_html = paginate_links(
				array(
					'base'    => add_query_arg( 'my_page', '%#%' ),
					'format'  => '',
					'current' => $page,
					'total'   => $total_pages,
					'type'    => 'list',
				)
			);
		}

		$user_id = $this->current_user;

		ob_start();
		require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/tab-my-comments.php';
		return ob_get_clean();
	}

	/**
	 * My Account Posts — AJAX endpoint for posts subtab content.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 * @throws Exception On error.
	 */
	public function my_account_posts( $request ) {
		$this->verify_nonce( $request );
		$this->current_user = get_current_user_id();

		if ( ! $this->current_user ) {
			throw new Exception( esc_html__( 'User not logged in', 'weal-profile' ) );
		}

		$page     = isset( $request['page'] ) ? max( 1, intval( $request['page'] ) ) : 1;
		$per_page = 10;

		$posts_query = new \WP_Query(
			array(
				'author'         => $this->current_user,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$user_posts  = $posts_query->posts;
		$total_pages = $posts_query->max_num_pages;

		$pagination_html = '';
		if ( $total_pages > 1 ) {
			$pagination_html = paginate_links(
				array(
					'base'    => add_query_arg( 'my_page', '%#%' ),
					'format'  => '',
					'current' => $page,
					'total'   => $total_pages,
					'type'    => 'list',
				)
			);
		}

		ob_start();
		require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/tab-my-posts.php';
		$html = ob_get_clean();

		return new WP_REST_Response(
			array(
				'html'        => $html,
				'page'        => $page,
				'total_pages' => $total_pages,
				'has_more'    => $page < $total_pages,
			)
		);
	}

	/**
	 * My Account Comments — AJAX endpoint for comments subtab content.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 * @throws Exception On error.
	 */
	public function my_account_comments( $request ) {
		$this->verify_nonce( $request );
		$this->current_user = get_current_user_id();

		if ( ! $this->current_user ) {
			throw new Exception( esc_html__( 'User not logged in', 'weal-profile' ) );
		}

		$page     = isset( $request['page'] ) ? max( 1, intval( $request['page'] ) ) : 1;
		$per_page = 10;

		$comment_query  = new \WP_Comment_Query();
		$total_comments = $comment_query->query(
			array(
				'user_id' => $this->current_user,
				'status'  => 'approve',
				'count'   => true,
			)
		);
		$total_pages    = (int) ceil( $total_comments / $per_page );

		$html = $this->get_comments_html( $page, $total_pages );

		return new WP_REST_Response(
			array(
				'html'        => $html,
				'page'        => $page,
				'total_pages' => $total_pages,
				'has_more'    => $page < $total_pages,
			)
		);
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

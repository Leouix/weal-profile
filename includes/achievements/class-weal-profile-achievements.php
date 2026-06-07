<?php
/**
 * Achievements module for Weal Profile plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Achievements;

use WealProfile\Includes\Manager\Settings_Manager;
use WealProfile\Includes\Weal_Profile_Module_Singleton_Interface;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Weal_Profile_Achievements
 *
 * Handles user achievements and badges based on activity.
 *
 * @since 1.4.0
 */
class Weal_Profile_Achievements implements Weal_Profile_Module_Singleton_Interface {

	/**
	 * The single instance of the class.
	 *
	 * @var Weal_Profile_Achievements|null
	 */
	private static $instance = null;

	/**
	 * Returns the main instance of the class.
	 *
	 * @return Weal_Profile_Achievements
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Private clone.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @throws \Exception If attempting to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Initialize hooks for the class.
	 */
	private function init_hooks() {
		add_filter( 'get_avatar', array( $this, 'filter_get_avatar' ), 15, 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'rest_api_init', array( $this, 'register_admin_route' ) );
	}

	/**
	 * Get approved comment count for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int Comment count.
	 */
	private function get_user_comment_count( $user_id ) {
		$count = get_comments(
			array(
				'count'   => true,
				'user_id' => $user_id,
				'status'  => 'approve',
			)
		);
		return (int) $count;
	}

	/**
	 * Check if user qualifies for commenter badge.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user has more than 2 approved comments.
	 */
	private function has_badge_commenter( $user_id ) {
		return $this->get_user_comment_count( $user_id ) > 2;
	}

	/**
	 * Wrap avatar HTML in badge container if user qualifies.
	 *
	 * @param string $avatar_html The avatar HTML.
	 * @param int    $user_id     User ID.
	 * @return string Wrapped or original avatar HTML.
	 */
	public static function wrap_avatar_with_badge( $avatar_html, $user_id ) {
		if ( ! $user_id ) {
			return $avatar_html;
		}

		$instance = self::instance();
		if ( $instance->has_badge_commenter( $user_id ) ) {
			return '<div class="has-badge">' . $avatar_html . '<span class="has-badge-commenter dashicons dashicons-awards" title="Great Commenter"></span></div>';
		}
		return $avatar_html;
	}

	/**
	 * Filter the avatar to add badge for comment context.
	 *
	 * @param string $avatar      Avatar HTML.
	 * @param mixed  $id_or_email User ID, email, or object.
	 * @param int    $size        Avatar size.
	 * @param string $default_url Default avatar URL.
	 * @param string $alt         Alt text.
	 * @return string Filtered avatar HTML.
	 */
	public function filter_get_avatar( $avatar, $id_or_email, $size, $default_url, $alt ) {
		$user_id = false;

		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
			$user_id = (int) $id_or_email->user_id;
		} elseif ( $id_or_email instanceof \WP_User ) {
			$user_id = $id_or_email->ID;
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		if ( ! $user_id ) {
			return $avatar;
		}

		if ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
			return self::wrap_avatar_with_badge( $avatar, $user_id );
		}

		return $avatar;
	}

	/**
	 * Get achievements data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of achievement items.
	 */
	public static function get_achievements_data( $user_id ) {
		$instance      = self::instance();
		$comment_count = $instance->get_user_comment_count( $user_id );

		return array(
			array(
				'id'     => 'commenter',
				'label'  => __( 'Active Commentator', 'weal-profile' ),
				'earned' => $comment_count > 2,
				'icon'   => '★',
			),
		);
	}

	/**
	 * Enqueue badge styles.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'weal-achievements-css',
			WEAL_PROFILE_PLUGIN_URL . 'public/css/achievements.css',
			array( 'dashicons' ),
			WEAL_PROFILE_VERSION
		);
	}

	/**
	 * Register admin REST route for saving achievements settings.
	 *
	 * @return void
	 */
	public function register_admin_route() {
		register_rest_route(
			'weal-profile/v1',
			'/admin-save-achievements-settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'admin_save_achievements_settings' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Handle saving achievements settings via REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function admin_save_achievements_settings( WP_REST_Request $request ) {
		$post_data = $request->get_params();

		$nonce = isset( $post_data['weal_profile_achievements_nonce'] )
			? sanitize_text_field( wp_unslash( $post_data['weal_profile_achievements_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, 'weal_profile_achievements_save' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Security check failed', 'weal-profile' ),
				),
				400
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Enqueue admin scripts for the Achievements settings page.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || 'weal-profile_page_weal-profile-achievements' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			'weal-profile-achievements-admin',
			WEAL_PROFILE_PLUGIN_URL . 'includes/achievements/js/weal-profile-achievements-admin.js',
			array(),
			WEAL_PROFILE_VERSION,
			true
		);

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_localize_script(
			'weal-profile-achievements-admin',
			'wealProfileAchievementsData',
			array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'root'  => esc_url_raw( rest_url() ),
				'page'  => $page,
			)
		);
	}
}

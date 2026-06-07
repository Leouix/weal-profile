<?php
/**
 * Achievements module for Weal Profile plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Achievements;

use WealProfile\Includes\Weal_Profile_Module_Singleton_Interface;

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
}

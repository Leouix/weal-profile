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
	 * Settings manager instance.
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

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
		$this->settings_manager = new Settings_Manager();
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
	 * Meta key for storing user-hidden achievements.
	 */
	const USER_ACHIEVEMENTS_HIDDEN_META = 'weal_profile_achievements_hidden';

	/**
	 * Initialize hooks for the class.
	 */
	private function init_hooks() {
		add_filter( 'get_avatar', array( $this, 'filter_get_avatar' ), 15, 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'rest_api_init', array( $this, 'register_admin_route' ) );
		add_action( 'rest_api_init', array( $this, 'register_user_toggle_route' ) );
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
	 * Get total likes received on a user's approved comments.
	 *
	 * @param int $user_id User ID.
	 * @return int Total likes.
	 */
	private function get_user_total_comment_likes( $user_id ) {
		global $wpdb;

		$total = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COALESCE(SUM(l.meta_value + 0), 0)
				FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} l
					ON c.comment_ID = l.comment_id AND l.meta_key = '_weal_likes_count'
				WHERE c.user_id = %d AND c.comment_approved = '1'",
				$user_id
			)
		);

		return (int) $total;
	}

	/**
	 * Get total dislikes received on a user's approved comments.
	 *
	 * @param int $user_id User ID.
	 * @return int Total dislikes.
	 */
	private function get_user_total_comment_dislikes( $user_id ) {
		global $wpdb;

		$total = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COALESCE(SUM(d.meta_value + 0), 0)
				FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} d
					ON c.comment_ID = d.comment_id AND d.meta_key = '_weal_dislikes_count'
				WHERE c.user_id = %d AND c.comment_approved = '1'",
				$user_id
			)
		);

		return (int) $total;
	}

	/**
	 * Get achievement description for a given type and target.
	 *
	 * @param string $achievement_id Achievement ID (commenter, cutie, angry).
	 * @param int    $target         Target threshold.
	 * @return string
	 */
	public static function get_achievement_description( $achievement_id, $target ) {
		$descriptions = array(
			'commenter' => sprintf(
				/* translators: %d: target comment count */
				__( 'Award for %d comments left', 'weal-profile' ),
				(int) $target
			),
			'cutie'     => sprintf(
				/* translators: %d: target comment likes count */
				__( 'Award for %d comment likes', 'weal-profile' ),
				(int) $target
			),
			'angry'     => sprintf(
				/* translators: %d: target comment dislikes count */
				__( 'Award for %d comment dislikes', 'weal-profile' ),
				(int) $target
			),
		);

		return isset( $descriptions[ $achievement_id ] ) ? $descriptions[ $achievement_id ] : '';
	}

	/**
	 * Get achievement definitions with default values.
	 *
	 * @return array
	 */
	public static function get_achievement_definitions() {
		return array(
			'commenter' => array(
				'label'  => __( 'Active Commentator', 'weal-profile' ),
				'target' => 3,
				'icon'   => 'dashicons-awards',
			),
			'cutie'     => array(
				'label'  => __( 'Cutie', 'weal-profile' ),
				'target' => 3,
				'icon'   => '&#x1f970;',
			),
			'angry'     => array(
				'label'  => __( 'Angry', 'weal-profile' ),
				'target' => 3,
				'icon'   => '&#x1f47f;',
			),
		);
	}

	/**
	 * Get achievements settings merged with definitions.
	 * Used by admin template and internal logic.
	 *
	 * @return array
	 */
	public static function get_admin_achievements_data() {
		$instance = self::instance();
		$saved    = $instance->settings_manager->get_achievements_settings();
		$defs     = self::get_achievement_definitions();

		$achievements = array();
		foreach ( $defs as $id => $default ) {
			$saved_item          = isset( $saved[ $id ] ) ? $saved[ $id ] : array();
			$achievements[ $id ] = wp_parse_args( $saved_item, $default );
		}
		return $achievements;
	}

	/**
	 * Check if user qualifies for commenter badge.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user meets the target.
	 */
	private function has_badge_commenter( $user_id ) {
		$achievements = self::get_admin_achievements_data();
		$settings     = isset( $achievements['commenter'] ) ? $achievements['commenter'] : array();

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		return $this->get_user_comment_count( $user_id ) >= (int) $settings['target'];
	}

	/**
	 * Check if user qualifies for cutie badge.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user meets the target.
	 */
	private function has_badge_cutie( $user_id ) {
		$achievements = self::get_admin_achievements_data();
		$settings     = isset( $achievements['cutie'] ) ? $achievements['cutie'] : array();

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		return $this->get_user_total_comment_likes( $user_id ) >= (int) $settings['target'];
	}

	/**
	 * Check if user qualifies for angry badge.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if user meets the target.
	 */
	private function has_badge_angry( $user_id ) {
		$achievements = self::get_admin_achievements_data();
		$settings     = isset( $achievements['angry'] ) ? $achievements['angry'] : array();

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		return $this->get_user_total_comment_dislikes( $user_id ) >= (int) $settings['target'];
	}

	/**
	 * Get hidden achievement IDs for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of hidden achievement IDs.
	 */
	public static function get_user_hidden_achievements( $user_id ) {
		$hidden = get_user_meta( $user_id, self::USER_ACHIEVEMENTS_HIDDEN_META, true );
		if ( ! is_array( $hidden ) ) {
			return array();
		}
		return $hidden;
	}

	/**
	 * Check if a user has hidden a specific achievement.
	 *
	 * @param int    $user_id        User ID.
	 * @param string $achievement_id Achievement ID.
	 * @return bool True if hidden.
	 */
	public static function is_achievement_hidden( $user_id, $achievement_id ) {
		$hidden = self::get_user_hidden_achievements( $user_id );
		return in_array( $achievement_id, $hidden, true );
	}

	/**
	 * Render an achievement icon — dashicon span or emoji span.
	 *
	 * @param string $icon     Icon value (dashicons-* class or emoji).
	 * @param string $css_class Additional classes for the span.
	 * @param string $title    Title attribute.
	 * @param string $description Description.
	 * @return string HTML for the icon.
	 */
	public static function render_achievement_icon( $icon, $css_class = '', $title = '', $description = '' ) {
		$title_attr = $title ? 'title="' . esc_attr( $title ) . '"' : '';
		$desc_attr  = $description ? 'data-description="' . esc_attr( $description ) . '"' : '';

		if ( str_starts_with( $icon, 'dashicons-' ) ) {
			return '<span class="dashicons ' . esc_attr( $icon ) . ' ' . esc_attr( $css_class ) . '" ' . $title_attr . ' ' . $desc_attr . '></span>';
		}

		return '<span class="' . esc_attr( $css_class ) . '" ' . $title_attr . ' ' . $desc_attr . '>' . wp_kses_post( $icon ) . '</span>';
	}

	/**
	 * Wrap avatar HTML in badge container if user qualifies and hasn't hidden it.
	 *
	 * @param string $avatar_html The avatar HTML.
	 * @param int    $user_id     User ID.
	 * @return string Wrapped or original avatar HTML.
	 */
	public static function wrap_avatar_with_badge( $avatar_html, $user_id ) {
		if ( ! $user_id ) {
			return $avatar_html;
		}

		$instance     = self::instance();
		$achievements = self::get_admin_achievements_data();
		$badges_html  = '';

		foreach ( $achievements as $id => $settings ) {
			if ( empty( $settings['enabled'] ) ) {
				continue;
			}

			if ( self::is_achievement_hidden( $user_id, $id ) ) {
				continue;
			}

			$qualifies = false;
			if ( 'commenter' === $id ) {
				$qualifies = $instance->has_badge_commenter( $user_id );
			} elseif ( 'cutie' === $id ) {
				$qualifies = $instance->has_badge_cutie( $user_id );
			} elseif ( 'angry' === $id ) {
				$qualifies = $instance->has_badge_angry( $user_id );
			}

			if ( ! $qualifies ) {
				continue;
			}

			$description = self::get_achievement_description( $id, $settings['target'] );
			$icon        = isset( $settings['icon'] ) ? $settings['icon'] : '';

			$badge_class  = 'has-badge-' . $id;
			$badges_html .= self::render_achievement_icon( $icon, $badge_class, $description );
		}

		if ( '' === $badges_html ) {
			return $avatar_html;
		}

		return '<div class="has-badge">' . $avatar_html . '<div class="weal-profile-badges-icons">' . $badges_html . '</div></div>';
	}

	/**
	 * Render achievements block for a user profile.
	 *
	 * @param int  $user_id     User ID.
	 * @param bool $show_toggle Whether to show toggle switches (own profile).
	 * @return string HTML output.
	 */
	public static function render_user_achievements( $user_id, $show_toggle = false ) {
		$achievements = self::get_achievements_data( $user_id );
		$hidden       = self::get_user_hidden_achievements( $user_id );

		if ( empty( $achievements ) ) {
			return '';
		}

		$html = '<div class="weal-profile-achievements-list">';

		foreach ( $achievements as $achievement ) {
			$is_hidden = in_array( $achievement['id'], $hidden, true );

			if ( ! $show_toggle && $is_hidden ) {
				continue;
			}

			$classes = array( 'weal-profile-achievement-item' );

			if ( $achievement['earned'] ) {
				$classes[] = 'earned';
			} else {
				$classes[] = 'not-earned';
			}

			if ( $is_hidden ) {
				$classes[] = 'user-hidden';
			}

			$html       .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
			$description = self::get_achievement_description( $achievement['id'], $achievement['target'] );
			$html       .= self::render_achievement_icon( $achievement['icon'], 'achievement-icon', '', $description );
			$html       .= '<span class="achievement-label">' . esc_html( $achievement['label'] ) . '</span>';

			if ( $show_toggle && $achievement['earned'] ) {
				$checked = $is_hidden ? '' : 'checked';
				$html   .= '<label class="achievement-switch">';
				$html   .= '<input type="checkbox" class="achievement-toggle-input" data-achievement-id="' . esc_attr( $achievement['id'] ) . '" ' . $checked . '>';
				$html   .= '<span class="achievement-slider round"></span>';
				$html   .= '</label>';
			} elseif ( $achievement['earned'] ) {
				$html .= '<span class="achievement-status earned">' . esc_html__( 'Earned', 'weal-profile' ) . '</span>';
			} else {
				$html .= '<span class="achievement-status locked">' . esc_html__( 'Not earned', 'weal-profile' ) . '</span>';
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
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
		$instance     = self::instance();
		$achievements = self::get_admin_achievements_data();
		$result       = array();

		foreach ( $achievements as $id => $settings ) {
			// Do not render achievements disabled in admin settings.
			if ( empty( $settings['enabled'] ) ) {
				continue;
			}

			if ( 'cutie' === $id ) {
				$count = $instance->get_user_total_comment_likes( $user_id );
			} elseif ( 'angry' === $id ) {
				$count = $instance->get_user_total_comment_dislikes( $user_id );
			} else {
				$count = $instance->get_user_comment_count( $user_id );
			}

			$earned = $count >= (int) $settings['target'];

			$result[] = array(
				'id'     => $id,
				'label'  => $settings['label'],
				'target' => $settings['target'],
				'earned' => $earned,
				'icon'   => $settings['icon'],
			);
		}

		return $result;
	}

	/**
	 * Get allowed HTML tags and attributes for achievements rendering.
	 *
	 * @return array
	 */
	public static function get_allowed_achievements_html() {
		return array(
			'div'   => array(
				'class' => array(),
				'id'    => array(),
			),
			'h3'    => array( 'class' => array() ),
			'span'  => array(
				'class'            => array(),
				'data-description' => array(),
			),
			'label' => array(
				'class' => array(),
				'for'   => array(),
			),
			'input' => array(
				'type'                => array(),
				'class'               => array(),
				'checked'             => array(),
				'data-achievement-id' => array(),
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

		$achievement_id = isset( $post_data['achievement_id'] )
			? sanitize_text_field( wp_unslash( $post_data['achievement_id'] ) )
			: '';

		$definitions = self::get_achievement_definitions();

		if ( ! isset( $definitions[ $achievement_id ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Invalid achievement ID', 'weal-profile' ),
				),
				400
			);
		}

		$defaults  = $definitions[ $achievement_id ];
		$submitted = isset( $post_data['achievements'][ $achievement_id ] ) ? $post_data['achievements'][ $achievement_id ] : array();

		$sanitized = array(
			'enabled' => ! empty( $submitted['enabled'] ),
			'target'  => isset( $submitted['target'] ) ? max( 1, (int) $submitted['target'] ) : $defaults['target'],
			'label'   => isset( $submitted['label'] ) ? sanitize_text_field( wp_unslash( $submitted['label'] ) ) : $defaults['label'],
		);

		$all_settings                    = $this->settings_manager->get_achievements_settings();
		$all_settings[ $achievement_id ] = $sanitized;

		$this->settings_manager->save_achievements_settings( $all_settings );

		return new WP_REST_Response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Register user REST route for toggling achievement visibility.
	 *
	 * @return void
	 */
	public function register_user_toggle_route() {
		register_rest_route(
			'weal-profile/v1',
			'/toggle-achievement-visibility/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_toggle_visibility' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	/**
	 * Handle toggling achievement visibility for a user.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_toggle_visibility( WP_REST_Request $request ) {
		$params = $request->get_params();

		$achievement_id = isset( $params['achievement_id'] ) ? sanitize_text_field( wp_unslash( $params['achievement_id'] ) ) : '';
		$hidden         = isset( $params['hidden'] ) ? rest_sanitize_boolean( $params['hidden'] ) : false;

		if ( empty( $achievement_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Invalid achievement ID', 'weal-profile' ),
				),
				400
			);
		}

		$user_id             = get_current_user_id();
		$hidden_achievements = self::get_user_hidden_achievements( $user_id );

		if ( $hidden ) {
			$hidden_achievements[] = $achievement_id;
			$hidden_achievements   = array_unique( $hidden_achievements );
		} else {
			$hidden_achievements = array_values( array_diff( $hidden_achievements, array( $achievement_id ) ) );
		}

		update_user_meta( $user_id, self::USER_ACHIEVEMENTS_HIDDEN_META, $hidden_achievements );

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

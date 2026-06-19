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
		add_action( 'rest_api_init', array( $this, 'register_duplicate_route' ) );
		add_action( 'rest_api_init', array( $this, 'register_delete_route' ) );
	}

	/**
	 * Get user metrics (comments, likes, dislikes) with request-level cache.
	 *
	 * @param int $user_id User ID.
	 * @return array{comments: int, likes: int, dislikes: int}
	 */
	private static function get_user_metrics( $user_id ) {
		static $cache = array();

		if ( isset( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}

		global $wpdb;

		$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT
					COUNT(DISTINCT c.comment_ID) AS comments,
					COALESCE(SUM(l.meta_value + 0), 0) AS likes,
					COALESCE(SUM(d.meta_value + 0), 0) AS dislikes
				FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} l
					ON c.comment_ID = l.comment_id AND l.meta_key = '_weal_likes_count'
				LEFT JOIN {$wpdb->commentmeta} d
					ON c.comment_ID = d.comment_id AND d.meta_key = '_weal_dislikes_count'
				WHERE c.user_id = %d AND c.comment_approved = '1'",
				$user_id
			)
		);

		$cache[ $user_id ] = array(
			'comments' => (int) ( $result->comments ?? 0 ),
			'likes'    => (int) ( $result->likes ?? 0 ),
			'dislikes' => (int) ( $result->dislikes ?? 0 ),
		);

		return $cache[ $user_id ];
	}

	/**
	 * Get achievement description for a given type and target.
	 *
	 * @param string $achievement_id Achievement ID (commenter, cutie, angry).
	 * @param int    $target         Target threshold.
	 * @param string $source         Optional source type for custom achievements.
	 * @return string
	 */
	public static function get_achievement_description( $achievement_id, $target, $source = '' ) {
		$lookup       = ! empty( $source ) ? $source : $achievement_id;
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

		return isset( $descriptions[ $lookup ] ) ? $descriptions[ $lookup ] : '';
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
	 * Check if achievement ID is a system (built-in) achievement.
	 *
	 * @param string $achievement_id Achievement ID.
	 * @return bool
	 */
	public static function is_system_achievement( $achievement_id ) {
		return isset( self::get_achievement_definitions()[ $achievement_id ] );
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
			if ( empty( $achievements[ $id ]['icon'] ) && isset( $default['icon'] ) ) {
				$achievements[ $id ]['icon'] = $default['icon'];
			}
		}

		foreach ( $saved as $id => $item ) {
			if ( isset( $achievements[ $id ] ) ) {
				continue;
			}
			$achievements[ $id ] = $item;
		}

		return $achievements;
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
	 * Render an achievement icon.
	 *
	 * @param string $icon       The icon value (dashicon class, emoji HTML, or attachment ID).
	 * @param string $css_class  Additional classes for the span.
	 * @param string $title      Title attribute.
	 * @param string $description Description.
	 * @return string HTML for the icon.
	 */
	public static function render_achievement_icon( $icon, $css_class = '', $title = '', $description = '' ) {
		$title_attr = $title ? 'title="' . esc_attr( $title ) . '"' : '';
		$desc_attr  = $description ? 'data-description="' . esc_attr( $description ) . '"' : '';

		if ( str_starts_with( $icon, 'dashicons-' ) ) {
			return '<span class="dashicons ' . esc_attr( $icon ) . ' ' . esc_attr( $css_class ) . '" ' . $title_attr . ' ' . $desc_attr . '></span>';
		}

		if ( is_numeric( $icon ) ) {
			$image_src = wp_get_attachment_image_src( (int) $icon, array( 30, 30 ) );
			if ( $image_src ) {
				$icon_class = esc_attr( $css_class ) . ' achievement-custom-icon';
				$alt_text   = $title ? esc_attr( $title ) : '';
				return '<img src="' . esc_url( $image_src[0] ) . '" class="' . $icon_class . '" alt="' . $alt_text . '" ' . $title_attr . '>';
			}
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

		$achievements = self::get_admin_achievements_data();
		$badges_html  = '';
		$metrics      = null;

		foreach ( $achievements as $id => $settings ) {
			if ( empty( $settings['enabled'] ) ) {
				continue;
			}

			if ( self::is_achievement_hidden( $user_id, $id ) ) {
				continue;
			}

			if ( null === $metrics ) {
				$metrics = self::get_user_metrics( $user_id );
			}

			$source_type = ! empty( $settings['source'] ) ? $settings['source'] : $id;

			if ( 'cutie' === $source_type ) {
				$count = $metrics['likes'];
			} elseif ( 'angry' === $source_type ) {
				$count = $metrics['dislikes'];
			} else {
				$count = $metrics['comments'];
			}

			if ( $count < (int) $settings['target'] ) {
				continue;
			}

			$icon = isset( $settings['icon'] ) ? $settings['icon'] : '';

			$badge_class  = 'has-badge-' . $id;
			$badges_html .= self::render_achievement_icon( $icon, $badge_class, $settings['label'] );
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
			$description = self::get_achievement_description( $achievement['id'], $achievement['target'], $achievement['source'] );
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
		if ( ! is_object( $id_or_email ) || empty( $id_or_email->user_id ) ) {
			return $avatar;
		}

		return self::wrap_avatar_with_badge( $avatar, (int) $id_or_email->user_id );
	}

	/**
	 * Get achievements data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of achievement items.
	 */
	public static function get_achievements_data( $user_id ) {
		$achievements = self::get_admin_achievements_data();
		$result       = array();
		$metrics      = null;

		foreach ( $achievements as $id => $settings ) {
			if ( empty( $settings['enabled'] ) ) {
				continue;
			}

			if ( null === $metrics ) {
				$metrics = self::get_user_metrics( $user_id );
			}

			$source_type = ! empty( $settings['source'] ) ? $settings['source'] : $id;

			if ( 'cutie' === $source_type ) {
				$count = $metrics['likes'];
			} elseif ( 'angry' === $source_type ) {
				$count = $metrics['dislikes'];
			} else {
				$count = $metrics['comments'];
			}

			$earned = $count >= (int) $settings['target'];

			$result[] = array(
				'id'     => $id,
				'label'  => $settings['label'],
				'target' => $settings['target'],
				'earned' => $earned,
				'icon'   => $settings['icon'],
				'source' => ! empty( $settings['source'] ) ? $settings['source'] : $id,
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
			'img'   => array(
				'src'      => array(),
				'alt'      => array(),
				'class'    => array(),
				'width'    => array(),
				'height'   => array(),
				'loading'  => array(),
				'decoding' => array(),
				'sizes'    => array(),
				'srcset'   => array(),
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

		$definitions  = self::get_achievement_definitions();
		$all_settings = $this->settings_manager->get_achievements_settings();

		if ( ! isset( $definitions[ $achievement_id ] ) && ! isset( $all_settings[ $achievement_id ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Invalid achievement ID', 'weal-profile' ),
				),
				400
			);
		}

		$defaults  = isset( $definitions[ $achievement_id ] ) ? $definitions[ $achievement_id ] : $all_settings[ $achievement_id ];
		$submitted = isset( $post_data['achievements'][ $achievement_id ] ) ? $post_data['achievements'][ $achievement_id ] : array();

		$sanitized = array(
			'enabled' => ! empty( $submitted['enabled'] ),
			'target'  => isset( $submitted['target'] ) ? max( 1, (int) $submitted['target'] ) : $defaults['target'],
			'label'   => isset( $submitted['label'] ) ? sanitize_text_field( wp_unslash( $submitted['label'] ) ) : $defaults['label'],
		);

		$icon_submitted = isset( $submitted['icon'] ) ? sanitize_text_field( wp_unslash( $submitted['icon'] ) ) : '';

		if ( ! empty( $submitted['remove_icon'] ) ) {
			$sanitized['icon'] = isset( $defaults['icon'] ) ? $defaults['icon'] : '';
		} elseif ( '' !== $icon_submitted ) {
			$sanitized['icon'] = $icon_submitted;
		} else {
			$sanitized['icon'] = isset( $all_settings[ $achievement_id ]['icon'] ) ? $all_settings[ $achievement_id ]['icon'] : ( $defaults['icon'] ?? '' );
		}

		if ( isset( $all_settings[ $achievement_id ] ) ) {
			$sanitized['source'] = $all_settings[ $achievement_id ]['source'] ?? '';
		}

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
	 * Register REST route for duplicating an achievement.
	 *
	 * @return void
	 */
	public function register_duplicate_route() {
		register_rest_route(
			'weal-profile/v1',
			'/duplicate-achievement/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_duplicate_achievement' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Handle duplicating a system achievement.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_duplicate_achievement( WP_REST_Request $request ) {
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

		$source_id = isset( $post_data['achievement_id'] )
			? sanitize_text_field( wp_unslash( $post_data['achievement_id'] ) )
			: '';

		$definitions = self::get_achievement_definitions();

		if ( ! isset( $definitions[ $source_id ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Only system achievements can be duplicated', 'weal-profile' ),
				),
				400
			);
		}

		$all_settings = $this->settings_manager->get_achievements_settings();
		$source_data  = isset( $all_settings[ $source_id ] )
			? wp_parse_args( $all_settings[ $source_id ], $definitions[ $source_id ] )
			: $definitions[ $source_id ];
		$new_id       = 'copy_' . $source_id . '_' . uniqid();

		$new_data = array(
			'label'   => $source_data['label'] . '-copy',
			'target'  => (int) $source_data['target'],
			'icon'    => $source_data['icon'] ?? '',
			'enabled' => ! empty( $source_data['enabled'] ),
			'source'  => $source_id,
		);

		$all_settings[ $new_id ] = $new_data;
		$this->settings_manager->save_achievements_settings( $all_settings );

		$html = self::render_admin_achievement_item( $new_id, $new_data );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'achievement_id' => $new_id,
				'html'           => $html,
			)
		);
	}

	/**
	 * Register REST route for deleting a custom achievement.
	 *
	 * @return void
	 */
	public function register_delete_route() {
		register_rest_route(
			'weal-profile/v1',
			'/delete-achievement/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_delete_achievement' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Handle deleting a custom achievement.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function handle_delete_achievement( WP_REST_Request $request ) {
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

		if ( self::is_system_achievement( $achievement_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'System achievements cannot be deleted', 'weal-profile' ),
				),
				400
			);
		}

		$all_settings = $this->settings_manager->get_achievements_settings();

		if ( ! isset( $all_settings[ $achievement_id ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Achievement not found', 'weal-profile' ),
				),
				404
			);
		}

		$icon = isset( $all_settings[ $achievement_id ]['icon'] ) ? $all_settings[ $achievement_id ]['icon'] : '';
		if ( is_numeric( $icon ) ) {
			wp_delete_attachment( (int) $icon, true );
		}

		unset( $all_settings[ $achievement_id ] );
		$this->settings_manager->save_achievements_settings( $all_settings );

		return new WP_REST_Response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Render a single achievement admin item HTML.
	 *
	 * @param string $id       Achievement ID.
	 * @param array  $settings Achievement settings.
	 * @return string HTML.
	 */
	public static function render_admin_achievement_item( $id, $settings ) {
		$source      = ! empty( $settings['source'] ) ? $settings['source'] : $id;
		$description = self::get_achievement_description( $id, $settings['target'], $source );
		ob_start();
		?>
		<div class="achievement-wrapper">
			<?php if ( self::is_system_achievement( $id ) ) : ?>
				<div class="achievement-duplicate" title="<?php esc_attr_e( 'Duplicate achievement', 'weal-profile' ); ?>"></div>
			<?php else : ?>
				<div class="achievement-delete" title="<?php esc_attr_e( 'Delete achievement', 'weal-profile' ); ?>">
					<img src="<?php echo esc_url( WEAL_PROFILE_PLUGIN_URL . 'admin/icons/delete.png' ); ?>" alt="<?php esc_attr_e( 'Delete', 'weal-profile' ); ?>">
				</div>
			<?php endif; ?>

			<form class="achievement-form">
				<?php wp_nonce_field( 'weal_profile_achievements_save', 'weal_profile_achievements_nonce' ); ?>
				<input type="hidden" name="achievement_id" value="<?php echo esc_attr( $id ); ?>">

				<div class="achievement-block">
					<h3><?php echo self::render_achievement_icon( $settings['icon'], 'admin-achievement-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $settings['label'] ); ?></h3>

					<div class="label-area">
						<input type="hidden" name="achievements[<?php echo esc_attr( $id ); ?>][enabled]" value="0">
						<label for="achievement-<?php echo esc_attr( $id ); ?>-enabled">
							<?php esc_html_e( 'Enable achievement', 'weal-profile' ); ?>
						</label>
						<input type="checkbox"
								id="achievement-<?php echo esc_attr( $id ); ?>-enabled"
								name="achievements[<?php echo esc_attr( $id ); ?>][enabled]"
								value="1"
								<?php checked( ! empty( $settings['enabled'] ) ); ?>>
					</div>

					<div class="label-area">
						<label for="achievement-<?php echo esc_attr( $id ); ?>-target">
							<?php esc_html_e( 'Target comments count:', 'weal-profile' ); ?>
						</label>
						<input type="number"
							id="achievement-<?php echo esc_attr( $id ); ?>-target"
							name="achievements[<?php echo esc_attr( $id ); ?>][target]"
							value="<?php echo esc_attr( $settings['target'] ); ?>"
							min="1">
						<p class="description">
							<?php echo esc_html( $description ); ?>
						</p>
					</div>

					<div class="label-area">
						<label for="achievement-<?php echo esc_attr( $id ); ?>-label">
							<?php esc_html_e( 'Label:', 'weal-profile' ); ?>
						</label>
						<input type="text"
							id="achievement-<?php echo esc_attr( $id ); ?>-label"
							name="achievements[<?php echo esc_attr( $id ); ?>][label]"
							value="<?php echo esc_attr( $settings['label'] ); ?>">
					</div>

					<div class="label-area">
						<label><?php esc_html_e( 'Custom Icon:', 'weal-profile' ); ?></label>
						<div class="achievement-icon-preview">
							<?php echo self::render_achievement_icon( $settings['icon'] ?? '', 'admin-achievement-icon-preview' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<input type="hidden"
							name="achievements[<?php echo esc_attr( $id ); ?>][icon]"
							value="<?php echo esc_attr( $settings['icon'] ?? '' ); ?>"
							class="achievement-icon-input">
						<input type="hidden"
							name="achievements[<?php echo esc_attr( $id ); ?>][remove_icon]"
							value="0"
							class="achievement-remove-icon-flag">
						<button type="button" class="button upload-achievement-icon-button">
							<?php esc_html_e( 'Choose Icon', 'weal-profile' ); ?>
						</button>
						<button type="button" class="button remove-achievement-icon-button">
							<?php esc_html_e( 'Remove Icon', 'weal-profile' ); ?>
						</button>
					</div>

					<div class="button-area">
						<input type="submit" class="save-achievement-button" value="<?php esc_attr_e( 'Save', 'weal-profile' ); ?>">
						<span class="achievement-success-notice"><?php esc_html_e( 'Success!', 'weal-profile' ); ?></span>
						<span class="achievement-error-notice"></span>
					</div>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
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

		wp_enqueue_media();

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
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'root'             => esc_url_raw( rest_url() ),
				'page'             => $page,
				'confirmDelete'    => esc_html__( 'Вы уверены что хотите удалить ачивку?', 'weal-profile' ),
				'chooseIconTitle'  => esc_html__( 'Choose Achievement Icon', 'weal-profile' ),
				'selectText'       => esc_html__( 'Select', 'weal-profile' ),
			)
		);
	}
}

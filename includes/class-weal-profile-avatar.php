<?php
/**
 * Avatar handling for Weal Profile plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WealProfile\Includes\Manager\Settings_Manager;

/**
 * Class Weal_Profile_Avatar
 *
 * Handles avatar upload, retrieval, and deletion.
 *
 * @since 1.0.0
 */
class Weal_Profile_Avatar {

	/**
	 * Check whether current get_avatar call is for a comment context.
	 *
	 * @param mixed $id_or_email Value passed to get_avatar().
	 * @return bool
	 */
	private static function is_comment_avatar_context( $id_or_email ): bool {
		// 1. Проверяем переданный объект (сработает и для WP_Comment, и для stdClass)
		$is_passed_comment = is_object( $id_or_email ) && ! empty( $id_or_email->comment_ID );

		// 2. Проверяем глобальный контекст WP
		$is_global_comment = isset( $GLOBALS['comment'] ) && $GLOBALS['comment'] instanceof WP_Comment;

		return $is_passed_comment || $is_global_comment;
	}

	/**
	 * Get the avatar attachment ID for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int Attachment ID or 0 if not set.
	 */
	public static function get_avatar_id( $user_id ) {
		$avatar_id = get_user_meta( $user_id, 'weal_profile_avatar_id', true );
		return absint( $avatar_id );
	}

	/**
	 * Get the avatar HTML for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string HTML image tag or empty string.
	 */
	public static function get_avatar_html( $user_id ) {
		$avatar_id = self::get_avatar_id( $user_id );

		if ( $avatar_id > 0 ) {
			return wp_get_attachment_image(
				$avatar_id,
				'thumbnail',
				false,
				array(
					'class' => 'weal-profile-avatar-img',
					'alt'   => __( 'Profile picture', 'weal-profile' ),
				)
			);
		}

		return get_avatar( $user_id, 96 );
	}

	/**
	 * Handle avatar upload.
	 *
	 * @return int|WP_Error New attachment ID on success, WP_Error on failure.
	 */
	public static function handle_upload() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in.', 'weal-profile' ) );
		}

		$user_id = get_current_user_id();

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'You do not have permission to upload avatars.', 'weal-profile' ) );
		}

		if ( ! isset( $_FILES['weal_profile_avatar'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return new WP_Error( 'no_file', __( 'No file uploaded.', 'weal-profile' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file = isset( $_FILES['weal_profile_avatar'] ) ? $_FILES['weal_profile_avatar'] : null;

		if ( ! is_array( $file ) || ! isset( $file['error'] ) || 0 !== (int) $file['error'] ) {
			return new WP_Error( 'upload_error', __( 'File upload error.', 'weal-profile' ) );
		}

		if ( ! isset( $file['size'] ) || (int) $file['size'] > 2 * 1024 * 1024 ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds 2MB limit.', 'weal-profile' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'weal_profile_avatar', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$old_avatar_id = self::get_avatar_id( $user_id );

		update_user_meta( $user_id, 'weal_profile_avatar_id', absint( $attachment_id ) );

		if ( $old_avatar_id > 0 ) {
			wp_delete_attachment( $old_avatar_id, true );
		}

		return $attachment_id;
	}

	/**
	 * Remove avatar for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success, false on failure.
	 */
	public static function remove_avatar( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$avatar_id = self::get_avatar_id( $user_id );

		if ( $avatar_id > 0 ) {
			wp_delete_attachment( $avatar_id, true );
		}

		delete_user_meta( $user_id, 'weal_profile_avatar_id' );

		return true;
	}

	/**
	 * Clean up avatar when user is deleted.
	 *
	 * @param int $user_id User ID.
	 */
	public static function cleanup_on_user_delete( $user_id ) {
		self::remove_avatar( $user_id );
	}

	/**
	 * Check if avatar field is allowed in plugin settings.
	 *
	 * @return bool True if avatar is in fields_allowed, false otherwise.
	 */
	public static function get_is_avatar_field_allowed() {
		$settings_manager = new Settings_Manager();
		$settings         = $settings_manager->get_settings();
		$fields_allowed   = $settings['fields_allowed'] ?? array();
		return in_array( 'avatar', $fields_allowed, true );
	}

	/**
	 * Подменяет ссылку автора комментария на ссылку его профиля.
	 *
	 * @param string     $url      URL комментария.
	 * @param int        $id       ID комментария.
	 * @param WP_Comment $comment  Объект комментария.
	 * @return string
	 */
	public static function filter_comment_author_url( $url, $id, $comment ) {

		if ( ! $comment instanceof WP_Comment || empty( $comment->user_id ) ) {
			return $url;
		}

		$user_id = (int) $comment->user_id;

		$settings     = new Settings_Manager();
		$profile_slug = $settings->get_user_page_url();

		if ( empty( $profile_slug ) ) {
			return $url;
		}

		$base_url = home_url( '/' . ltrim( $profile_slug, '/' ) );

		if ( is_user_logged_in() && get_current_user_id() === $user_id ) {
			$profile_url = $base_url;
		} else {
			$profile_url = add_query_arg( 'u', Weal_Profile::encode_user_token( $user_id ), $base_url );
		}

		return esc_url( $profile_url );
	}

	/**
	 * Filter the standard WordPress avatar to use our custom one.
	 *
	 * @param string $avatar      HTML for the user's avatar.
	 * @param mixed  $id_or_email The Gravatar to retrieve. Accepts a user ID, Gravatar MD5 hash, user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param int    $size        Square avatar width and height in pixels to retrieve.
	 * @param string $default_url     URL to a default_url image to use if no avatar is available.
	 * @param string $alt         Alternative text to use in the avatar image tag.
	 * @return string Filtered avatar HTML.
	 */
	public static function filter_get_avatar( $avatar, $id_or_email, $size, $default_url, $alt ) {
		$user_id = false;

		// WordPress может передать сюда разные типы данных, нам нужно извлечь ID пользователя.
		if ( is_numeric( $id_or_email ) ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
			// Например, это объект комментария.
			$user_id = (int) $id_or_email->user_id;
		} elseif ( $id_or_email instanceof WP_User ) {
			$user_id = $id_or_email->ID;
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		$avatar_image = $avatar;

		// Если нашли пользователя, проверяем нашу мету.
		if ( $user_id ) {
			$custom_avatar_id = self::get_avatar_id( $user_id );

			if ( $custom_avatar_id > 0 ) {
				// Формируем HTML стандартными средствами WP, подставляя запрошенный размер.
				$custom_avatar = wp_get_attachment_image(
					$custom_avatar_id,
					array( $size, $size ),
					false,
					array(
						'class' => 'avatar avatar-' . $size . ' photo',
						'alt'   => $alt ? $alt : __( 'Profile picture', 'weal-profile' ),
					)
				);

				if ( $custom_avatar ) {
					$avatar_image = $custom_avatar;
				}
			}
		}

		if ( ! $user_id ) {
			return $avatar_image;
		}

		// Wrap avatar into profile link only in comments context.
		if ( ! self::is_comment_avatar_context( $id_or_email, $user_id ) ) {
			return $avatar_image;
		}

		$settings     = new Settings_Manager();
		$profile_slug = $settings->get_user_page_url();

		if ( empty( $profile_slug ) ) {
			return $avatar_image;
		}

		$profile_url = is_user_logged_in() && get_current_user_id() === (int) $user_id
			? home_url( '/' . ltrim( $profile_slug, '/' ) )
			: add_query_arg( 'u', Weal_Profile::encode_user_token( $user_id ), home_url( '/' . ltrim( $profile_slug, '/' ) ) );

		return '<a href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener">' . $avatar_image . '</a>';
	}
}

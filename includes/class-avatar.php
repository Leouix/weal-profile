<?php
/**
 * Avatar handling for Weal Profile plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Weal_Profile_Avatar
 *
 * Handles avatar upload, retrieval, and deletion.
 *
 * @since 1.0.0
 */
class Weal_Profile_Avatar {

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

		if ( ! isset( $_FILES['profile_avatar'] ) ) {
			return new WP_Error( 'no_file', __( 'No file uploaded.', 'weal-profile' ) );
		}

		$file = $_FILES['profile_avatar'];

		if ( ! isset( $file['error'] ) || 0 !== $file['error'] ) {
			return new WP_Error( 'upload_error', __( 'File upload error.', 'weal-profile' ) );
		}

		if ( ! isset( $file['size'] ) || $file['size'] > 2 * 1024 * 1024 ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds 2MB limit.', 'weal-profile' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'profile_avatar', 0 );

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
}

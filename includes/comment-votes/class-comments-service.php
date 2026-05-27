<?php
/**
 * Likes Vote Service
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Likes_Vote_Service
 */
class Comments_Service {

	/**
	 * Get all vote data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|null Null if user not found.
	 */
	public function get_user_comments_data( $user_id ) {
		global $wpdb;

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return null;
		}

		$comments_table = $wpdb->prefix . 'comments';
		$user_email     = $user->user_email;


		$commentmeta_table = $wpdb->prefix . 'commentmeta';

		$top_comments = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT c.comment_ID, c.comment_content, c.comment_post_ID,
					COALESCE(m.meta_value, 0) as likes_count
				FROM %i c
				LEFT JOIN %i m ON c.comment_ID = m.comment_id AND m.meta_key = '_weal_likes_count'
				WHERE c.comment_author_email = %s AND c.comment_approved = 1
				ORDER BY COALESCE(m.meta_value, 0) DESC
				LIMIT 3",
				$comments_table,
				$commentmeta_table,
				$user_email
			)
		);

		return $top_comments;
	}
}

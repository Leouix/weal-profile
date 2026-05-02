<?php
/**
 * Likes Vote Service
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LikesVoteService
 */
class LikesVoteService {

	/**
	 * Get all vote data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array{
	 *     user: \WP_User|false,
	 *     total_likes: int,
	 *     total_dislikes: int,
	 *     top_comments: array
	 * }|null Null if user not found.
	 */
	public function get_user_vote_data( $user_id ) {
		global $wpdb;

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return null;
		}

		$table_name     = $wpdb->prefix . Comment_Votes::TABLE_NAME;
		$comments_table = $wpdb->prefix . 'comments';
		$user_email     = $user->user_email;

		$total_likes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i v
                 INNER JOIN %i c ON v.comment_id = c.comment_ID
                 WHERE c.comment_author_email = %s AND v.is_liked = 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comments_table,
				$user_email
			)
		);

		$total_dislikes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i v
                 INNER JOIN %i c ON v.comment_id = c.comment_ID
                 WHERE c.comment_author_email = %s AND v.is_liked = 0', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comments_table,
				$user_email
			)
		);

		$commentmeta_table = $wpdb->prefix . 'commentmeta';

		$top_comments = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT c.comment_ID, c.comment_content, c.comment_post_ID,
                    COALESCE(m.meta_value, 0) as likes_count
                FROM %i c
                LEFT JOIN %i m ON c.comment_ID = m.comment_id AND m.meta_key = '_weal_likes_count'
                WHERE c.comment_author_email = %s AND c.comment_approved = 1
                ORDER BY COALESCE(m.meta_value, 0) DESC
                LIMIT 3", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$comments_table,
				$commentmeta_table,
				$user_email
			)
		);

		return array(
			'user'           => $user,
			'total_likes'    => $total_likes,
			'total_dislikes' => $total_dislikes,
			'top_comments'   => $top_comments,
		);
	}
}

<?php
/**
 * Likes Vote Service
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

use WealProfile\Includes\Manager\Settings_Manager;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Likes_Vote_Service
 */
class Likes_Vote_Service {

	/**
	 * Get all vote data for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array|null Null if user not found.
	 */
	public function get_user_vote_data( $user_id ) {
		global $wpdb;

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return null;
		}

		$table_name     = $wpdb->prefix . Comment_Votes::TABLE_NAME;
		$comments_table = $wpdb->prefix . 'comments';

		$total_likes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i v
				INNER JOIN %i c ON v.comment_id = c.comment_ID
				WHERE c.user_id = %d AND v.is_liked = 1',
				$table_name,
				$comments_table,
				$user_id
			)
		);

		$total_dislikes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i v
				INNER JOIN %i c ON v.comment_id = c.comment_ID
				WHERE c.user_id = %d AND v.is_liked = 0',
				$table_name,
				$comments_table,
				$user_id
			)
		);

		return array(
			'user'           => $user,
			'total_likes'    => $total_likes,
			'total_dislikes' => $total_dislikes,
		);
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
				'SELECT id, is_liked FROM %i WHERE comment_id = %d AND user_id = %d LIMIT 1',
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

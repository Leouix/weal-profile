<?php
/**
 * REST API for Comment Votes
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_Votes
 */
class REST_Votes {

	const NAMESPACE = 'weal-profile/v1';
	const ROUTE     = '/comment-vote';

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_vote' ),
				'permission_callback' => array( $this, 'check_permission' ),
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
	 * Check if user has permission to vote.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		return is_user_logged_in()
			? true
			: new WP_Error(
				'rest_not_logged_in',
				esc_html__( 'Login required to vote', 'weal-profile' ),
				array( 'status' => 401 )
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
				'SELECT id, is_liked FROM %i WHERE comment_id = %d AND user_id = %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
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

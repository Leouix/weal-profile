<?php
/**
 * Comment Votes - Database and Frontend Display
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Votes
 */
class Comment_Votes {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'weal_comment_votes';

	/**
	 * The single instance of the class.
	 *
	 * @var Comment_Votes|null
	 */
	private static $instance = null;

	/**
	 * Returns the main instance of the class.
	 *
	 * @return Comment_Votes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent creating a new instance via 'new'.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Private clone method to prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Private wakeup method to prevent unserializing of the instance.
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'comment_text', array( $this, 'append_vote_buttons' ), 10, 2 );
	}

	/**
	 * Create the custom table for comment votes.
	 */
	public static function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . self::TABLE_NAME;

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            comment_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            is_liked TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX comment_id_index (comment_id),
            INDEX user_id_index (user_id),
            UNIQUE KEY uniq_comment_user (comment_id, user_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the custom table on uninstall.
	 */
	public static function drop_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets() {
		if ( ! is_singular() && ! is_home() && ! is_archive() ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'weal-comment-votes-css',
			plugin_dir_url( dirname( __DIR__ ) ) . 'public/css/comment-votes.css',
			array( 'dashicons' ),
			WEAL_PROFILE_VERSION
		);

		wp_enqueue_script(
			'weal-comment-votes-js',
			plugin_dir_url( dirname( __DIR__ ) ) . 'public/js/comment-votes.js',
			array(),
			WEAL_PROFILE_VERSION,
			true
		);

		wp_localize_script(
			'weal-comment-votes-js',
			'wealCommentVotesData',
			array(
				'root'       => esc_url_raw( rest_url() ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'isLoggedIn' => is_user_logged_in(),
			)
		);
	}

	/**
	 * Append vote buttons to comment text.
	 *
	 * @param string          $comment_text Comment text.
	 * @param WP_Comment|null $comment      Comment object.
	 * @return string
	 */
	public function append_vote_buttons( $comment_text, $comment = null ) {
		if ( ! $comment ) {
			$comment = get_comment();
		}

		if ( ! $comment || empty( $comment->comment_ID ) ) {
			return $comment_text;
		}

		$comment_id = $comment->comment_ID;
		$likes      = (int) get_comment_meta( $comment_id, '_weal_likes_count', true );
		$dislikes   = (int) get_comment_meta( $comment_id, '_weal_dislikes_count', true );

		$user_vote = null;
		if ( is_user_logged_in() ) {
			$user_vote = $this->get_user_vote( $comment_id, get_current_user_id() );
		}

		$like_active_class    = ( 1 === $user_vote ) ? 'weal-vote-active' : '';
		$dislike_active_class = ( 0 === $user_vote ) ? 'weal-vote-active' : '';

		$buttons_html  = '<div class="weal-vote-container" data-comment-id="' . esc_attr( $comment_id ) . '">';
		$buttons_html .= '<button class="weal-vote-btn weal-vote-like ' . esc_attr( $like_active_class ) . '" data-comment-id="' . esc_attr( $comment_id ) . '" data-action="like" aria-label="' . esc_attr__( 'Like this comment', 'weal-profile' ) . '">';
		$buttons_html .= '<span class="dashicons dashicons-thumbs-up"></span>';
		$buttons_html .= '<span class="weal-vote-count weal-like-count">' . esc_html( $likes ) . '</span>';
		$buttons_html .= '</button>';

		$buttons_html .= '<button class="weal-vote-btn weal-vote-dislike ' . esc_attr( $dislike_active_class ) . '" data-comment-id="' . esc_attr( $comment_id ) . '" data-action="dislike" aria-label="' . esc_attr__( 'Dislike this comment', 'weal-profile' ) . '">';
		$buttons_html .= '<span class="dashicons dashicons-thumbs-down"></span>';
		$buttons_html .= '<span class="weal-vote-count weal-dislike-count">' . esc_html( $dislikes ) . '</span>';
		$buttons_html .= '</button>';

		if ( ! is_user_logged_in() ) {
			$buttons_html .= '<span class="weal-vote-login-hint">' . esc_html__( 'Login to vote', 'weal-profile' ) . '</span>';
		}

		$buttons_html .= '</div>';

		return $comment_text . $buttons_html;
	}

	/**
	 * Get user's vote for a comment.
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $user_id    User ID.
	 * @return int|null Returns 1 for like, 0 for dislike, null for no vote.
	 */
	public function get_user_vote( $comment_id, $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$result = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT is_liked FROM %i WHERE comment_id = %d AND user_id = %d LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comment_id,
				$user_id
			)
		);

		if ( null === $result ) {
			return null;
		}

		return (int) $result;
	}

	/**
	 * Update vote counts in comment meta.
	 *
	 * @param int $comment_id Comment ID.
	 * @return array Array with likes and dislikes count.
	 */
	public static function update_vote_counts( $comment_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$likes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE comment_id = %d AND is_liked = 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comment_id
			)
		);

		$dislikes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE comment_id = %d AND is_liked = 0', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comment_id
			)
		);

		update_comment_meta( $comment_id, '_weal_likes_count', $likes );
		update_comment_meta( $comment_id, '_weal_dislikes_count', $dislikes );

		return array(
			'likes'    => $likes,
			'dislikes' => $dislikes,
		);
	}
}

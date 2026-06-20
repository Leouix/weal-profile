<?php
/**
 * Comment Votes - Database and Frontend Display
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

use WealProfile\Includes\Manager\Settings_Manager;
use WealProfile\Includes\Weal_Profile_Module_Singleton_Interface;
use WP_Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Votes
 */
class Comment_Votes implements Weal_Profile_Module_Singleton_Interface {

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
	 * Whether comment votes are enabled (lazily loaded once per request).
	 *
	 * @var bool|null
	 */
	private $comment_votes_enabled = null;

	/**
	 * Batch-loaded user votes cache: "{$user_id}_{$comment_id}" => is_liked|null.
	 *
	 * @var array
	 */
	private static $user_votes_batch_cache = array();

	/**
	 * Comment IDs already preloaded via update_meta_cache().
	 *
	 * @var int[]
	 */
	private static $preloaded_comment_ids = array();

	/**
	 * Returns the main instance of the class.
	 *
	 * @return Comment_Votes
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'comment_text', array( $this, 'append_vote_buttons' ), 10, 2 );
		add_filter( 'the_comments', array( $this, 'preload_comment_data' ) );
	}

	/**
	 * Create the custom table for comment votes.
	 */
	public static function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . self::TABLE_NAME;

		$sql = "CREATE TABLE $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			comment_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			is_liked TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			INDEX comment_id_index (comment_id),
			INDEX user_id_index (user_id),
			INDEX ix_comment_liked (comment_id, is_liked),
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
	 * Get whether comment votes are enabled, with request-level caching.
	 *
	 * @return bool
	 */
	private function is_comment_votes_enabled() {
		if ( null === $this->comment_votes_enabled ) {
			$settings                    = ( new Settings_Manager() )->get_settings();
			$this->comment_votes_enabled = ! empty( $settings['comment_votes_enabled'] );
		}
		return $this->comment_votes_enabled;
	}

	/**
	 * Preload comment meta and batch user votes before the comment loop renders.
	 *
	 * Hooked to 'the_comments' to intercept every WP_Comment_Query result
	 * before individual comment_text filters fire.
	 *
	 * @param WP_Comment[] $comments Array of comment objects.
	 * @return WP_Comment[]
	 */
	public function preload_comment_data( $comments ) {
		if ( empty( $comments ) ) {
			return $comments;
		}

		if ( ! $this->is_comment_votes_enabled() ) {
			return $comments;
		}

		$comment_ids = wp_list_pluck( $comments, 'comment_ID' );
		$new_ids     = array_diff( $comment_ids, self::$preloaded_comment_ids );

		if ( ! empty( $new_ids ) ) {
			// Pre-warm meta cache so get_comment_meta() hits memory, not DB.
			update_meta_cache( 'comment', $new_ids );
			self::$preloaded_comment_ids = array_merge( self::$preloaded_comment_ids, $new_ids );
		}

		if ( is_user_logged_in() ) {
			self::batch_load_user_votes( get_current_user_id(), $comment_ids );
		}

		return $comments;
	}

	/**
	 * Batch-load user votes for a list of comment IDs in a single query.
	 *
	 * @param int   $user_id     User ID.
	 * @param int[] $comment_ids Comment IDs.
	 */
	private static function batch_load_user_votes( $user_id, $comment_ids ) {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Filter out comment IDs already cached for this user.
		$uncached = array();
		foreach ( $comment_ids as $cid ) {
			$key = $user_id . '_' . $cid;
			if ( ! array_key_exists( $key, self::$user_votes_batch_cache ) ) {
				$uncached[] = $cid;
			}
		}

		if ( empty( $uncached ) ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $uncached ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_id, is_liked FROM %i WHERE user_id = %d AND comment_id IN ($placeholders)",
				array_merge( array( $table_name, $user_id ), $uncached )
			)
		);
		// phpcs:enable

		// Initialize all as null.
		foreach ( $uncached as $cid ) {
			self::$user_votes_batch_cache[ $user_id . '_' . $cid ] = null;
		}

		// Fill in found votes.
		foreach ( $results as $row ) {
			self::$user_votes_batch_cache[ $user_id . '_' . (int) $row->comment_id ] = (int) $row->is_liked;
		}
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets() {
		if ( ! is_singular() && ! is_home() && ! is_archive() ) {
			return;
		}

		if ( ! $this->is_comment_votes_enabled() ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'weal-comment-votes-css',
			WEAL_PROFILE_PLUGIN_URL . 'public/css/comment-votes.css',
			array( 'dashicons' ),
			WEAL_PROFILE_VERSION
		);

		wp_enqueue_script(
			'weal-comment-votes-js',
			WEAL_PROFILE_PLUGIN_URL . 'public/js/comment-votes.js',
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
		if ( ! $this->is_comment_votes_enabled() ) {
			return $comment_text;
		}

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
		// Check batch cache first (populated by preload_comment_data).
		$cache_key = $user_id . '_' . $comment_id;
		if ( array_key_exists( $cache_key, self::$user_votes_batch_cache ) ) {
			return self::$user_votes_batch_cache[ $cache_key ];
		}

		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$result = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT is_liked FROM %i WHERE comment_id = %d AND user_id = %d LIMIT 1',
				$table_name,
				$comment_id,
				$user_id
			)
		);

		$vote = ( null === $result ) ? null : (int) $result;

		// Cache for subsequent calls within the same request.
		self::$user_votes_batch_cache[ $cache_key ] = $vote;

		return $vote;
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

		// Single query aggregates both counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(is_liked = 1), 0) AS likes, COALESCE(SUM(is_liked = 0), 0) AS dislikes FROM %i WHERE comment_id = %d',
				$table_name,
				$comment_id
			)
		);

		$likes    = (int) $row->likes;
		$dislikes = (int) $row->dislikes;

		update_comment_meta( $comment_id, '_weal_likes_count', $likes );
		update_comment_meta( $comment_id, '_weal_dislikes_count', $dislikes );

		return array(
			'likes'    => $likes,
			'dislikes' => $dislikes,
		);
	}

	/**
	 * Get vote data for a user's comments.
	 *
	 * @param WP_Comment[] $comments Paginated comment objects to enrich.
	 * @param int          $user_id  User ID.
	 * @return array{comments: WP_Comment[], total_likes: int, total_dislikes: int}
	 */
	public static function get_vote_data_for_user( $comments, $user_id ) {
		global $wpdb;

		$cache_key = 'weal_profile_user_vote_totals_' . $user_id;
		$totals    = wp_cache_get( $cache_key, 'weal_profile' );

		if ( false === $totals ) {
			$totals = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT 
                COALESCE(SUM(l.meta_value + 0), 0) AS total_likes,
                COALESCE(SUM(d.meta_value + 0), 0) AS total_dislikes
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->commentmeta} l 
                ON c.comment_ID = l.comment_id AND l.meta_key = '_weal_likes_count'
            LEFT JOIN {$wpdb->commentmeta} d 
                ON c.comment_ID = d.comment_id AND d.meta_key = '_weal_dislikes_count'
            WHERE c.user_id = %d AND c.comment_approved = '1'",
					$user_id
				)
			);

			wp_cache_set( $cache_key, $totals, 'weal_profile', 300 );
		}

		// 2. Предварительно загружаем метаданные для текущих комментов одним запросом (решает проблему N+1).
		if ( ! empty( $comments ) ) {
			$comment_ids = wp_list_pluck( $comments, 'comment_ID' );
			update_meta_cache( 'comment', $comment_ids );
		}

		// Теперь get_comment_meta берет данные из кэша в оперативной памяти (без запросов к БД).
		foreach ( $comments as $comment ) {
			$likes    = (int) get_comment_meta( $comment->comment_ID, '_weal_likes_count', true );
			$dislikes = (int) get_comment_meta( $comment->comment_ID, '_weal_dislikes_count', true );

			$comment->likes_count    = $likes;
			$comment->dislikes_count = $dislikes;
			$comment->has_likes      = $likes > 0;
			$comment->has_dislikes   = $dislikes > 0;
		}

		return array(
			'comments'       => $comments,
			'total_likes'    => (int) $totals->total_likes,
			'total_dislikes' => (int) $totals->total_dislikes,
		);
	}
}

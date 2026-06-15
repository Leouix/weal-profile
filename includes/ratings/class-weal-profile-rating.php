<?php
/**
 * The rating functionality for the Weal Profile plugin.
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Ratings;

use WealProfile\Includes\Weal_Profile_Module_Singleton_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Weal_Profile_Rating
 */
class Weal_Profile_Rating implements Weal_Profile_Module_Singleton_Interface {

	/**
	 * The single instance of the class.
	 *
	 * @var Weal_Profile_Rating|null
	 */
	private static $instance = null;

	/**
	 * Returns the main instance of the class.
	 *
	 * @return Weal_Profile_Rating
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
		add_filter( 'the_content', array( $this, 'display_rating_html' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_rating_assets' ) );
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
	 * Enqueue rating scripts and styles.
	 */
	public function enqueue_rating_assets() {
		if ( ! is_single() ) {
			return;
		}

		wp_enqueue_style(
			'weal-rating-css',
			WEAL_PROFILE_PLUGIN_URL . 'public/css/rating.css',
			array( 'dashicons' ),
			WEAL_PROFILE_VERSION
		);

		wp_enqueue_script(
			'weal-rating-js',
			WEAL_PROFILE_PLUGIN_URL . 'public/js/rating.js',
			array(),
			WEAL_PROFILE_VERSION,
			true
		);

		wp_localize_script(
			'weal-rating-js',
			'wealRating',
			array(
				'apiUrl' => rest_url( 'weal-profile/v1/rate-post' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Appends rating HTML to the post content.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public function display_rating_html( $content ) {
		if ( ! is_single() ) {
			return $content;
		}

		global $post;
		$post_id = $post->ID;

		$sum     = (int) get_post_meta( $post_id, 'rating_sum', true );
		$count   = (int) get_post_meta( $post_id, 'rating_count', true );
		$average = $count > 0 ? round( $sum / $count, 1 ) : 0;

		$html  = '<div class="post-rating" data-post-id="' . esc_attr( $post_id ) . '" itemscope itemtype="https://schema.org/CreativeWork">';
		$html .= '<meta itemprop="name" content="' . esc_attr( get_the_title( $post_id ) ) . '">';

		if ( $count > 0 ) {
			$html .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
			$html .= '<meta itemprop="ratingValue" content="' . esc_attr( $average ) . '">';
			$html .= '<meta itemprop="ratingCount" content="' . esc_attr( $count ) . '">';
			$html .= '<meta itemprop="bestRating" content="5">';
			$html .= '<meta itemprop="worstRating" content="1">';
		}

		$html .= '<p class="rating-label">' . esc_html__( 'Rate this article:', 'weal-profile' ) . '</p>';
		$html .= '<div class="rating-stars">';

		for ( $i = 1; $i <= 5; $i++ ) {
			if ( $average >= $i ) {
				$fill = '100%';
			} elseif ( $average < ( $i - 1 ) ) {
				$fill = '0%';
			} else {
				$fill = ( ( $average - ( $i - 1 ) ) * 100 ) . '%';
			}

			$html .= '<span class="star-wrapper dashicons dashicons-star-empty" data-rate="' . esc_attr( $i ) . '" data-initial-fill="' . esc_attr( $fill ) . '" style="--fill:' . esc_attr( $fill ) . ';">';
			$html .= '<span class="star-filled dashicons dashicons-star-filled"></span>';
			$html .= '</span>';
		}

		$html .= '</div>';

		$html .= '<div class="rating-result">';
		$html .= '<span class="average-value">' . esc_html( $average ) . '</span> / 5';
		$html .= ' (<span class="count-value">' . esc_html( $count ) . '</span> ' . esc_html__( 'votes', 'weal-profile' ) . ')';
		$html .= '</div>';

		if ( $count > 0 ) {
			$html .= '</div>';
		}

		$html .= '</div>';

		return $content . $html;
	}

	/**
	 * Registers REST API routes.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'weal-profile/v1',
			'/rate-post',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'process_rating_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handles the rating request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function process_rating_request( $request ) {
		$post_id = intval( $request->get_param( 'post_id' ) );
		$rating  = intval( $request->get_param( 'rating' ) );

		if ( $post_id <= 0 || $rating < 1 || $rating > 5 ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid data.', 'weal-profile' ),
				),
				400
			);
		}

		$cookie_name = 'weal_voted_post_' . $post_id;
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'You have already voted.', 'weal-profile' ),
				),
				403
			);
		}

		$sum   = (int) get_post_meta( $post_id, 'rating_sum', true );
		$count = (int) get_post_meta( $post_id, 'rating_count', true );

		$sum += $rating;
		++$count;
		$average = round( $sum / $count, 1 );

		update_post_meta( $post_id, 'rating_sum', $sum );
		update_post_meta( $post_id, 'rating_count', $count );
		update_post_meta( $post_id, 'rating_average', $average );

		setcookie( $cookie_name, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'average' => $average,
					'count'   => $count,
				),
			)
		);
	}
}

<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Manager;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public page manager class.
 */
class Public_Page_Manager {


	private const FALLBACK_SLUGS = array(
		'my-account',
		'my-profile',
		'profile-account',
	);
	private const MAX_ATTEMPTS   = 100;
	private const SLUG_PREFIX    = 'my-account--';

	/**
	 * Find available slug.
	 *
	 * @throws Exception If no available slug found.
	 */
	public function find_available_slug() {
		foreach ( self::FALLBACK_SLUGS as $slug ) {
			if ( ! $this->slug_exists( $slug ) ) {
				return $slug;
			}
		}

		for ( $i = 1; $i <= self::MAX_ATTEMPTS; $i++ ) {
			$slug = self::SLUG_PREFIX . str_pad( $i, 4, '0', STR_PAD_LEFT );
			if ( ! $this->slug_exists( $slug ) ) {
				return $slug;
			}
		}

		throw new Exception(
			esc_html__( 'Unable to find available slug for public page after 100 attempts', 'weal-profile' )
		);
	}

	/**
	 * Check if slug exists.
	 *
	 * @param string $slug Slug to check.
	 */
	public function slug_exists( $slug ) {
		return get_page_by_path( $slug, OBJECT, 'page' ) !== null;
	}

	/**
	 * Get page by slug.
	 *
	 * @param string $slug Page slug.
	 */
	public function get_page_by_slug( $slug ) {
		return get_page_by_path( $slug, OBJECT, 'page' );
	}

	/**
	 * Create page.
	 *
	 * @param string $slug Page slug.
	 * @throws Exception If page creation fails.
	 */
	public function create_page( $slug ) {
		$page_id = wp_insert_post(
			array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_author'    => 1,
				'post_title'     => ucwords( str_replace( '-', ' ', $slug ) ),
				'post_name'      => sanitize_title( $slug ),
				'post_status'    => 'publish',
				'post_content'   => '',
				'post_type'      => 'page',
			)
		);

		if ( is_wp_error( $page_id ) ) {
			throw new Exception( esc_html( $page_id->get_error_message() ) );
		}

		return $page_id;
	}

	/**
	 * Delete page by slug.
	 *
	 * @param string $slug Page slug.
	 */
	public function delete_page_by_slug( $slug ) {
		$page = $this->get_page_by_slug( $slug );
		if ( $page ) {
			wp_delete_post( $page->ID, true );
		}
	}

	/**
	 * Update page URL.
	 *
	 * @param string $old_slug Old slug.
	 * @param string $new_slug New slug.
	 * @throws Exception If page creation fails.
	 */
	public function update_page_url( $old_slug, $new_slug ) {
		$this->delete_page_by_slug( $old_slug );
		$this->create_page( $new_slug );
	}
}

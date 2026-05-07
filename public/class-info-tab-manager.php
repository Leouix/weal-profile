<?php
/**
 * Info Tab Class.
 *
 * @package weal-profile
 */

namespace WealProfile\Public;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use stdClass;

/**
 * Info Tab Class.
 *
 * @package weal-profile
 */
class Info_Tab_Manager {

	/**
	 * Logged user ID.
	 *
	 * @var int
	 */
	private $logged_user_id;
	/**
	 * Admin settings.
	 *
	 * @var mixed
	 */
	private $admin_settings;

	/**
	 * Constructor.
	 *
	 * @param int   $logged_user_id Logged user ID.
	 * @param mixed $admin_settings Admin settings.
	 */
	public function __construct( $logged_user_id, $admin_settings ) {
		$this->logged_user_id = $logged_user_id;
		$this->admin_settings = $admin_settings;
	}

	/**
	 * Save user info.
	 *
	 * @param  int   $user_id     User ID.
	 * @param  array $user_fields User fields.
	 * @return int|\WP_Error
	 * @throws \Exception On error.
	 */
	public function save_user_info( $user_id, $user_fields ) {

		$user_meta = array_filter(
			array(
				'nickname'    => $user_fields['nickname'] ?? null,
				'first_name'  => $user_fields['first_name'] ?? null,
				'last_name'   => $user_fields['last_name'] ?? null,
				'description' => $user_fields['description'] ?? null,
			),
			function ( $value ) {
				return ! is_null( $value );
			}
		);

		$this->update_user_meta( $user_id, $user_meta );

		$user_data = array_merge(
			array(
				'ID' => $this->logged_user_id,
			),
			array_filter(
				array(
					'user_email'      => $user_fields['user_email'] ?? null,
					'user_registered' => $user_fields['user_registered'] ?? null,
					'display_name'    => $user_fields['display_name'] ?? null,
					'user_url'        => $user_fields['user_url'] ?? null,
				),
				function ( $value ) {
					return ! is_null( $value );
				}
			)
		);

		try {
			return wp_update_user( $user_data );
		} catch ( \Exception $e ) {
			throw new \Exception(
				esc_html( $e->getMessage() ),
				absint( $e->getCode() )
			);
		}
	}

	/**
	 * Update user meta.
	 *
	 * @param  int   $user_id   User ID.
	 * @param  array $user_meta User meta.
	 * @return void
	 * @throws \Exception On error.
	 */
	public function update_user_meta( $user_id, $user_meta ) {
		try {
			foreach ( $user_meta as $key => $value ) {
				update_user_meta( $user_id, $key, $value );
			}
		} catch ( \Exception $e ) {
			throw new \Exception(
				esc_html( $e->getMessage() ),
				absint( $e->getCode() )
			);
		}
	}

	/**
	 * Get user data.
	 *
	 * @return string
	 */
	public function get_user_data() {

		$user_meta   = get_user_meta( $this->logged_user_id );
		$meta_fields = array(
			'nickname',
			'first_name',
			'last_name',
			'description',
		);

		$user_data      = get_userdata( $this->logged_user_id );
		$allowed_fields = $this->admin_settings['fields_allowed'] ?? array();

		$user_data_obj     = new stdClass();
		$user_data_obj->ID = $user_data->ID;

		if ( empty( $allowed_fields ) ) {
			$allowed_fields = array( 'display_name', 'nickname', 'description', 'user_url', 'avatar' );
		}

		foreach ( $allowed_fields as $allowed_field ) {
			if ( in_array( $allowed_field, $meta_fields, true ) ) {
				$user_data_obj->$allowed_field = $user_meta[ $allowed_field ][0];
			} else {
				$user_data_obj->$allowed_field = $user_data->$allowed_field;
			}
		}

		return include WEAL_PROFILE_PLUGIN_DIR . 'public/partials/tab-info.php';
	}

	/**
	 * Handle user saving.
	 *
	 * @param  array $post_data Post data.
	 * @return int|\WP_Error
	 * @throws \Exception On error.
	 */
	public function handle_user_saving( $post_data ) {

		$user_data                 = array();
		$user_data['display_name'] = $post_data['display_name'] ?? null;
		$user_data['user_url']     = $post_data['user_url'] ?? null;
		$user_data['nickname']     = $post_data['nickname'] ?? null;
		$user_data['first_name']   = $post_data['first_name'] ?? null;
		$user_data['last_name']    = $post_data['last_name'] ?? null;
		$user_data['description']  = $post_data['description'] ?? null;

		return $this->save_user_info( $this->logged_user_id, $user_data );
	}
}

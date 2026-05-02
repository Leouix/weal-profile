<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings manager class.
 */
class Settings_Manager {

    private const OPTION_NAME = 'weal_profile_settings';

    private $default_fields = array(
        'display_name',
        'user_url',
        'nickname',
        'description',
        'avatar',
    );

    public function get_settings() {
        $settings = get_option( self::OPTION_NAME, array() );

        return array(
            'user_page_url'  => $settings['user_page_url'] ?? null,
            'fields_allowed' => $settings['fields_allowed'] ?? $this->default_fields,
        );
    }

    public function get_user_page_url() {
        $settings = $this->get_settings();
        return $settings['user_page_url'] ?? null;
    }

    public function save_settings( $user_page_url, $fields_allowed ) {
        $data = array(
            'user_page_url'  => sanitize_text_field( $user_page_url ),
            'fields_allowed' => array_map( 'sanitize_text_field', $fields_allowed ),
        );

        return update_option( self::OPTION_NAME, $data );
    }

    public function is_table_exists() {
        // Больше не требуется при использовании Options API
        return true;
    }

    public function clear_cache() {
        // Options API в WordPress кэшируется автоматически (alloptions)
    }

    public function get_default_fields() {
        return $this->default_fields;
    }
}
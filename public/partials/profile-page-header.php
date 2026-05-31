<?php
/**
 * Profile page header partial.
 *
 * Shared between own-profile and other-user-profile templates.
 * Sets up global variables and renders the HTML header/container.
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WealProfile\Includes\Manager\Settings_Manager;

global $weal_profile_user_id;

$weal_profile_is_own_profile          = get_current_user_id() === (int) $weal_profile_user_id;
$weal_profile_avatar_html             = Weal_Profile_Avatar::get_avatar_html( $weal_profile_user_id );
$weal_profile_is_avatar_field_allowed = Weal_Profile_Avatar::get_is_avatar_field_allowed();

$weal_profile_settings_manager = new Settings_Manager();
$weal_profile_allowed_fields   = $weal_profile_settings_manager->get_settings()['fields_allowed'] ?? array();
$weal_profile_userdata         = get_userdata( $weal_profile_user_id );

if ( in_array( 'display_name', $weal_profile_allowed_fields, true ) && ! empty( $weal_profile_userdata->display_name ) ) {
	$weal_profile_display_name = $weal_profile_userdata->display_name;
} elseif ( in_array( 'nickname', $weal_profile_allowed_fields, true ) ) {
	$weal_profile_nickname     = get_user_meta( $weal_profile_user_id, 'nickname', true );
	$weal_profile_display_name = ! empty( $weal_profile_nickname ) ? $weal_profile_nickname : '';
} else {
	$weal_profile_display_name = '';
}

if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
	?>
	<div class="wp-site-blocks">
		<header class="wp-block-template-part">
			<?php block_template_part( 'header' ); ?>
		</header>
	</div>
	<?php
	wp_head();
} else {
	get_header();
}
?>
	<div class="container au-container">
		<div class="entry-content wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained">

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

global $weal_profile_user_id;

$profile_user_id                      = $weal_profile_user_id;
$is_own_profile                       = get_current_user_id() === (int) $profile_user_id;
$weal_profile_avatar_html             = Weal_Profile_Avatar::get_avatar_html( $profile_user_id );
$weal_profile_is_avatar_field_allowed = Weal_Profile_Avatar::get_is_avatar_field_allowed();

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
		<div class="entry-content alignfull wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained">

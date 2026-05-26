<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables for settings page.
 *
 * @var array $fields_allowed_array
 * @var string $user_page_url
 */

?>

<div class="au-container">
	<form id="admin-user-account-form">
		<?php wp_nonce_field( 'weal_profile_admin_save', 'weal_profile_admin_nonce' ); ?>

		<h1><?php esc_html_e( 'Plugin settings page', 'weal-profile' ); ?></h1>

		<div class="url-label-area">
			<label for="adu-form-input" class="sub-title"><?php esc_html_e( 'URL user page:', 'weal-profile' ); ?></label>
			<div class="input-url-wrapper">
				<span><?php printf( '%s', esc_url( get_bloginfo( 'url' ) ) ); ?>/</span><input id="adu-form-input"
						type="text"
						name="weal_profile_page_url"
						value="<?php printf( '%s', esc_html( $user_page_url ) ); ?>"
						disabled
				><div id="lock-url"><div id="dashicons-unlock" class="dashicons dashicons-unlock hidden"></div><div id="dashicons-lock" class="dashicons dashicons-lock visible"></div></div>

				<a id="adu-form-input-text"
					href="<?php printf( '%s', esc_url( get_bloginfo( 'url' ) ) ); ?>/<?php printf( '%s', esc_html( $user_page_url ) ); ?>"
					target="_blank"
				>
					<?php printf( '%s', esc_url( get_bloginfo( 'url' ) ) ); ?>/<?php printf( '%s', esc_html( $user_page_url ) ); ?>
				</a>

		</div>

		<div  class="sub-title"><?php esc_html_e( 'Which fields should be shown in the user account?', 'weal-profile' ); ?></div>

		<div class="label-area">
			<input type="checkbox" id="adu-avatar" name="weal_profile_fields[]" value="avatar" <?php checked( in_array( 'avatar', $fields_allowed_array, true ) ); ?>>
			<label for="adu-avatar"><?php esc_html_e( 'avatar', 'weal-profile' ); ?></label><br>
		</div>

		<div class="label-area">
			<input type="checkbox" id="adu-display_name" name="weal_profile_fields[]" value="display_name" <?php checked( in_array( 'display_name', $fields_allowed_array, true ) ); ?>>
			<label for="adu-display_name"><?php esc_html_e( 'display_name', 'weal-profile' ); ?></label><br>
		</div>

		<div class="label-area">
			<input type="checkbox" id="adu-nickname" name="weal_profile_fields[]" value="nickname" <?php checked( in_array( 'nickname', $fields_allowed_array, true ) ); ?>>
			<label for="adu-nickname"><?php esc_html_e( 'nickname', 'weal-profile' ); ?></label><br>
		</div>

		<div class="label-area">
			<input type="checkbox" id="adu-first_name" name="weal_profile_fields[]" value="first_name" <?php checked( in_array( 'first_name', $fields_allowed_array, true ) ); ?>>
			<label for="adu-first_name"><?php esc_html_e( 'first_name', 'weal-profile' ); ?></label><br>
		</div>

		<div class="label-area">
			<input type="checkbox" id="adu-last_name" name="weal_profile_fields[]" value="last_name" <?php checked( in_array( 'last_name', $fields_allowed_array, true ) ); ?>>
			<label for="adu-last_name"><?php esc_html_e( 'last_name', 'weal-profile' ); ?></label><br>
		</div>

		<div class="label-area">
			<input type="checkbox" id="adu-user_url" name="weal_profile_fields[]" value="user_url" <?php checked( in_array( 'user_url', $fields_allowed_array, true ) ); ?>>
			<label for="adu-user_url"><?php esc_html_e( 'user_url', 'weal-profile' ); ?></label><br>
		</div>

		<div class="label-area">
			<input type="checkbox" id="adu-description" name="weal_profile_fields[]" value="description" <?php checked( in_array( 'description', $fields_allowed_array, true ) ); ?>>
			<label for="adu-description"><?php esc_html_e( 'description', 'weal-profile' ); ?></label><br>
		</div>

		<div class="sub-title"><?php esc_html_e( 'Comment Votes (Likes/Dislikes)', 'weal-profile' ); ?></div>

		<div class="label-area">
			<input type="checkbox" id="comment-votes-enabled" name="weal_profile_comment_votes" value="1" <?php checked( $comment_votes_enabled ); ?>>
			<label for="comment-votes-enabled"><?php esc_html_e( 'Enable likes and dislikes on comments', 'weal-profile' ); ?></label>
		</div>

		<div class="button-area">
			<input id="save-create-button" type="submit" value="<?php esc_attr_e( 'Сохранить настройки', 'weal-profile' ); ?>">
			<div id="success-notice"><?php esc_html_e( 'Success!', 'weal-profile' ); ?></div>
		</div>

	</form>
</div>
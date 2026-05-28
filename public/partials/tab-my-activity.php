<?php
/**
 * Template for the Activity tab container with Posts/Comments subtabs.
 *
 * @package weal-profile
 *
 * Expected variables:
 *   $active_subtab    string  'posts' or 'comments'
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="other-user-tabs">
	<div class="other-user-tab <?php echo 'posts' === $active_subtab ? 'active' : ''; ?>" data-tab="posts" onclick="switchMyAccountTab(this)">
		<?php esc_html_e( 'Posts', 'weal-profile' ); ?>
	</div>
	<div class="other-user-tab <?php echo 'comments' === $active_subtab ? 'active' : ''; ?>" data-tab="comments" onclick="switchMyAccountTab(this)">
		<?php esc_html_e( 'Comments', 'weal-profile' ); ?>
	</div>
</div>
<div id="my-account-subtab-content"></div>

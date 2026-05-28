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
<div class="activity-buttons">
	<div class="activity-button <?php echo 'posts' === $active_subtab ? 'active' : ''; ?>" data-tab="posts" data-wp-action="switch-my-tab">
		<?php esc_html_e( 'Posts', 'weal-profile' ); ?>
	</div>
	<div class="activity-button <?php echo 'comments' === $active_subtab && 'posts' !== $active_subtab ? 'active' : ''; ?>" data-tab="comments" data-wp-action="switch-my-tab">
		<?php esc_html_e( 'Comments', 'weal-profile' ); ?>
	</div>
</div>
<div id="my-account-subtab-content"></div>

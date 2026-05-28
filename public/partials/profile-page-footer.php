<?php
/**
 * Profile page footer partial.
 *
 * Shared between own-profile and other-user-profile templates.
 * Closes containers and renders the HTML footer.
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		</div>
	</div>

<?php
if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
	?>
	<div class="wp-site-blocks">
		<footer class="wp-block-template-part">
			<?php block_template_part( 'footer' ); ?>
		</footer>
	</div>
	<?php
	wp_footer();
} else {
	get_footer();
}

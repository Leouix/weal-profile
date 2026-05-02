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
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link  https://weal.cloud
 * @since 1.0.0
 *
 * @package    Weal_Profile
 * @subpackage Weal_Profile/public/partials
 */

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
			<h2>My Account</h2>
			<div id="main-tabs">
				<div id="tab-button-1"
					class="main-tabs-item"
					onclick="switchTab(this)">
					<div class="text">Comments</div>
				</div>
				<div id="tab-button-3"
					class="main-tabs-item"
					onclick="switchTab(this)">
					<div class="text">My Info</div>
				</div>
			</div>

			<div id="container-results"></div>
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

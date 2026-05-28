<?php
/**
 * Template for the My Posts subtab content.
 *
 * @package weal-profile
 *
 * Expected variables:
 *   $user_posts       array|WP_Post[]
 *   $pagination_html  string
 *   $total_pages      int
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( ! empty( $user_posts ) ) : ?>
	<?php foreach ( $user_posts as $post_item ) : ?>
		<?php setup_postdata( $post_item ); ?>
        <a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">
		<div class="weal-user-post-item">
			<?php if ( has_post_thumbnail( $post_item->ID ) ) : ?>
				<div class="post-thumbnail">
					<?php echo get_the_post_thumbnail( $post_item->ID, 'thumbnail' ); ?>
				</div>
			<?php endif; ?>
			<div class="post-content">
				<h3>

						<?php echo esc_html( get_the_title( $post_item->ID ) ); ?>

				</h3>
			</div>
		</div>
        </a>
	<?php endforeach; ?>
	<?php wp_reset_postdata(); ?>

	<?php if ( $total_pages > 1 ) : ?>
		<div class="weal-pagination" data-subtab="posts">
			<?php echo wp_kses_post( $pagination_html ); ?>
		</div>
	<?php endif; ?>
<?php else : ?>
	<p><?php esc_html_e( 'No posts found.', 'weal-profile' ); ?></p>
<?php endif; ?>

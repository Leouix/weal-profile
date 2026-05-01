<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2>My comments</h2>

<?php
/**
 * User comments variable.
 *
 * @var $user_comments
 */

if ( ! empty( $user_comments ) ) {
	foreach ( $user_comments as $user_comment ) {

		$post_url   = get_permalink( $user_comment->comment_post_ID );
		$post_title = get_the_title( $user_comment->comment_post_ID );
		?>
		<div class="au-comment-area">
		<?php

		printf( '<p class="post-link">Post: <a href="%s" target="_blank">%s</a></p>', esc_url( $post_url ), esc_html( $post_title ) );

		printf( '<p>Comment text: %s</p>', esc_html( $user_comment->comment_content ) );

		printf(
			'<p class="comment-date">Date: %s</p>',
			esc_html( gmdate( 'Y-m-d H:i', strtotime( $user_comment->comment_date ) ) )
		);

		printf( '<p class="comment-author">Author: %s</p>', esc_html( $user_comment->comment_author ) );

		?>
		</div>

		<?php
	}
} else {
	echo esc_html( 'No comments' );
}

?>

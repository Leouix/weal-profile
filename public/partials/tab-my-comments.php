<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

use WealProfile\Includes\Manager\Settings_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<?php
/**
 * User comments variable.
 *
 * @var $user_comments
 */

if ( ! empty( $user_comments ) ) {

    ?>
    <div class="weal-comment-reactions">

        <?php if ( ! empty( $user_comments ) ) : ?>
            <div class="weal-top-comments">
                <h4><?php esc_html_e( 'Top Comments', 'weal-profile' ); ?></h4>
                <ul>
                    <?php foreach ( $user_comments as $comment ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_permalink( $comment->comment_post_ID ) ); ?>#comment-<?php echo esc_attr( $comment->comment_ID ); ?>">
                                <?php echo esc_html( wp_trim_words( $comment->comment_content, 10, '...' ) ); ?>
                            </a>

                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <?php


} else {
	echo esc_html__( 'No comments', 'weal-profile' );
}


?>

<?php
/**
 * Admin-only CSS for the Book and Chapter editor screens.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'mab_enqueue_admin_styles' );

/**
 * Load the small admin stylesheet only on Book and Chapter editor screens.
 *
 * @param string $hook_suffix Current admin page identifier.
 */
function mab_enqueue_admin_styles( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, array( MAB_BOOK_POST_TYPE, MAB_CHAPTER_POST_TYPE ), true ) ) {
		return;
	}

	wp_enqueue_style( 'make-a-book-admin', MAKE_A_BOOK_URL . 'assets/css/admin.css', array(), MAKE_A_BOOK_VERSION );
}

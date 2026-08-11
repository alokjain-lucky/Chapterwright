<?php
/**
 * Live chapter-order suggestions on the Chapter editor screen.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'mab_enqueue_chapter_order_script' );
add_action( 'wp_ajax_mab_next_chapter_order', 'mab_ajax_next_chapter_order' );

/**
 * Load the chapter-order helper script only on the Chapter editor screen.
 *
 * @param string $hook_suffix Current admin page identifier.
 */
function mab_enqueue_chapter_order_script( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || MAB_CHAPTER_POST_TYPE !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script(
		'make-a-book-chapter-order',
		MAKE_A_BOOK_URL . 'assets/js/chapter-order.js',
		array(),
		MAKE_A_BOOK_VERSION,
		true
	);
	wp_localize_script(
		'make-a-book-chapter-order',
		'makeABookChapterOrder',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mab_next_chapter_order' ),
		)
	);
}

/**
 * AJAX callback: return the next suggested chapter order for a book.
 *
 * A read-only convenience endpoint gated by a nonce and the same capability
 * required to edit posts; it never writes data. Lets
 * assets/js/chapter-order.js keep the Order field's suggestion in sync when
 * an author changes the Book dropdown without reloading the screen.
 */
function mab_ajax_next_chapter_order() {
	check_ajax_referer( 'mab_next_chapter_order', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'make-a-book' ) ), 403 );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( wp_unslash( $_POST['book_id'] ) ) : 0;
	if ( ! $book_id || MAB_BOOK_POST_TYPE !== get_post_type( $book_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Choose a book first.', 'make-a-book' ) ), 400 );
	}

	wp_send_json_success( array( 'order' => mab_get_next_chapter_order( $book_id ) ) );
}

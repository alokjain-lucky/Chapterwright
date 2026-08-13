<?php
/**
 * Front-end reader asset loading.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'mab_enqueue_public_assets' );

/**
 * Load reader styles and scripts only on plugin views or pages containing
 * the library shortcode, so the CSS/JS never loads on unrelated pages.
 */
function mab_enqueue_public_assets() {
	$queried_id = get_queried_object_id();
	$content    = $queried_id ? get_post_field( 'post_content', $queried_id ) : '';
	$is_view    = is_singular( array( MAB_BOOK_POST_TYPE, MAB_CHAPTER_POST_TYPE ) )
		|| is_post_type_archive( MAB_BOOK_POST_TYPE );

	if ( ! $is_view && ! ( $content && has_shortcode( $content, 'make_a_book' ) ) ) {
		return;
	}

	wp_enqueue_style( 'make-a-book', MAKE_A_BOOK_URL . 'assets/css/make-a-book.css', array(), MAKE_A_BOOK_VERSION );
	wp_enqueue_script( 'make-a-book-reader', MAKE_A_BOOK_URL . 'assets/js/make-a-book-reader.js', array(), MAKE_A_BOOK_VERSION, true );
	wp_localize_script(
		'make-a-book-reader',
		'makeABookReader',
		array(
			'modeAuto'  => __( 'Use system color mode', 'make-a-book' ),
			'modeLight' => __( 'Use light color mode', 'make-a-book' ),
			'modeDark'  => __( 'Use dark color mode', 'make-a-book' ),
		)
	);

	// The code-snippet block's own frame chrome (language label, copy
	// button) is normally only enqueued on pages that use that block — but
	// assets/js/code-highlight.js synthesizes the same markup around any
	// bare code block, on every book/chapter page, so the styles for it
	// need to be available everywhere too, not just where the block is used.
	wp_enqueue_style( 'make-a-book-code', MAKE_A_BOOK_URL . 'blocks/code-snippet/style.css', array( 'make-a-book' ), MAKE_A_BOOK_VERSION );
	wp_enqueue_script( 'make-a-book-code-highlight', MAKE_A_BOOK_URL . 'assets/js/code-highlight.js', array(), MAKE_A_BOOK_VERSION, true );
	wp_localize_script(
		'make-a-book-code-highlight',
		'makeABookCode',
		array(
			'copyLabel'   => __( 'Copy code', 'make-a-book' ),
			'copiedLabel' => __( 'Copied!', 'make-a-book' ),
		)
	);

	// The table-of-contents drawer only exists on the chapter template
	// (templates/single-mab_chapter.php) — no reason to load its script on
	// the book page, archive, or shortcode-embedded pages.
	if ( is_singular( MAB_CHAPTER_POST_TYPE ) ) {
		wp_enqueue_script( 'make-a-book-toc-drawer', MAKE_A_BOOK_URL . 'assets/js/toc-drawer.js', array(), MAKE_A_BOOK_VERSION, true );
	}
}

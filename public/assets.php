<?php
/**
 * Front-end reader asset loading.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'hsrtech_enqueue_public_assets' );

/**
 * Load reader styles and scripts only on plugin views or pages containing
 * the library shortcode, so the CSS/JS never loads on unrelated pages.
 */
function hsrtech_enqueue_public_assets() {
	$queried_id = get_queried_object_id();
	$content    = $queried_id ? get_post_field( 'post_content', $queried_id ) : '';
	$is_view    = is_singular( array( HSRTECH_BOOK_POST_TYPE, HSRTECH_CHAPTER_POST_TYPE ) )
		|| is_post_type_archive( HSRTECH_BOOK_POST_TYPE );

	if ( ! $is_view && ! ( $content && has_shortcode( $content, 'hsrtech_books' ) ) ) {
		return;
	}

	wp_enqueue_style( 'chapterwright', HSRTECH_URL . 'assets/css/chapterwright.css', array(), HSRTECH_VERSION );
	wp_enqueue_script( 'chapterwright-reader', HSRTECH_URL . 'assets/js/chapterwright-reader.js', array(), HSRTECH_VERSION, true );
	wp_localize_script(
		'chapterwright-reader',
		'hsrtechReader',
		array(
			'modeAuto'  => __( 'Use system color mode', 'chapterwright' ),
			'modeLight' => __( 'Use light color mode', 'chapterwright' ),
			'modeDark'  => __( 'Use dark color mode', 'chapterwright' ),
		)
	);

	// The code-snippet block's own frame chrome (language label, copy
	// button) is normally only enqueued on pages that use that block — but
	// assets/js/code-highlight.js synthesizes the same markup around any
	// bare code block, on every book/chapter page, so the styles for it
	// need to be available everywhere too, not just where the block is used.
	wp_enqueue_style( 'chapterwright-code', HSRTECH_URL . 'blocks/code-snippet/style.css', array( 'chapterwright' ), HSRTECH_VERSION );
	wp_enqueue_script( 'chapterwright-code-highlight', HSRTECH_URL . 'assets/js/code-highlight.js', array(), HSRTECH_VERSION, true );
	wp_localize_script(
		'chapterwright-code-highlight',
		'hsrtechCode',
		array(
			'copyLabel'   => __( 'Copy code', 'chapterwright' ),
			'copiedLabel' => __( 'Copied!', 'chapterwright' ),
		)
	);

	// The table-of-contents drawer only exists on the chapter template
	// (templates/single-hsrtech_chapter.php) — no reason to load its script on
	// the book page, archive, or shortcode-embedded pages.
	if ( is_singular( HSRTECH_CHAPTER_POST_TYPE ) ) {
		wp_enqueue_script( 'chapterwright-toc-drawer', HSRTECH_URL . 'assets/js/toc-drawer.js', array(), HSRTECH_VERSION, true );
	}
}

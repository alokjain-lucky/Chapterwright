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
	$queried_id            = get_queried_object_id();
	$content               = $queried_id ? get_post_field( 'post_content', $queried_id ) : '';
	$is_view               = is_singular( array( HSRTECH_BOOK_POST_TYPE, HSRTECH_CHAPTER_POST_TYPE ) )
		|| is_post_type_archive( HSRTECH_BOOK_POST_TYPE );
	$has_library_shortcode = $content && has_shortcode( $content, 'hsrtech_books' );

	if ( $is_view || $has_library_shortcode ) {
		wp_enqueue_style( 'chapterwright', HSRTECH_URL . 'assets/css/chapterwright.css', array(), HSRTECH_VERSION );

		// Only printed when a site owner has actually set a non-zero offset
		// (Settings → "Button position") — chapterwright.css's own
		// var(--hsrtech-toc-jump-offset, 0px) fallback already keeps every
		// other site at today's plain 2rem/1.25rem position with nothing
		// extra in the page's <head> at all.
		$hsrtech_toc_button_offset = hsrtech_toc_button_offset();
		if ( $hsrtech_toc_button_offset > 0 ) {
			wp_add_inline_style(
				'chapterwright',
				sprintf( ':root { --hsrtech-toc-jump-offset: %dpx; }', $hsrtech_toc_button_offset )
			);
		}

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
	}

	// The code-snippet block's highlighting script used to load only
	// alongside the rest of the reader experience above (book/chapter/
	// archive/shortcode pages) — which meant a Code Snippet block used in an
	// ordinary post or page got the frame (language label, copy button; that
	// part is block.json's own "style" registration, and always loads
	// wherever the block does) but never the colored tokens or working copy
	// button, since the script that adds those simply hadn't run. Loading it
	// here too, whenever the current post actually contains the block,
	// fixes that without pulling in the rest of the book-reading experience
	// (chapterwright.css's book hero/TOC/etc. styles, the mode toggle) on a
	// page that has nothing to do with any of that.
	$has_code_snippet_block = $queried_id && has_block( 'chapterwright/code-snippet', $queried_id );

	if ( $is_view || $has_library_shortcode || $has_code_snippet_block ) {
		// No dependency on the 'chapterwright' handle above: every custom
		// property this stylesheet reads has a literal fallback (see its own
		// docblock), specifically so it never needs that other stylesheet to
		// also be loaded, on a page where it might not be (like this one).
		wp_enqueue_style( 'chapterwright-code', HSRTECH_URL . 'blocks/code-snippet/style.css', array(), HSRTECH_VERSION );
		// Registered (not just enqueued outright) via the same shared function
		// the block editor's own dependency graph uses — see
		// hsrtech_register_code_highlight_script(), blocks/code-snippet/code-snippet.php
		// — so there's one URL/version for this script, not two copies that
		// could drift apart.
		hsrtech_register_code_highlight_script();
		wp_enqueue_script( 'chapterwright-code-highlight' );
		wp_localize_script(
			'chapterwright-code-highlight',
			'hsrtechCode',
			array(
				'copyLabel'   => __( 'Copy code', 'chapterwright' ),
				'copiedLabel' => __( 'Copied!', 'chapterwright' ),
			)
		);
	}

	// The table-of-contents drawer only exists on the chapter template
	// (templates/single-hsrtech_chapter.php) — no reason to load its script on
	// the book page, archive, or shortcode-embedded pages.
	if ( is_singular( HSRTECH_CHAPTER_POST_TYPE ) ) {
		wp_enqueue_script( 'chapterwright-toc-drawer', HSRTECH_URL . 'assets/js/toc-drawer.js', array(), HSRTECH_VERSION, true );
	}
}

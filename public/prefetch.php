<?php
/**
 * Background prefetch of the previous/next chapter, for faster navigation.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'hsrtech_print_chapter_prefetch_links' );

/**
 * Hint the browser to fetch the previous/next chapter's HTML in the
 * background while the current chapter is being read, so clicking
 * "Previous"/"Next" (.hsrtech-reader__next in templates/single-hsrtech_chapter.php)
 * is typically served from cache instead of waiting on a fresh request.
 *
 * A plain `<link rel="prefetch">` rather than a JS-driven fetch(): it's the
 * browser's own purpose-built mechanism for exactly this situation ("the
 * visitor will very likely navigate here next"), so request timing, cache
 * freshness, and eviction are all handled by the browser itself rather than
 * reimplemented here. It also degrades to a complete no-op in browsers that
 * don't support it (notably Safari) instead of doing extra background
 * network work those browsers couldn't have benefited from anyway, and
 * Chrome already skips prefetch automatically when the visitor has Data
 * Saver turned on — nothing extra to implement for that either.
 *
 * Hooked directly to `wp_head`, independent of the chapter template's own
 * local variables, because on this plugin's routed pages `wp_head()` fires
 * before the template body runs at all (see the ordering note in
 * templates/partials/document-start.php) — the main query has already
 * resolved by this point regardless, so `is_singular()` and
 * `get_queried_object_id()` work exactly as they would anywhere else.
 */
function hsrtech_print_chapter_prefetch_links() {
	if ( ! is_singular( HSRTECH_CHAPTER_POST_TYPE ) ) {
		return;
	}

	$chapter_id = get_queried_object_id();
	$book_id    = absint( get_post_meta( $chapter_id, '_hsrtech_book_id', true ) );

	if ( ! $book_id ) {
		return;
	}

	$neighbors = hsrtech_locate_chapter( $chapter_id, hsrtech_get_chapters( $book_id ) );

	foreach ( array( $neighbors['previous'], $neighbors['next'] ) as $neighbor ) {
		if ( $neighbor ) {
			printf( '<link rel="prefetch" href="%s">' . "\n", esc_url( get_permalink( $neighbor ) ) );
		}
	}
}

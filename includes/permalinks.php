<?php
/**
 * Chapter permalinks nested under their book: /books/{book-slug}/{chapter-slug}/.
 *
 * Chapters register with `'rewrite' => false` (includes/content-types.php) so
 * WordPress never sets up the old flat `/book-chapter/%postname%/` structure
 * at all — this file is the only thing generating and matching chapter URLs.
 *
 * Nothing here is stored anywhere: a chapter's URL is computed fresh on every
 * request from its current `_hsrtech_book_id` meta and the book's current
 * slug, exactly like every other WordPress permalink. Existing chapters need
 * no migration and keep their content untouched — only the URL changes.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hsrtech_register_chapter_rewrite_rules' );
add_filter( 'post_type_link', 'hsrtech_filter_chapter_permalink', 10, 2 );

/**
 * Route /books/{book-slug}/{chapter-slug}/ to the matching chapter.
 *
 * The book-slug segment is not itself used to look up the chapter — a
 * chapter's post_name is unique across the whole post type regardless of
 * which book it belongs to, so `name` + `post_type` alone resolve it
 * unambiguously, the same way WordPress's own default post-type rewrite
 * would. A request with the *wrong* book segment for an otherwise-valid
 * chapter slug still resolves (WordPress's own redirect_canonical(), active
 * by default, sends it on to the correct URL since get_permalink() below is
 * what it compares against).
 *
 * Registered at 'top' priority so it is matched before WordPress's own
 * `books/%postname%/` rule for the Book post type — the two never actually
 * collide (one segment vs. two), but 'top' keeps this rule first regardless
 * of what other plugins add later.
 *
 * The second rule below is the same match with a trailing `/page/N/`, for a
 * chapter that uses the `<!--nextpage-->` Quick Tag to paginate its own
 * content — WordPress's default post-type rewrite generates this
 * automatically; a hand-written rule like this one does not get it for free.
 */
function hsrtech_register_chapter_rewrite_rules() {
	add_rewrite_rule(
		'^books/([^/]+)/([^/]+)/page/([0-9]+)/?$',
		'index.php?post_type=' . HSRTECH_CHAPTER_POST_TYPE . '&name=$matches[2]&page=$matches[3]',
		'top'
	);

	add_rewrite_rule(
		'^books/([^/]+)/([^/]+)/?$',
		'index.php?post_type=' . HSRTECH_CHAPTER_POST_TYPE . '&name=$matches[2]',
		'top'
	);
}

/**
 * Build a chapter's permalink as /books/{book-slug}/{chapter-slug}/.
 *
 * Runs for every get_permalink() call on a chapter regardless of post
 * status (draft, pending, etc.) — WordPress always applies the
 * `post_type_link` filter, including for the ugly fallback link a
 * `'rewrite' => false` post type would otherwise get, so this fully replaces
 * that fallback rather than adjusting it.
 *
 * @param string  $post_link Default permalink WordPress computed.
 * @param WP_Post $post      The post in question.
 * @return string
 */
function hsrtech_filter_chapter_permalink( $post_link, $post ) {
	if ( ! is_object( $post ) || HSRTECH_CHAPTER_POST_TYPE !== $post->post_type ) {
		return $post_link;
	}

	$slug    = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
	$book_id = absint( get_post_meta( $post->ID, '_hsrtech_book_id', true ) );
	$book    = $book_id ? get_post( $book_id ) : null;

	if ( $book && HSRTECH_BOOK_POST_TYPE === $book->post_type && $book->post_name ) {
		return home_url( user_trailingslashit( "books/{$book->post_name}/{$slug}" ) );
	}

	// A chapter not yet assigned to a book has nothing to nest under. Falls
	// back to the plugin's old flat base rather than erroring — this is only
	// reachable for a chapter that isn't meant to be publicly linked yet
	// anyway (see README's "must be assigned to a Book" note).
	return home_url( user_trailingslashit( "book-chapter/{$slug}" ) );
}

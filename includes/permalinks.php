<?php
/**
 * Chapter and Section permalinks, both nested under their book:
 * /books/{book-slug}/{chapter-slug}/ and /books/{book-slug}/{section-slug}/.
 *
 * Both post types register with `'rewrite' => false` (includes/content-types.php)
 * so WordPress never sets up a flat `%postname%/` structure for either — this
 * file is the only thing generating and matching their URLs.
 *
 * Nothing here is stored anywhere: a post's URL is computed fresh on every
 * request from its current `_hsrtech_book_id` meta and the book's current
 * slug, exactly like every other WordPress permalink. Existing posts need no
 * migration and keep their content untouched — only the URL changes.
 *
 * Chapter and Section end up sharing the exact same URL shape once both are
 * nested one level under their book — a chapter's and a section's post_name
 * are each unique only *within* their own post type, not against each
 * other, so the same /books/{book-slug}/{leaf-slug}/ request could in
 * principle match either. hsrtech_register_nested_rewrite_rules() below
 * resolves both with one rule (`post_type` as an array), the same way
 * WordPress core resolves `'post_type' => 'any'`-style queries — see that
 * function's own docblock.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'hsrtech_register_nested_rewrite_rules' );
add_filter( 'post_type_link', 'hsrtech_filter_chapter_permalink', 10, 2 );
add_filter( 'post_type_link', 'hsrtech_filter_section_permalink', 10, 2 );

/**
 * Route /books/{book-slug}/{leaf-slug}/ to the matching Chapter or Section.
 *
 * The book-slug segment is not itself used to look up which post it is — a
 * chapter's or a section's post_name is unique across its own whole post
 * type regardless of which book it belongs to, so `name` alone resolves it
 * unambiguously among posts of that type, the same way WordPress's own
 * default post-type rewrite would. `post_type` is passed as an array
 * (`post_type[]=...&post_type[]=...`) covering both, since a single request
 * can't know in advance which one it is; WP_Query's own `name` matching
 * already works correctly against a `post_type` array (core relies on this
 * same mechanism for `'post_type' => 'any'` queries), and once the actual
 * post resolves, `is_singular( HSRTECH_CHAPTER_POST_TYPE )` /
 * `is_singular( HSRTECH_SECTION_POST_TYPE )` in public/template-router.php
 * each check the *resolved* post's real type, not the request's, so routing
 * to the right template still works correctly either way. A request with
 * the *wrong* book segment for an otherwise-valid slug still resolves
 * (WordPress's own redirect_canonical(), active by default, sends it on to
 * the correct URL since get_permalink() below is what it compares against).
 *
 * Registered at 'top' priority so it is matched before WordPress's own
 * `books/%postname%/` rule for the Book post type — the two never actually
 * collide (one segment vs. two), but 'top' keeps this rule first regardless
 * of what other plugins add later.
 *
 * The first rule below is the chapter-only variant with a trailing
 * `/page/N/`, for a chapter that uses the `<!--nextpage-->` Quick Tag to
 * paginate its own content — WordPress's default post-type rewrite
 * generates this automatically; a hand-written rule like this one does not
 * get it for free. Scoped to Chapter only since Section's own template has
 * no `wp_link_pages()` call and never splits its content across pages.
 */
function hsrtech_register_nested_rewrite_rules() {
	add_rewrite_rule(
		'^books/([^/]+)/([^/]+)/page/([0-9]+)/?$',
		'index.php?post_type=' . HSRTECH_CHAPTER_POST_TYPE . '&name=$matches[2]&page=$matches[3]',
		'top'
	);

	add_rewrite_rule(
		'^books/([^/]+)/([^/]+)/?$',
		'index.php?post_type[]=' . HSRTECH_CHAPTER_POST_TYPE . '&post_type[]=' . HSRTECH_SECTION_POST_TYPE . '&name=$matches[2]',
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

	// A chapter that isn't published yet can't be reliably *previewed* at its
	// pretty /books/{book}/{chapter}/ URL: that URL only resolves through the
	// name-based rewrite rule above, and WordPress's own preview machinery
	// (redirect_canonical()'s is_preview()/nonce check, WP_Query's own status
	// widening) only ever trusts a preview request that identifies the post by
	// numeric ID (`?p=123`), the same way get_permalink() itself falls back to
	// a plain `?p=123` URL for a draft 'post'/'page' whose slug hasn't been
	// saved yet. Chapters always have *some* slug (the fallback below computes
	// one from the title even before the post is ever saved), so that same
	// core fallback never kicks in for us — without this, get_preview_post_link()
	// (admin/rest/chapters.php's "View" action, and WordPress's own built-in
	// block-editor "Preview" button, which both build the preview URL from
	// this filter's return value) hands back a pretty URL that 404s, and
	// redirect_canonical() strips the `preview` query arg on the way there.
	// Matches how WP core's own draft-preview fallback behaves; only the base
	// URL shape changes; get_preview_post_link() layers `preview=true` (and,
	// for the block editor's autosave-backed Preview button, `preview_nonce`)
	// on top of whatever this returns either way.
	if ( 'publish' !== $post->post_status ) {
		return add_query_arg(
			array(
				'p'         => $post->ID,
				'post_type' => HSRTECH_CHAPTER_POST_TYPE,
			),
			home_url( '/' )
		);
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
	// anyway (see README's "must be assigned to a Book" note). No rewrite
	// rule matches this fallback shape any more (dropped when chapters
	// nested under their book), so it is a non-functional placeholder for
	// that unreachable case, not a working URL — same trade-off Section's
	// own fallback below makes.
	return home_url( user_trailingslashit( "book-chapter/{$slug}" ) );
}

/**
 * Build a section's permalink as /books/{book-slug}/{section-slug}/.
 *
 * Mirrors hsrtech_filter_chapter_permalink() exactly — see that function's
 * docblock for why `post_type_link` is the right hook and why this fully
 * replaces the fallback link a `'rewrite' => false` post type would
 * otherwise get.
 *
 * @param string  $post_link Default permalink WordPress computed.
 * @param WP_Post $post      The post in question.
 * @return string
 */
function hsrtech_filter_section_permalink( $post_link, $post ) {
	if ( ! is_object( $post ) || HSRTECH_SECTION_POST_TYPE !== $post->post_type ) {
		return $post_link;
	}

	// Same preview-safety fallback as hsrtech_filter_chapter_permalink() above
	// — see that function's docblock for why a non-published post needs the
	// plain ?p=ID form rather than the pretty nested URL.
	if ( 'publish' !== $post->post_status ) {
		return add_query_arg(
			array(
				'p'         => $post->ID,
				'post_type' => HSRTECH_SECTION_POST_TYPE,
			),
			home_url( '/' )
		);
	}

	$slug    = $post->post_name ? $post->post_name : sanitize_title( $post->post_title );
	$book_id = absint( get_post_meta( $post->ID, '_hsrtech_book_id', true ) );
	$book    = $book_id ? get_post( $book_id ) : null;

	if ( $book && HSRTECH_BOOK_POST_TYPE === $book->post_type && $book->post_name ) {
		return home_url( user_trailingslashit( "books/{$book->post_name}/{$slug}" ) );
	}

	// A section not yet assigned to a book has nothing to nest under, and
	// (unlike a chapter) never had an older working flat URL to preserve
	// either — Section only ever existed as a post type from 3.0.0 onward,
	// already nested. Falls back to the plugin's earlier flat `/section/`
	// base as a non-functional placeholder rather than erroring; no rewrite
	// rule matches it, but an unassigned section isn't meant to be publicly
	// reachable anyway (hsrtech_build_toc_sections() only ever links a
	// section that belongs to a book).
	return home_url( user_trailingslashit( "section/{$slug}" ) );
}

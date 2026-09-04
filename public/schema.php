<?php
/**
 * Book and Chapter schema.org structured data.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'hsrtech_print_schema' );
add_filter( 'user_contactmethods', 'hsrtech_add_author_contact_methods' );

/**
 * Add "LinkedIn URL" and "GitHub URL" fields to every user's own profile
 * screen (wp-admin/profile.php), alongside the built-in Website field —
 * the standard WordPress mechanism for exactly this (the same one many
 * themes use to add a Twitter/Instagram field of their own). Values live
 * in ordinary user meta under these two keys and are entirely optional;
 * nothing here is specific to any one person or site, so a fresh install
 * of this plugin has both fields simply empty until an author fills them
 * in — hsrtech_build_author_schema() below only emits a `sameAs` entry
 * for whichever of these (or the `hsrtech_schema_author_same_as` filter)
 * actually has a value.
 *
 * @param array $methods Existing contact-method fields.
 * @return array
 */
function hsrtech_add_author_contact_methods( $methods ) {
	$methods['hsrtech_linkedin'] = __( 'LinkedIn URL', 'chapterwright' );
	$methods['hsrtech_github']   = __( 'GitHub URL', 'chapterwright' );
	return $methods;
}

/**
 * Build the JSON-LD `Person` entity for a book/chapter/section's author.
 *
 * Beyond the author's display name and their profile's Website field
 * (both already WordPress core fields), this adds three things aimed at
 * helping a search engine recognize the same person as one consistent
 * entity across every page rather than a bag of disconnected facts:
 * a stable `@id` (their author archive URL, `#person`-anchored) so
 * multiple JSON-LD blocks across different pages all point at the same
 * node instead of search engines needing to guess they're the same
 * person; an `image` from their Gravatar, which works out of the box for
 * any WordPress user with no extra setup; and `sameAs`, external profile
 * URLs (LinkedIn/GitHub via the contact-method fields above) that are
 * the actual signal search engines use to tie a name to a real-world
 * identity. `sameAs` is left out entirely rather than an empty array
 * when nothing is filled in, so a fresh install's schema stays exactly
 * as before until an author adds a profile link.
 *
 * @param int $author_id WordPress user ID (a post's `post_author`).
 * @return array
 */
function hsrtech_build_author_schema( $author_id ) {
	$author_id = (int) $author_id;
	$same_as   = array_values(
		array_filter(
			array(
				get_the_author_meta( 'hsrtech_linkedin', $author_id ),
				get_the_author_meta( 'hsrtech_github', $author_id ),
			)
		)
	);

	/**
	 * Filters the external profile URLs (`sameAs`) used to identify a
	 * book/chapter/section's author in its JSON-LD `Person` entity — lets
	 * a site add more than the built-in LinkedIn/GitHub fields (an X/Twitter
	 * profile, an Amazon author page, etc.) without editing this file.
	 *
	 * @param string[] $same_as   URLs collected so far.
	 * @param int      $author_id WordPress user ID.
	 */
	$same_as = apply_filters( 'hsrtech_schema_author_same_as', $same_as, $author_id );

	$author = array(
		'@type' => 'Person',
		'@id'   => get_author_posts_url( $author_id ) . '#person',
		'name'  => get_the_author_meta( 'display_name', $author_id ),
		'url'   => get_the_author_meta( 'url', $author_id ),
		'image' => get_avatar_url( $author_id ),
	);

	if ( $same_as ) {
		$author['sameAs'] = $same_as;
	}

	return $author;
}

/**
 * Print Book or Chapter JSON-LD on singular plugin views.
 *
 * Values come from WordPress APIs and are encoded with wp_json_encode(),
 * keeping structured data synchronized with the visible title, excerpt,
 * author, cover, publication date, parent book, and chapter position.
 */
function hsrtech_print_schema() {
	if ( ! is_singular( array( HSRTECH_BOOK_POST_TYPE, HSRTECH_CHAPTER_POST_TYPE ) ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$schema  = array(
		'@context'      => 'https://schema.org',
		'@type'         => HSRTECH_BOOK_POST_TYPE === get_post_type( $post_id ) ? 'Book' : 'Chapter',
		'name'          => get_the_title( $post_id ),
		'url'           => get_permalink( $post_id ),
		'description'   => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
		'datePublished' => get_the_date( DATE_W3C, $post_id ),
		'dateModified'  => get_the_modified_date( DATE_W3C, $post_id ),
		'author'        => hsrtech_build_author_schema( (int) get_post_field( 'post_author', $post_id ) ),
	);

	if ( has_post_thumbnail( $post_id ) ) {
		$schema['image'] = get_the_post_thumbnail_url( $post_id, 'full' );
	}

	if ( HSRTECH_CHAPTER_POST_TYPE === get_post_type( $post_id ) ) {
		$book_id = absint( get_post_meta( $post_id, '_hsrtech_book_id', true ) );
		if ( $book_id ) {
			$schema['isPartOf'] = array(
				'@type' => 'Book',
				'name'  => get_the_title( $book_id ),
				'url'   => get_permalink( $book_id ),
			);
			$schema['position'] = absint( get_post_meta( $post_id, '_hsrtech_order', true ) );
		}
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode() escapes forward slashes by default (JSON_UNESCAPED_SLASHES is deliberately omitted), so a title/excerpt/name containing "</script>" cannot break out of this tag; JSON_UNESCAPED_UNICODE only affects how non-ASCII characters are represented and carries no markup risk. ?></script>
	<?php
}

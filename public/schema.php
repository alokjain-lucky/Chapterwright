<?php
/**
 * Book and Chapter schema.org structured data.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', 'mab_print_schema' );

/**
 * Print Book or Chapter JSON-LD on singular plugin views.
 *
 * Values come from WordPress APIs and are encoded with wp_json_encode(),
 * keeping structured data synchronized with the visible title, excerpt,
 * author, cover, publication date, parent book, and chapter position.
 */
function mab_print_schema() {
	if ( ! is_singular( array( MAB_BOOK_POST_TYPE, MAB_CHAPTER_POST_TYPE ) ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$schema  = array(
		'@context'      => 'https://schema.org',
		'@type'         => MAB_BOOK_POST_TYPE === get_post_type( $post_id ) ? 'Book' : 'Chapter',
		'name'          => get_the_title( $post_id ),
		'url'           => get_permalink( $post_id ),
		'description'   => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
		'datePublished' => get_the_date( DATE_W3C, $post_id ),
		'dateModified'  => get_the_modified_date( DATE_W3C, $post_id ),
		'author'        => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
			'url'   => get_the_author_meta( 'url', (int) get_post_field( 'post_author', $post_id ) ),
		),
	);

	if ( has_post_thumbnail( $post_id ) ) {
		$schema['image'] = get_the_post_thumbnail_url( $post_id, 'full' );
	}

	if ( MAB_CHAPTER_POST_TYPE === get_post_type( $post_id ) ) {
		$book_id = absint( get_post_meta( $post_id, '_mab_book_id', true ) );
		if ( $book_id ) {
			$schema['isPartOf'] = array(
				'@type' => 'Book',
				'name'  => get_the_title( $book_id ),
				'url'   => get_permalink( $book_id ),
			);
			$schema['position'] = absint( get_post_meta( $post_id, '_mab_order', true ) );
		}
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safely encoded for a script data block. ?></script>
	<?php
}

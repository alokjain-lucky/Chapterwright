<?php
/**
 * Runtime smoke checks executed inside wp-env with `npm run env:test`.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$failures = array();

foreach ( array( 'mab_book', 'mab_chapter' ) as $post_type ) {
	if ( ! post_type_exists( $post_type ) ) {
		$failures[] = "Post type {$post_type} is not registered.";
	}
}

// Slug => expected chapter count. Keep in sync with tests/fixtures/seed-books.php.
$expected_chapter_counts = array(
	'wordpress-security-field-guide' => 8,
	'wordpress-speed-handbook'       => 8,
	'ai-in-wordpress-7'              => 5,
);

$books = get_posts( array( 'post_type' => 'mab_book', 'post_status' => 'publish', 'numberposts' => -1 ) );
if ( count( $expected_chapter_counts ) !== count( $books ) ) {
	$failures[] = 'Expected exactly ' . count( $expected_chapter_counts ) . ' seeded books; found ' . count( $books ) . '.';
}

$total_chapters = 0;

foreach ( $books as $book ) {
	$chapters        = mab_get_chapters( $book->ID );
	$total_chapters += count( $chapters );

	$expected = isset( $expected_chapter_counts[ $book->post_name ] ) ? $expected_chapter_counts[ $book->post_name ] : null;
	if ( null === $expected ) {
		$failures[] = "Unexpected seeded book slug: {$book->post_name}.";
	} elseif ( $expected !== count( $chapters ) ) {
		$failures[] = "Expected {$expected} chapters for {$book->post_title}; found " . count( $chapters ) . '.';
	}
	if ( empty( get_post_meta( $book->ID, '_mab_subtitle', true ) ) ) {
		$failures[] = "Book {$book->post_title} has no subtitle.";
	}
}

if ( $failures ) {
	foreach ( $failures as $failure ) {
		WP_CLI::warning( $failure );
	}
	WP_CLI::error( 'Make a Book smoke checks failed.' );
}

WP_CLI::success( "Make a Book smoke checks passed: {$total_chapters} ordered chapters across " . count( $books ) . ' books, and required metadata are present.' );

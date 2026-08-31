<?php
/**
 * Shared content queries used by templates, admin screens, and the block.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch chapters assigned to a book in reading order.
 *
 * Chapters first sort by the numeric `_hsrtech_order` value. Publication date is
 * a stable fallback when two chapters share the same order value.
 *
 * @param int      $book_id  Book post ID.
 * @param string[] $statuses Post statuses to include. Defaults to only
 *                            'publish' — every existing call site (reading
 *                            navigation, pagination counts) wants real,
 *                            readable chapters only. Pass
 *                            `array( 'publish', 'draft' )` specifically for
 *                            building the *visual* table of contents when
 *                            `hsrtech_show_draft_chapters()` is on (see
 *                            templates/single-hsrtech_book.php and
 *                            templates/single-hsrtech_chapter.php) — draft
 *                            chapters returned that way are rendered
 *                            unlinked in templates/partials/toc-list.php,
 *                            never used for prev/next navigation.
 * @return WP_Post[] Ordered chapter posts.
 */
function hsrtech_get_chapters( $book_id, $statuses = array( 'publish' ) ) {
	return get_posts(
		array(
			'post_type'      => HSRTECH_CHAPTER_POST_TYPE,
			'post_status'    => $statuses,
			'posts_per_page' => -1,
			'meta_key'       => '_hsrtech_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for editor-defined chapter order.
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'date'           => 'ASC',
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Chapter-to-book relationship is stored as post meta.
				array(
					'key'     => '_hsrtech_book_id',
					'value'   => absint( $book_id ),
					'compare' => '=',
				),
			),
		)
	);
}

/**
 * Build the table-of-contents section list for a book: each of its Section
 * posts (in order, from hsrtech_get_book_sections()) with the chapters
 * assigned to it, plus a final unlabeled "Chapters" group for any chapter
 * not assigned to a section. A section left with no currently-visible
 * chapters (e.g. all its chapters are still drafts) is dropped from the
 * result entirely rather than rendered empty.
 *
 * Shared by every place that needs this exact grouping so they can't drift
 * out of sync with each other: the book page's own table of contents
 * (templates/single-hsrtech_book.php) and the chapter page's table-of-contents
 * drawer (templates/single-hsrtech_chapter.php) both build `$sections` this way
 * and then both render it through the same templates/partials/toc-list.php.
 *
 * Deliberately calls hsrtech_get_book_sections() with no status argument, so
 * it only ever sees published sections — this builds a table of contents
 * shown to anonymous site visitors, and a draft/pending/private/future
 * section's own permalink already 404s for them, so surfacing its title here
 * would only leak it, not offer working access to it.
 *
 * @param int       $book_id  Book post ID.
 * @param WP_Post[] $chapters Chapters already fetched via hsrtech_get_chapters( $book_id ).
 * @return array<int,array{name:string,description:string,url:string,chapters:WP_Post[]}>
 */
function hsrtech_build_toc_sections( $book_id, $chapters ) {
	$sections     = array();
	$index_by_id  = array();
	$section_rows = hsrtech_get_book_sections( $book_id );
	$unassigned   = array();

	foreach ( $section_rows as $row ) {
		// A section's short description (above, its excerpt) and its optional
		// full introduction page (its content) are independent: the page
		// exists at the section's own permalink, linked from the heading,
		// never inlined here — the description text keeps showing exactly as
		// before, whether or not the section also has a page worth linking to
		// (`has_content`, hsrtech_prepare_section_post(), includes/sections.php).
		$index_by_id[ $row['id'] ] = count( $sections );
		$sections[]                = array(
			'name'        => $row['name'],
			'description' => $row['description'],
			'url'         => $row['has_content'] ? get_permalink( $row['id'] ) : '',
			'chapters'    => array(),
		);
	}

	foreach ( $chapters as $chapter ) {
		$section_id = absint( get_post_meta( $chapter->ID, '_hsrtech_section_id', true ) );
		if ( $section_id && isset( $index_by_id[ $section_id ] ) ) {
			$sections[ $index_by_id[ $section_id ] ]['chapters'][] = $chapter;
		} else {
			$unassigned[] = $chapter;
		}
	}

	if ( $unassigned ) {
		$sections[] = array(
			'name'        => __( 'Chapters', 'chapterwright' ),
			'description' => '',
			'url'         => '',
			'chapters'    => $unassigned,
		);
	}

	return array_values(
		array_filter(
			$sections,
			static function ( $section ) {
				return ! empty( $section['chapters'] );
			}
		)
	);
}

/**
 * Locate a chapter's position and immediate neighbors within an already-
 * fetched, already-ordered list of chapters.
 *
 * Shared by templates/single-hsrtech_chapter.php (previous/next navigation, the
 * "X of Y" counter, and the book-completion progress bar all need to know
 * where the current chapter sits) and hsrtech_print_chapter_prefetch_links()
 * (public/prefetch.php), which needs the same neighbors to hint the browser
 * to fetch them ahead of a click. Pulled out specifically so those two call
 * sites can't drift into finding "the next chapter" two different ways.
 *
 * @param int       $chapter_id Chapter post ID to locate.
 * @param WP_Post[] $chapters  Chapters already fetched via hsrtech_get_chapters( $book_id ), in reading order.
 * @return array{index:int|false, previous:WP_Post|null, next:WP_Post|null} 'index' is false when $chapter_id isn't in $chapters (e.g. an orphaned or unpublished chapter).
 */
function hsrtech_locate_chapter( $chapter_id, $chapters ) {
	foreach ( $chapters as $index => $chapter ) {
		if ( $chapter->ID === $chapter_id ) {
			return array(
				'index'    => $index,
				'previous' => $index > 0 ? $chapters[ $index - 1 ] : null,
				'next'     => isset( $chapters[ $index + 1 ] ) ? $chapters[ $index + 1 ] : null,
			);
		}
	}

	return array(
		'index'    => false,
		'previous' => null,
		'next'     => null,
	);
}

/**
 * Locate the previous/next chapter around a section's own introduction page.
 *
 * A section's page (when it has one — see hsrtech_prepare_section_post()'s
 * `has_content`) sits in the book's reading order right before the first
 * chapter assigned to it, the same way its heading sits right before that
 * chapter's row in the table of contents. So "next" from a section page is
 * that first chapter, and "previous" is whichever chapter immediately
 * precedes it in the book's overall order — typically the last chapter of
 * whatever section came before this one, or nothing at all if this section
 * is first in the book.
 *
 * @param int       $section_id Section post ID.
 * @param WP_Post[] $chapters   Chapters already fetched via hsrtech_get_chapters( $book_id ), in reading order.
 * @return array{previous:WP_Post|null, next:WP_Post|null} Both null when no chapter in $chapters is assigned to this section (e.g. every chapter under it is still a draft).
 */
function hsrtech_locate_section_neighbors( $section_id, $chapters ) {
	foreach ( $chapters as $index => $chapter ) {
		if ( (int) get_post_meta( $chapter->ID, '_hsrtech_section_id', true ) === (int) $section_id ) {
			return array(
				'previous' => $index > 0 ? $chapters[ $index - 1 ] : null,
				'next'     => $chapter,
			);
		}
	}

	return array(
		'previous' => null,
		'next'     => null,
	);
}

/**
 * Fetch chapters assigned to a book regardless of status, for admin screens.
 *
 * Used by the Book Details meta box so authors can see draft and pending
 * chapters, not only published ones, while building out a book.
 *
 * @param int $book_id Book post ID.
 * @return WP_Post[] Chapters ordered by `_hsrtech_order`.
 */
function hsrtech_get_all_chapters_for_admin( $book_id ) {
	return get_posts(
		array(
			'post_type'      => HSRTECH_CHAPTER_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'meta_key'       => '_hsrtech_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for editor-defined chapter order.
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'date'           => 'ASC',
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Chapter-to-book relationship is stored as post meta.
				array(
					'key'     => '_hsrtech_book_id',
					'value'   => absint( $book_id ),
					'compare' => '=',
				),
			),
		)
	);
}

/**
 * Suggest the next chapter order number for a book.
 *
 * Used to prefill new chapters with a sensible order value so authors do not
 * have to look up the highest existing chapter number by hand.
 *
 * @param int $book_id Book post ID.
 * @return int One past the highest existing `_hsrtech_order` value, or 1 if the book has no chapters yet.
 */
function hsrtech_get_next_chapter_order( $book_id ) {
	$chapters = hsrtech_get_all_chapters_for_admin( $book_id );
	$highest  = 0;

	foreach ( $chapters as $chapter ) {
		$highest = max( $highest, absint( get_post_meta( $chapter->ID, '_hsrtech_order', true ) ) );
	}

	return $highest + 1;
}

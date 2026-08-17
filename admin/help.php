<?php
/**
 * Contextual Help tabs for the Book and Chapter admin screens.
 *
 * Uses WordPress's built-in "Help" panel (the tab in the top-right corner
 * of an admin screen) rather than a separate documentation page, so usage
 * instructions are available exactly where an author needs them and never
 * go out of sync with a README a reader has to leave wp-admin to find.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'current_screen', 'hsrtech_add_help_tabs' );

/**
 * Add Help tabs to the Book and Chapter list and edit screens.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function hsrtech_add_help_tabs( $screen ) {
	if ( ! $screen instanceof WP_Screen ) {
		return;
	}

	if ( HSRTECH_BOOK_POST_TYPE === $screen->post_type ) {
		hsrtech_add_book_help_tabs( $screen );
	} elseif ( HSRTECH_CHAPTER_POST_TYPE === $screen->post_type ) {
		hsrtech_add_chapter_help_tabs( $screen );
	} else {
		return;
	}

	hsrtech_set_help_sidebar( $screen );
}

/**
 * Help tabs for the Book list and edit screens.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function hsrtech_add_book_help_tabs( $screen ) {
	$screen->add_help_tab(
		array(
			'id'      => 'hsrtech-book-overview',
			'title'   => __( 'Overview', 'chapterwright' ),
			'content' =>
				'<p>' . esc_html__( 'A Book is the landing page and table of contents for one publication. It brings together a title, an optional cover image, a subtitle, an introduction, and every Chapter assigned to it.', 'chapterwright' ) . '</p>' .
				'<p>' . esc_html__( 'Most day-to-day work — adding chapters, grouping them into sections, and reordering — is faster from the Chapterwright admin page (Books & Chapters) than from this screen. Use this screen for the book\'s actual content: title, introduction, cover image, and excerpt.', 'chapterwright' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'hsrtech-book-details',
			'title'   => __( 'Book Details', 'chapterwright' ),
			'content' =>
				'<p>' . esc_html__( 'The Book Details panel, in the sidebar of the Book editor, controls:', 'chapterwright' ) . '</p>' .
				'<ul>' .
				'<li>' . esc_html__( 'Subtitle — a short line shown under the book title on its landing page.', 'chapterwright' ) . '</li>' .
				'<li>' . esc_html__( 'Accent color — colors links, hover states, blockquote/callout borders, and the reading-progress bar on this book\'s pages.', 'chapterwright' ) . '</li>' .
				'</ul>' .
				'<p>' . esc_html__( 'These same fields are also editable from the Chapterwright admin page, alongside this book\'s sections and chapters.', 'chapterwright' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'hsrtech-book-library',
			'title'   => __( 'Displaying Your Library', 'chapterwright' ),
			'content' =>
				'<p>' . esc_html__( 'Every published book automatically appears on the library archive at /books/. To show the same library inside an existing page or post instead, add a Shortcode block containing:', 'chapterwright' ) . '</p>' .
				'<p><code>[hsrtech_books]</code></p>' .
				'<p>' . esc_html__( 'Add a limit="6" attribute to control how many books are shown (1-100; the default is 12).', 'chapterwright' ) . '</p>',
		)
	);
}

/**
 * Help tabs for the Chapter list and edit screens.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function hsrtech_add_chapter_help_tabs( $screen ) {
	$screen->add_help_tab(
		array(
			'id'      => 'hsrtech-chapter-overview',
			'title'   => __( 'Overview', 'chapterwright' ),
			'content' =>
				'<p>' . esc_html__( 'A Chapter is one page of reading content that belongs to a Book. Write the chapter using normal blocks — headings, images, lists, tables, quotes, and code are all supported — the same as any other WordPress post.', 'chapterwright' ) . '</p>' .
				'<p>' . esc_html__( 'A chapter is created already assigned to a Book from the Chapterwright admin page, and must be published before it appears in that book\'s table of contents or reading navigation.', 'chapterwright' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'hsrtech-chapter-organizing',
			'title'   => __( 'Organizing Chapters', 'chapterwright' ),
			'content' =>
				'<p>' . esc_html__( 'The Chapter Details panel shows how this chapter currently fits into its book — which Book, which Section (if any), and its reading order — for reference, not as editable fields:', 'chapterwright' ) . '</p>' .
				'<ul>' .
				'<li>' . esc_html__( 'Book — which book this chapter belongs to.', 'chapterwright' ) . '</li>' .
				'<li>' . esc_html__( 'Section — an optional group, such as "Getting Started" or "Part II". Chapters assigned to the same section are grouped together, with the section\'s own text, in the table of contents; chapters left unassigned appear under a default "Chapters" heading.', 'chapterwright' ) . '</li>' .
				'<li>' . esc_html__( 'Order — controls reading order, lowest to highest.', 'chapterwright' ) . '</li>' .
				'</ul>' .
				'<p>' . esc_html__( 'Creating chapters, assigning them to a book and section, and reordering them are all done from the Chapterwright admin page (Books & Chapters), not from this screen — open a book there to see every chapter and section together in one place. The Chapter Details panel links straight there.', 'chapterwright' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'hsrtech-chapter-code',
			'title'   => __( 'Code Snippets', 'chapterwright' ),
			'content' =>
				'<p>' . esc_html__( 'For chapters that include code examples, search the block inserter for "Code Snippet" instead of using the core Code block. It adds a language label, an optional caption (e.g. a filename), and a Copy button for readers, styled to match the chapter reader.', 'chapterwright' ) . '</p>',
		)
	);
}

/**
 * Shared "For more information" sidebar shown alongside the help tabs.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function hsrtech_set_help_sidebar( $screen ) {
	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'For more information', 'chapterwright' ) . '</strong></p>' .
		'<p><a href="' . esc_url( admin_url( 'admin.php?page=chapterwright-settings' ) ) . '">' . esc_html__( 'Chapterwright Settings', 'chapterwright' ) . '</a></p>' .
		'<p><a href="' . esc_url( admin_url( 'admin.php?page=chapterwright' ) ) . '">' . esc_html__( 'Chapterwright: Books & Chapters', 'chapterwright' ) . '</a></p>' .
		'<p><a href="' . esc_url( 'https://github.com/alokjain-lucky/Chapterwright' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Plugin documentation on GitHub', 'chapterwright' ) . '</a></p>'
	);
}

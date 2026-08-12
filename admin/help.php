<?php
/**
 * Contextual Help tabs for the Book and Chapter admin screens.
 *
 * Uses WordPress's built-in "Help" panel (the tab in the top-right corner
 * of an admin screen) rather than a separate documentation page, so usage
 * instructions are available exactly where an author needs them and never
 * go out of sync with a README a reader has to leave wp-admin to find.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'current_screen', 'mab_add_help_tabs' );

/**
 * Add Help tabs to the Book and Chapter list and edit screens.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function mab_add_help_tabs( $screen ) {
	if ( ! $screen instanceof WP_Screen ) {
		return;
	}

	if ( MAB_BOOK_POST_TYPE === $screen->post_type ) {
		mab_add_book_help_tabs( $screen );
	} elseif ( MAB_CHAPTER_POST_TYPE === $screen->post_type ) {
		mab_add_chapter_help_tabs( $screen );
	} else {
		return;
	}

	mab_set_help_sidebar( $screen );
}

/**
 * Help tabs for the Book list and edit screens.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function mab_add_book_help_tabs( $screen ) {
	$screen->add_help_tab(
		array(
			'id'      => 'mab-book-overview',
			'title'   => __( 'Overview', 'make-a-book' ),
			'content' =>
				'<p>' . esc_html__( 'A Book is the landing page and table of contents for one publication. It brings together a title, an optional cover image, a subtitle, an introduction, and every Chapter assigned to it.', 'make-a-book' ) . '</p>' .
				'<p>' . esc_html__( 'Most day-to-day work — adding chapters, grouping them into sections, and reordering — is faster from the Make a Book admin page (Books & Chapters) than from this screen. Use this screen for the book\'s actual content: title, introduction, cover image, and excerpt.', 'make-a-book' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mab-book-details',
			'title'   => __( 'Book Details', 'make-a-book' ),
			'content' =>
				'<p>' . esc_html__( 'The Book Details panel, in the sidebar of the Book editor, controls:', 'make-a-book' ) . '</p>' .
				'<ul>' .
				'<li>' . esc_html__( 'Subtitle — a short line shown under the book title on its landing page.', 'make-a-book' ) . '</li>' .
				'<li>' . esc_html__( 'Accent color — used for links, the "Start reading" button, and other highlights on this book\'s pages.', 'make-a-book' ) . '</li>' .
				'</ul>' .
				'<p>' . esc_html__( 'These same fields are also editable from the Make a Book admin page, alongside this book\'s sections and chapters.', 'make-a-book' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mab-book-library',
			'title'   => __( 'Displaying Your Library', 'make-a-book' ),
			'content' =>
				'<p>' . esc_html__( 'Every published book automatically appears on the library archive at /books/. To show the same library inside an existing page or post instead, add a Shortcode block containing:', 'make-a-book' ) . '</p>' .
				'<p><code>[make_a_book]</code></p>' .
				'<p>' . esc_html__( 'Add a limit="6" attribute to control how many books are shown (1-100; the default is 12).', 'make-a-book' ) . '</p>',
		)
	);
}

/**
 * Help tabs for the Chapter list and edit screens.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function mab_add_chapter_help_tabs( $screen ) {
	$screen->add_help_tab(
		array(
			'id'      => 'mab-chapter-overview',
			'title'   => __( 'Overview', 'make-a-book' ),
			'content' =>
				'<p>' . esc_html__( 'A Chapter is one page of reading content that belongs to a Book. Write the chapter using normal blocks — headings, images, lists, tables, quotes, and code are all supported — the same as any other WordPress post.', 'make-a-book' ) . '</p>' .
				'<p>' . esc_html__( 'A chapter must be published and assigned to a Book (in the Chapter Details panel, or from the Make a Book admin page) before it appears in that book\'s table of contents or reading navigation.', 'make-a-book' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mab-chapter-organizing',
			'title'   => __( 'Organizing Chapters', 'make-a-book' ),
			'content' =>
				'<p>' . esc_html__( 'The Chapter Details panel controls how a chapter fits into its book:', 'make-a-book' ) . '</p>' .
				'<ul>' .
				'<li>' . esc_html__( 'Book — which book this chapter belongs to. Required.', 'make-a-book' ) . '</li>' .
				'<li>' . esc_html__( 'Section — an optional group, such as "Getting Started" or "Part II". Chapters assigned to the same section are grouped together, with the section\'s own text, in the table of contents; chapters left unassigned appear under a default "Chapters" heading.', 'make-a-book' ) . '</li>' .
				'<li>' . esc_html__( 'Chapter number / order — controls reading order, lowest to highest.', 'make-a-book' ) . '</li>' .
				'</ul>' .
				'<p>' . esc_html__( 'Sections themselves — creating them, writing their description text, and reordering them — are managed from the Make a Book admin page (Books & Chapters), not from this screen. That page is also the fastest way to add a new chapter to a book and reorder existing ones: open a book there to see every chapter and section together in one place.', 'make-a-book' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mab-chapter-code',
			'title'   => __( 'Code Snippets', 'make-a-book' ),
			'content' =>
				'<p>' . esc_html__( 'For chapters that include code examples, search the block inserter for "Code Snippet" instead of using the core Code block. It adds a language label, an optional caption (e.g. a filename), and a Copy button for readers, styled to match the chapter reader.', 'make-a-book' ) . '</p>',
		)
	);
}

/**
 * Shared "For more information" sidebar shown alongside the help tabs.
 *
 * @param WP_Screen $screen Current admin screen.
 */
function mab_set_help_sidebar( $screen ) {
	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'For more information', 'make-a-book' ) . '</strong></p>' .
		'<p><a href="' . esc_url( admin_url( 'admin.php?page=make-a-book-settings' ) ) . '">' . esc_html__( 'Make a Book Settings', 'make-a-book' ) . '</a></p>' .
		'<p><a href="' . esc_url( admin_url( 'admin.php?page=make-a-book' ) ) . '">' . esc_html__( 'Make a Book: Books & Chapters', 'make-a-book' ) . '</a></p>' .
		'<p><a href="' . esc_url( 'https://github.com/alokjain-lucky/Make-a-Book' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Plugin documentation on GitHub', 'make-a-book' ) . '</a></p>'
	);
}

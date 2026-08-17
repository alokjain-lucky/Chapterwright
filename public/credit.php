<?php
/**
 * "This book is created with Chapterwright" footer credit.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the credit line, if enabled in Settings.
 *
 * Called directly from templates/archive-hsrtech_book.php,
 * templates/single-hsrtech_book.php, and templates/single-hsrtech_chapter.php,
 * just before their closing </main>, so it sits inside .hsrtech-page and
 * inherits the same color variables and font as the surrounding page.
 *
 * @param string $context 'book' (default — used on book and chapter pages,
 *                         where "book" correctly refers to the one book
 *                         being viewed) or 'archive' (the /books/ library
 *                         page, which lists many books, so the wording
 *                         doesn't reference a single "book").
 */
function hsrtech_render_credit( $context = 'book' ) {
	if ( ! hsrtech_show_credit() ) {
		return;
	}

	$repo_url = 'https://github.com/alokjain-lucky/Chapterwright';
	$label    = 'archive' === $context
		/* translators: 1: opening <a> tag linking to the plugin's repository, 2: closing </a> tag. */
		? __( 'This library is powered by %1$sChapterwright%2$s', 'chapterwright' )
		/* translators: 1: opening <a> tag linking to the plugin's repository, 2: closing </a> tag. */
		: __( 'This book is created with %1$sChapterwright%2$s', 'chapterwright' );
	?>
	<p class="hsrtech-credit">
		<?php
		printf(
			esc_html( $label ),
			'<a href="' . esc_url( $repo_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>
	<?php
}

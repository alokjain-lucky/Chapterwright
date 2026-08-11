<?php
/**
 * "This book is created with Make a Book" footer credit.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the credit line, if enabled in Settings.
 *
 * Called directly from templates/archive-mab_book.php,
 * templates/single-mab_book.php, and templates/single-mab_chapter.php,
 * just before their closing </main>, so it sits inside .mab-page and
 * inherits the same color variables and font as the surrounding page.
 *
 * @param string $context 'book' (default — used on book and chapter pages,
 *                         where "book" correctly refers to the one book
 *                         being viewed) or 'archive' (the /books/ library
 *                         page, which lists many books, so the wording
 *                         doesn't reference a single "book").
 */
function mab_render_credit( $context = 'book' ) {
	if ( ! mab_show_credit() ) {
		return;
	}

	$repo_url = 'https://github.com/alokjain-lucky/Make-a-Book';
	$label    = 'archive' === $context
		/* translators: 1: opening <a> tag linking to the plugin's repository, 2: closing </a> tag. */
		? __( 'This library is powered by %1$sMake a Book%2$s', 'make-a-book' )
		/* translators: 1: opening <a> tag linking to the plugin's repository, 2: closing </a> tag. */
		: __( 'This book is created with %1$sMake a Book%2$s', 'make-a-book' );
	?>
	<p class="mab-credit">
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

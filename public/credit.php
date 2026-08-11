<?php
/**
 * "Created with Make a Book" footer credit.
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
 */
function mab_render_credit() {
	if ( ! mab_show_credit() ) {
		return;
	}

	$repo_url = 'https://github.com/alokjain-lucky/Make-a-Book';
	?>
	<p class="mab-credit">
		<?php
		printf(
			/* translators: 1: opening <a> tag linking to the plugin's repository, 2: closing </a> tag. */
			esc_html__( 'Created with %1$sMake a Book%2$s', 'make-a-book' ),
			'<a href="' . esc_url( $repo_url ) . '" target="_blank" rel="noopener noreferrer">',
			'</a>'
		);
		?>
	</p>
	<?php
}

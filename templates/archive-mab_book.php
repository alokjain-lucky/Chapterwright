<?php
/**
 * Book library archive.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require MAKE_A_BOOK_PATH . 'templates/partials/document-start.php';
global $wp_query;
$query = $wp_query;
?>
<main class="mab-page mab-archive">
	<header class="mab-archive__header">
		<p class="mab-eyebrow"><?php esc_html_e( 'The library', 'make-a-book' ); ?></p>
		<h1><?php esc_html_e( 'Books worth opening', 'make-a-book' ); ?></h1>
		<p><?php esc_html_e( 'Read one chapter at a time, right here on the web.', 'make-a-book' ); ?></p>
	</header>
	<?php include MAKE_A_BOOK_PATH . 'templates/book-grid.php'; ?>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

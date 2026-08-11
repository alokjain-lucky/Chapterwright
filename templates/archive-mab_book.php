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

$archive_eyebrow    = mab_get_text( 'archive_eyebrow' );
$archive_heading    = mab_get_text( 'archive_heading' );
$archive_subheading = mab_get_text( 'archive_subheading' );
$has_header_text    = $archive_eyebrow || $archive_heading || $archive_subheading;
?>
<main class="mab-page mab-archive">
	<?php if ( $has_header_text ) : ?>
		<header class="mab-archive__header">
			<?php if ( $archive_eyebrow ) : ?><p class="mab-eyebrow"><?php echo esc_html( $archive_eyebrow ); ?></p><?php endif; ?>
			<?php if ( $archive_heading ) : ?><h1><?php echo esc_html( $archive_heading ); ?></h1><?php endif; ?>
			<?php if ( $archive_subheading ) : ?><p><?php echo esc_html( $archive_subheading ); ?></p><?php endif; ?>
		</header>
	<?php endif; ?>
	<?php include MAKE_A_BOOK_PATH . 'templates/book-grid.php'; ?>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

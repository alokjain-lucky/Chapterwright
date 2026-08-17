<?php
/**
 * Book library archive.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require HSRTECH_PATH . 'templates/partials/document-start.php';
global $wp_query;
$hsrtech_query = $wp_query;

$hsrtech_archive_eyebrow    = hsrtech_get_text( 'archive_eyebrow' );
$hsrtech_archive_heading    = hsrtech_get_text( 'archive_heading' );
$hsrtech_archive_subheading = hsrtech_get_text( 'archive_subheading' );
$hsrtech_has_header_text    = $hsrtech_archive_eyebrow || $hsrtech_archive_heading || $hsrtech_archive_subheading;
?>
<a class="hsrtech-skip-link" href="#hsrtech-archive-content"><?php esc_html_e( 'Skip to book library', 'chapterwright' ); ?></a>
<main id="hsrtech-archive-content" class="hsrtech-page hsrtech-archive" tabindex="-1">
	<?php if ( $hsrtech_has_header_text ) : ?>
		<header class="hsrtech-archive__header">
			<?php if ( $hsrtech_archive_eyebrow ) { ?>
				<p class="hsrtech-eyebrow"><?php echo esc_html( $hsrtech_archive_eyebrow ); ?></p>
			<?php } ?>
			<?php if ( $hsrtech_archive_heading ) { ?>
				<h1><?php echo esc_html( $hsrtech_archive_heading ); ?></h1>
			<?php } ?>
			<?php if ( $hsrtech_archive_subheading ) { ?>
				<p><?php echo esc_html( $hsrtech_archive_subheading ); ?></p>
			<?php } ?>
		</header>
	<?php endif; ?>
	<?php if ( ! $hsrtech_archive_heading ) : ?>
		<?php
		/*
		 * Clearing the "Heading" field in Settings is documented (see
		 * README.md's Settings section) as the intended way to drop that
		 * VISIBLE line — but that leaves the page with no <h1> at all,
		 * which is a real gap for anyone navigating by heading (screen
		 * readers, and some browser extensions/keyboard users rely on
		 * exactly one clear top-level heading per page). A visually-hidden
		 * heading preserves the "no visible line" outcome the setting
		 * promises while still giving assistive tech something to land on.
		 */
		?>
		<h1 class="hsrtech-sr-only"><?php echo esc_html( post_type_archive_title( '', false ) ); ?></h1>
	<?php endif; ?>
	<?php require HSRTECH_PATH . 'templates/book-grid.php'; ?>
	<?php hsrtech_render_credit( 'archive' ); ?>
</main>
<?php require HSRTECH_PATH . 'templates/partials/document-end.php'; ?>

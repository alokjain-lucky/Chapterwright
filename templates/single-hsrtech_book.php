<?php
/**
 * Single book landing page.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require HSRTECH_PATH . 'templates/partials/document-start.php';
the_post();

$hsrtech_book_id     = get_the_ID();
$hsrtech_chapters    = hsrtech_get_chapters( $hsrtech_book_id );
$hsrtech_subtitle    = get_post_meta( $hsrtech_book_id, '_hsrtech_subtitle', true );
$hsrtech_accent      = get_post_meta( $hsrtech_book_id, '_hsrtech_accent', true );
$hsrtech_coming_soon = (bool) get_post_meta( $hsrtech_book_id, '_hsrtech_coming_soon', true );

// The TOC shows draft chapters too (unlinked, faded — see
// templates/partials/toc-list.php) when the site owner turns that on in
// Settings; $hsrtech_chapters above stays published-only since it also drives the
// "Start reading" link just below, which must never point at a draft.
$hsrtech_toc_chapters = hsrtech_show_draft_chapters() ? hsrtech_get_chapters( $hsrtech_book_id, array( 'publish', 'draft' ) ) : $hsrtech_chapters;

// See hsrtech_build_toc_sections(), includes/queries.php, for what this
// actually builds and why it's shared with the chapter-page TOC drawer.
$hsrtech_sections                     = hsrtech_build_toc_sections( $hsrtech_book_id, $hsrtech_toc_chapters );
$hsrtech_toc_heading                  = hsrtech_get_text( 'toc_heading' );
$hsrtech_show_toc_excerpt             = hsrtech_show_toc_excerpt();
$hsrtech_show_toc_section_description = hsrtech_show_toc_section_description();
$hsrtech_current_chapter_id           = 0; // Nothing is "current" on the book's own page — see templates/partials/toc-list.php.

// "Start reading" leads into the book's actual reading order: the first
// chapter's own section, when that section has its own introduction page
// worth reading first, otherwise straight to the first chapter itself —
// same reasoning as hsrtech_locate_section_neighbors() (includes/queries.php),
// just at the very start of the book rather than mid-book.
$hsrtech_start_reading_id = $hsrtech_chapters ? $hsrtech_chapters[0]->ID : 0;
if ( $hsrtech_start_reading_id ) {
	$hsrtech_first_section_id = absint( get_post_meta( $hsrtech_start_reading_id, '_hsrtech_section_id', true ) );
	if ( $hsrtech_first_section_id ) {
		$hsrtech_first_section = hsrtech_get_section( $hsrtech_first_section_id );
		if ( $hsrtech_first_section && $hsrtech_first_section['has_content'] ) {
			$hsrtech_start_reading_id = $hsrtech_first_section_id;
		}
	}
}
?>
<a class="hsrtech-skip-link" href="#hsrtech-main-content"><?php esc_html_e( 'Skip to book content', 'chapterwright' ); ?></a>
<main id="hsrtech-main-content" class="hsrtech-page hsrtech-book" style="--hsrtech-accent:<?php echo esc_attr( $hsrtech_accent ? $hsrtech_accent : '#f45d48' ); ?>" tabindex="-1">
	<div class="hsrtech-display-controls">
		<a class="hsrtech-back-link" href="<?php echo esc_url( get_post_type_archive_link( HSRTECH_BOOK_POST_TYPE ) ); ?>"><span aria-hidden="true">←</span> <?php esc_html_e( 'Back to library', 'chapterwright' ); ?></a>
		<?php if ( hsrtech_show_mode_toggle() ) : ?>
			<button class="hsrtech-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-hsrtech-mode-label><?php esc_html_e( 'Color mode', 'chapterwright' ); ?></span></button>
		<?php endif; ?>
	</div>
	<section class="hsrtech-book-hero">
		<div class="hsrtech-book-hero__copy">
			<p class="hsrtech-eyebrow"><?php esc_html_e( 'A book by', 'chapterwright' ); ?> <?php the_author(); ?></p>
			<?php if ( $hsrtech_coming_soon ) : ?>
				<p class="hsrtech-badge hsrtech-badge--coming-soon"><?php esc_html_e( 'Coming soon', 'chapterwright' ); ?></p>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<?php if ( $hsrtech_subtitle ) : ?>
				<p class="hsrtech-book-hero__subtitle"><?php echo esc_html( $hsrtech_subtitle ); ?></p>
			<?php endif; ?>
			<div class="hsrtech-book-hero__description"><?php the_excerpt(); ?></div>
			<?php // Coming-soon books never show "Start reading", even if a draft-preview chapter or two already exists — the flag means "not open yet," not "no chapters". ?>
			<?php if ( $hsrtech_chapters && ! $hsrtech_coming_soon ) : ?>
				<div class="hsrtech-book-hero__actions">
					<a class="hsrtech-button hsrtech-button--outline" href="#hsrtech-toc"><?php esc_html_e( 'Table of contents', 'chapterwright' ); ?> <span aria-hidden="true">↓</span></a>
					<a class="hsrtech-button" href="<?php echo esc_url( get_permalink( $hsrtech_start_reading_id ) ); ?>"><?php esc_html_e( 'Start reading', 'chapterwright' ); ?> <span aria-hidden="true">→</span></a>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="hsrtech-book-hero__cover"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
	</section>

	<?php if ( trim( get_the_content() ) ) : ?>
		<section class="hsrtech-book-intro"><?php the_content(); ?></section>
	<?php endif; ?>

	<section class="hsrtech-toc" id="hsrtech-toc" aria-labelledby="hsrtech-toc-eyebrow<?php echo $hsrtech_toc_heading ? ' hsrtech-toc-title' : ''; ?>">
		<div class="hsrtech-toc__heading">
			<p class="hsrtech-eyebrow" id="hsrtech-toc-eyebrow"><?php esc_html_e( 'Table of contents', 'chapterwright' ); ?></p>
			<?php if ( $hsrtech_toc_heading ) { ?>
				<h2 id="hsrtech-toc-title"><?php echo esc_html( $hsrtech_toc_heading ); ?></h2>
			<?php } ?>
		</div>
		<?php require HSRTECH_PATH . 'templates/partials/toc-list.php'; ?>
	</section>
	<?php hsrtech_render_credit(); ?>
</main>
<?php require HSRTECH_PATH . 'templates/partials/document-end.php'; ?>

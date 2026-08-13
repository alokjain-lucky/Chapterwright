<?php
/**
 * Single book landing page.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require MAKE_A_BOOK_PATH . 'templates/partials/document-start.php';
the_post();

$book_id     = get_the_ID();
$chapters    = mab_get_chapters( $book_id );
$subtitle    = get_post_meta( $book_id, '_mab_subtitle', true );
$accent      = get_post_meta( $book_id, '_mab_accent', true );
$coming_soon = (bool) get_post_meta( $book_id, '_mab_coming_soon', true );

// The TOC shows draft chapters too (unlinked, faded — see
// templates/partials/toc-list.php) when the site owner turns that on in
// Settings; $chapters above stays published-only since it also drives the
// "Start reading" link just below, which must never point at a draft.
$toc_chapters = mab_show_draft_chapters() ? mab_get_chapters( $book_id, array( 'publish', 'draft' ) ) : $chapters;

// See mab_build_toc_sections(), includes/queries.php, for what this
// actually builds and why it's shared with the chapter-page TOC drawer.
$sections            = mab_build_toc_sections( $book_id, $toc_chapters );
$toc_heading         = mab_get_text( 'toc_heading' );
$show_toc_excerpt    = mab_show_toc_excerpt();
$current_chapter_id  = 0; // Nothing is "current" on the book's own page — see templates/partials/toc-list.php.
?>
<a class="mab-skip-link" href="#mab-main-content"><?php esc_html_e( 'Skip to book content', 'make-a-book' ); ?></a>
<main id="mab-main-content" class="mab-page mab-book" style="--mab-accent:<?php echo esc_attr( $accent ? $accent : '#f45d48' ); ?>" tabindex="-1">
	<div class="mab-display-controls">
		<a class="mab-back-link" href="<?php echo esc_url( get_post_type_archive_link( MAB_BOOK_POST_TYPE ) ); ?>"><span aria-hidden="true">←</span> <?php esc_html_e( 'Back to library', 'make-a-book' ); ?></a>
		<?php if ( mab_show_mode_toggle() ) : ?>
			<button class="mab-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-mab-mode-label><?php esc_html_e( 'Color mode', 'make-a-book' ); ?></span></button>
		<?php endif; ?>
	</div>
	<section class="mab-book-hero">
		<div class="mab-book-hero__copy">
			<p class="mab-eyebrow"><?php esc_html_e( 'A book by', 'make-a-book' ); ?> <?php the_author(); ?></p>
			<?php if ( $coming_soon ) : ?>
				<p class="mab-badge mab-badge--coming-soon"><?php esc_html_e( 'Coming soon', 'make-a-book' ); ?></p>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<?php if ( $subtitle ) : ?>
				<p class="mab-book-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<div class="mab-book-hero__description"><?php the_excerpt(); ?></div>
			<?php // Coming-soon books never show "Start reading", even if a draft-preview chapter or two already exists — the flag means "not open yet," not "no chapters." ?>
			<?php if ( $chapters && ! $coming_soon ) : ?>
				<div class="mab-book-hero__actions">
					<a class="mab-button mab-button--outline" href="#mab-toc"><?php esc_html_e( 'Table of contents', 'make-a-book' ); ?> <span aria-hidden="true">↓</span></a>
					<a class="mab-button" href="<?php echo esc_url( get_permalink( $chapters[0] ) ); ?>"><?php esc_html_e( 'Start reading', 'make-a-book' ); ?> <span aria-hidden="true">→</span></a>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mab-book-hero__cover"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
	</section>

	<?php if ( trim( get_the_content() ) ) : ?>
		<section class="mab-book-intro"><?php the_content(); ?></section>
	<?php endif; ?>

	<section class="mab-toc" id="mab-toc" aria-labelledby="mab-toc-eyebrow<?php echo $toc_heading ? ' mab-toc-title' : ''; ?>">
		<div class="mab-toc__heading">
			<p class="mab-eyebrow" id="mab-toc-eyebrow"><?php esc_html_e( 'Table of contents', 'make-a-book' ); ?></p>
			<?php if ( $toc_heading ) : ?><h2 id="mab-toc-title"><?php echo esc_html( $toc_heading ); ?></h2><?php endif; ?>
		</div>
		<?php require MAKE_A_BOOK_PATH . 'templates/partials/toc-list.php'; ?>
	</section>
	<?php mab_render_credit(); ?>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

<?php
/**
 * Standalone section introduction page.
 *
 * A Section's post is the same object that groups chapters under a heading
 * in the table of contents (see includes/sections.php) — this template is
 * just its own page, reached only when the section actually has content
 * (hsrtech_build_toc_sections(), includes/queries.php, only links to it in
 * that case). Reuses the same prose styling as a chapter's own content
 * (`.hsrtech-chapter__content`) and the parent book's accent color, so it
 * reads as part of the same reader rather than a bare, unstyled page,
 * without needing its own dedicated stylesheet.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require HSRTECH_PATH . 'templates/partials/document-start.php';
the_post();

$hsrtech_section_id = get_the_ID();
$hsrtech_book_id    = absint( get_post_meta( $hsrtech_section_id, '_hsrtech_book_id', true ) );
$hsrtech_book       = $hsrtech_book_id ? get_post( $hsrtech_book_id ) : null;
$hsrtech_accent     = $hsrtech_book ? get_post_meta( $hsrtech_book->ID, '_hsrtech_accent', true ) : '';

// Pagination here follows the same chapter-to-chapter thread as a chapter
// page's own Previous/Next (see hsrtech_locate_section_neighbors(),
// includes/queries.php) rather than only offering a way back to the table
// of contents — a section's page is a stop along the book's reading order,
// not a dead end.
$hsrtech_chapters  = $hsrtech_book_id ? hsrtech_get_chapters( $hsrtech_book_id ) : array();
$hsrtech_neighbors = hsrtech_locate_section_neighbors( $hsrtech_section_id, $hsrtech_chapters );
$hsrtech_previous  = $hsrtech_neighbors['previous'];
$hsrtech_next      = $hsrtech_neighbors['next'];

// The floating "table of contents" button and its slide-in drawer — same
// computation and markup as templates/single-hsrtech_chapter.php, so a
// reader on a section's own page has the same way to jump around the book
// as a reader on any chapter's page does. $hsrtech_current_chapter_id stays
// 0: no single chapter row is "current" on a section's own page, the same
// way none is on the book page itself (see templates/partials/toc-list.php).
$hsrtech_show_drawer_toc              = $hsrtech_book_id && hsrtech_show_toc_button();
$hsrtech_toc_chapters                 = ( $hsrtech_show_drawer_toc && hsrtech_show_draft_chapters() )
	? hsrtech_get_chapters( $hsrtech_book_id, array( 'publish', 'draft' ) )
	: $hsrtech_chapters;
$hsrtech_sections                     = $hsrtech_show_drawer_toc ? hsrtech_build_toc_sections( $hsrtech_book_id, $hsrtech_toc_chapters ) : array();
$hsrtech_show_toc_excerpt             = hsrtech_show_toc_excerpt();
$hsrtech_show_toc_section_description = hsrtech_show_toc_section_description();
$hsrtech_current_chapter_id           = 0;
?>
<a class="hsrtech-skip-link" href="#hsrtech-section-content"><?php esc_html_e( 'Skip to content', 'chapterwright' ); ?></a>
<main id="hsrtech-section-content" class="hsrtech-page hsrtech-reader" style="--hsrtech-accent:<?php echo esc_attr( $hsrtech_accent ? $hsrtech_accent : '#f45d48' ); ?>;" tabindex="-1">
	<nav class="hsrtech-reader__bar" aria-label="<?php esc_attr_e( 'Book navigation', 'chapterwright' ); ?>">
		<?php if ( $hsrtech_book ) : ?>
			<a class="hsrtech-reader__book" href="<?php echo esc_url( get_permalink( $hsrtech_book ) ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( get_the_title( $hsrtech_book ) ); ?></a>
		<?php endif; ?>
		<?php if ( hsrtech_show_mode_toggle() ) : ?>
			<button class="hsrtech-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-hsrtech-mode-label><?php esc_html_e( 'Color mode', 'chapterwright' ); ?></span></button>
		<?php endif; ?>
	</nav>
	<article class="hsrtech-chapter">
		<header class="hsrtech-chapter__header">
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) { ?>
				<p class="hsrtech-chapter__deck"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php } ?>
		</header>
		<?php if ( has_post_thumbnail() ) { ?>
			<figure class="hsrtech-chapter__image"><?php the_post_thumbnail( 'full' ); ?></figure>
		<?php } ?>
		<div class="hsrtech-chapter__content"><?php the_content(); ?></div>
	</article>
	<nav class="hsrtech-reader__next" aria-label="<?php esc_attr_e( 'Chapter pagination', 'chapterwright' ); ?>">
		<?php if ( $hsrtech_previous ) { ?>
			<a href="<?php echo esc_url( get_permalink( $hsrtech_previous ) ); ?>"><small><?php esc_html_e( 'Previous', 'chapterwright' ); ?></small><span>← <?php echo esc_html( get_the_title( $hsrtech_previous ) ); ?></span></a>
		<?php } else { ?>
			<span></span>
		<?php } ?>
		<?php if ( $hsrtech_next ) { ?>
			<a class="is-next" href="<?php echo esc_url( get_permalink( $hsrtech_next ) ); ?>"><small><?php esc_html_e( 'Next', 'chapterwright' ); ?></small><span><?php echo esc_html( get_the_title( $hsrtech_next ) ); ?> →</span></a>
		<?php } elseif ( $hsrtech_book ) { ?>
			<a class="is-next" href="<?php echo esc_url( get_permalink( $hsrtech_book ) . '#hsrtech-toc' ); ?>"><small><?php esc_html_e( 'Continue', 'chapterwright' ); ?></small><span><?php esc_html_e( 'Table of contents', 'chapterwright' ); ?> →</span></a>
		<?php } ?>
	</nav>
	<?php hsrtech_render_credit(); ?>
	<?php if ( $hsrtech_show_drawer_toc ) : ?>
		<a
			class="hsrtech-toc-jump"
			href="<?php echo esc_url( get_permalink( $hsrtech_book ) . '#hsrtech-toc-eyebrow' ); ?>"
			aria-controls="hsrtech-toc-drawer"
			aria-expanded="false"
			aria-haspopup="dialog"
			data-hsrtech-toc-trigger
			aria-label="<?php esc_attr_e( 'Table of contents', 'chapterwright' ); ?>"
			data-tooltip="<?php esc_attr_e( 'Table of contents', 'chapterwright' ); ?>"
		>
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="17" x2="14" y2="17"></line></svg>
		</a>
		<div class="hsrtech-toc-drawer-backdrop" data-hsrtech-toc-backdrop hidden></div>
		<aside class="hsrtech-toc-drawer" id="hsrtech-toc-drawer" role="dialog" aria-modal="true" aria-labelledby="hsrtech-toc-drawer-title" hidden>
			<div class="hsrtech-toc-drawer__header">
				<p class="hsrtech-eyebrow" id="hsrtech-toc-drawer-title"><?php esc_html_e( 'Table of contents', 'chapterwright' ); ?></p>
				<button type="button" class="hsrtech-toc-drawer__close" data-hsrtech-toc-close aria-label="<?php esc_attr_e( 'Close table of contents', 'chapterwright' ); ?>">
					<span aria-hidden="true">×</span>
				</button>
			</div>
			<div class="hsrtech-toc-drawer__body">
				<?php require HSRTECH_PATH . 'templates/partials/toc-list.php'; ?>
			</div>
		</aside>
	<?php endif; ?>
</main>
<?php require HSRTECH_PATH . 'templates/partials/document-end.php'; ?>

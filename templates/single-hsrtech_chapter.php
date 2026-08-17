<?php
/**
 * Distraction-free chapter reader.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require HSRTECH_PATH . 'templates/partials/document-start.php';
the_post();

$chapter_id = get_the_ID();
$book_id    = absint( get_post_meta( $chapter_id, '_hsrtech_book_id', true ) );
$chapters   = $book_id ? hsrtech_get_chapters( $book_id ) : array();
$accent     = $book_id ? get_post_meta( $book_id, '_hsrtech_accent', true ) : '';
$neighbors  = hsrtech_locate_chapter( $chapter_id, $chapters );
$current    = $neighbors['index'];
$previous   = $neighbors['previous'];
$next       = $neighbors['next'];

// For the table-of-contents drawer (below). See hsrtech_build_toc_sections(),
// includes/queries.php — the exact same grouping the book page itself uses,
// so the drawer can never show a different chapter list than the book page
// would. $current_chapter_id is read by templates/partials/toc-list.php to
// mark this chapter's own row in the list. The drawer's chapter list can
// include drafts (unlinked, faded — see toc-list.php) when the site owner
// turns that on; $chapters above stays published-only since it also drives
// prev/next navigation and the "X of Y" counter, which must never count or
// link to an unpublished chapter.
$show_drawer_toc    = $book_id && hsrtech_show_toc_button();
$toc_chapters       = ( $show_drawer_toc && hsrtech_show_draft_chapters() )
	? hsrtech_get_chapters( $book_id, array( 'publish', 'draft' ) )
	: $chapters;
$sections           = $show_drawer_toc ? hsrtech_build_toc_sections( $book_id, $toc_chapters ) : array();
$show_toc_excerpt   = hsrtech_show_toc_excerpt();
$current_chapter_id = $chapter_id;

// How far into the *book* this chapter sits, inclusive of itself (chapter 2
// of 2 is "100% through" — not this single chapter's own scroll position,
// which .hsrtech-reading-progress at the top of the page already covers
// separately). Exposed as a CSS custom property and read by the reader
// bar's bottom divider and the prev/next nav's top divider (see
// .hsrtech-reader__bar::after / .hsrtech-reader__next::before in chapterwright.css),
// which double as a book-completion indicator instead of being plain lines.
$book_progress = ( false !== $current && count( $chapters ) > 0 )
	? round( ( $current + 1 ) / count( $chapters ) * 100 )
	: 0;
?>
<a class="hsrtech-skip-link" href="#hsrtech-chapter-content"><?php esc_html_e( 'Skip to chapter content', 'chapterwright' ); ?></a>
<main id="hsrtech-chapter-content" class="hsrtech-page hsrtech-reader" style="--hsrtech-accent:<?php echo esc_attr( $accent ? $accent : '#f45d48' ); ?>;--hsrtech-progress:<?php echo esc_attr( $book_progress ); ?>%;" tabindex="-1">
	<?php
	/*
	 * Lives inside <main>, not as a sibling before it, specifically so it
	 * inherits the --hsrtech-accent custom property set on <main>'s own inline
	 * style just above — position: fixed means moving it here changes
	 * nothing about where it renders (still pinned to the very top of the
	 * viewport via inset: 0 0 auto), only what CSS variables it can see.
	 * Previously it sat before <main> in the markup, so --hsrtech-accent was
	 * undefined at its scope and .hsrtech-reading-progress span's
	 * `var(--hsrtech-accent, #f45d48)` silently fell through to the hardcoded
	 * fallback color on every single book, regardless of that book's own
	 * accent setting.
	 */
	?>
	<div class="hsrtech-reading-progress" aria-hidden="true"><span data-hsrtech-reading-progress></span></div>
	<nav class="hsrtech-reader__bar" aria-label="<?php esc_attr_e( 'Book navigation', 'chapterwright' ); ?>">
		<?php if ( $book_id ) : ?>
			<a class="hsrtech-reader__book" href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( get_the_title( $book_id ) ); ?></a>
		<?php endif; ?>
		<?php if ( false !== $current && count( $chapters ) > 0 ) : ?>
			<?php /* translators: 1: current chapter number, 2: total number of chapters. */ ?>
			<span><?php echo esc_html( sprintf( __( '%1$d of %2$d', 'chapterwright' ), $current + 1, count( $chapters ) ) ); ?></span>
		<?php endif; ?>
		<?php if ( hsrtech_show_mode_toggle() ) : ?>
			<button class="hsrtech-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-hsrtech-mode-label><?php esc_html_e( 'Color mode', 'chapterwright' ); ?></span></button>
		<?php endif; ?>
	</nav>
	<article class="hsrtech-chapter">
		<header class="hsrtech-chapter__header">
			<p class="hsrtech-eyebrow">
				<?php /* translators: %s: Chapter number. */ ?>
				<?php echo esc_html( sprintf( __( 'Chapter %s', 'chapterwright' ), get_post_meta( $chapter_id, '_hsrtech_order', true ) ) ); ?>
				<span class="hsrtech-eyebrow__meta" aria-hidden="true">·</span>
				<span class="hsrtech-eyebrow__meta">
					<?php
					$reading_minutes = hsrtech_reading_time( $chapter_id );
					echo esc_html(
						sprintf(
							/* translators: %d: estimated reading time in minutes. */
							_n( '%d min read', '%d min read', $reading_minutes, 'chapterwright' ),
							$reading_minutes
						)
					);
					?>
				</span>
			</p>
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
		<?php if ( $previous ) { ?>
			<a href="<?php echo esc_url( get_permalink( $previous ) ); ?>"><small><?php esc_html_e( 'Previous', 'chapterwright' ); ?></small><span>← <?php echo esc_html( get_the_title( $previous ) ); ?></span></a>
		<?php } else { ?>
			<span></span>
		<?php } ?>
		<?php if ( $next ) { ?>
			<a class="is-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><small><?php esc_html_e( 'Next', 'chapterwright' ); ?></small><span><?php echo esc_html( get_the_title( $next ) ); ?> →</span></a>
		<?php } elseif ( $book_id ) { ?>
			<a class="is-next" href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><small><?php esc_html_e( 'Finished', 'chapterwright' ); ?></small><span><?php esc_html_e( 'Back to contents', 'chapterwright' ); ?> →</span></a>
		<?php } ?>
	</nav>
	<?php hsrtech_render_credit(); ?>
	<?php if ( $show_drawer_toc ) : ?>
		<a
			class="hsrtech-toc-jump"
			href="<?php echo esc_url( get_permalink( $book_id ) . '#hsrtech-toc-eyebrow' ); ?>"
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

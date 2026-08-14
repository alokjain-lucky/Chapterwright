<?php
/**
 * Distraction-free chapter reader.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require MAKE_A_BOOK_PATH . 'templates/partials/document-start.php';
the_post();

$chapter_id = get_the_ID();
$book_id    = absint( get_post_meta( $chapter_id, '_mab_book_id', true ) );
$chapters   = $book_id ? mab_get_chapters( $book_id ) : array();
$accent     = $book_id ? get_post_meta( $book_id, '_mab_accent', true ) : '';
$neighbors  = mab_locate_chapter( $chapter_id, $chapters );
$current    = $neighbors['index'];
$previous   = $neighbors['previous'];
$next       = $neighbors['next'];

// For the table-of-contents drawer (below). See mab_build_toc_sections(),
// includes/queries.php — the exact same grouping the book page itself uses,
// so the drawer can never show a different chapter list than the book page
// would. $current_chapter_id is read by templates/partials/toc-list.php to
// mark this chapter's own row in the list. The drawer's chapter list can
// include drafts (unlinked, faded — see toc-list.php) when the site owner
// turns that on; $chapters above stays published-only since it also drives
// prev/next navigation and the "X of Y" counter, which must never count or
// link to an unpublished chapter.
$show_drawer_toc    = $book_id && mab_show_toc_button();
$toc_chapters       = ( $show_drawer_toc && mab_show_draft_chapters() )
	? mab_get_chapters( $book_id, array( 'publish', 'draft' ) )
	: $chapters;
$sections           = $show_drawer_toc ? mab_build_toc_sections( $book_id, $toc_chapters ) : array();
$show_toc_excerpt   = mab_show_toc_excerpt();
$current_chapter_id = $chapter_id;

// How far into the *book* this chapter sits, inclusive of itself (chapter 2
// of 2 is "100% through" — not this single chapter's own scroll position,
// which .mab-reading-progress at the top of the page already covers
// separately). Exposed as a CSS custom property and read by the reader
// bar's bottom divider and the prev/next nav's top divider (see
// .mab-reader__bar::after / .mab-reader__next::before in make-a-book.css),
// which double as a book-completion indicator instead of being plain lines.
$book_progress = ( false !== $current && count( $chapters ) > 0 )
	? round( ( $current + 1 ) / count( $chapters ) * 100 )
	: 0;
?>
<a class="mab-skip-link" href="#mab-chapter-content"><?php esc_html_e( 'Skip to chapter content', 'make-a-book' ); ?></a>
<main id="mab-chapter-content" class="mab-page mab-reader" style="--mab-accent:<?php echo esc_attr( $accent ? $accent : '#f45d48' ); ?>;--mab-progress:<?php echo esc_attr( $book_progress ); ?>%;" tabindex="-1">
	<?php
	/*
	 * Lives inside <main>, not as a sibling before it, specifically so it
	 * inherits the --mab-accent custom property set on <main>'s own inline
	 * style just above — position: fixed means moving it here changes
	 * nothing about where it renders (still pinned to the very top of the
	 * viewport via inset: 0 0 auto), only what CSS variables it can see.
	 * Previously it sat before <main> in the markup, so --mab-accent was
	 * undefined at its scope and .mab-reading-progress span's
	 * `var(--mab-accent, #f45d48)` silently fell through to the hardcoded
	 * fallback color on every single book, regardless of that book's own
	 * accent setting.
	 */
	?>
	<div class="mab-reading-progress" aria-hidden="true"><span data-mab-reading-progress></span></div>
	<nav class="mab-reader__bar" aria-label="<?php esc_attr_e( 'Book navigation', 'make-a-book' ); ?>">
		<?php if ( $book_id ) : ?>
			<a class="mab-reader__book" href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( get_the_title( $book_id ) ); ?></a>
		<?php endif; ?>
		<?php if ( false !== $current && count( $chapters ) > 0 ) : ?>
			<?php /* translators: 1: current chapter number, 2: total number of chapters. */ ?>
			<span><?php echo esc_html( sprintf( __( '%1$d of %2$d', 'make-a-book' ), $current + 1, count( $chapters ) ) ); ?></span>
		<?php endif; ?>
		<?php if ( mab_show_mode_toggle() ) : ?>
			<button class="mab-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-mab-mode-label><?php esc_html_e( 'Color mode', 'make-a-book' ); ?></span></button>
		<?php endif; ?>
	</nav>
	<article class="mab-chapter">
		<header class="mab-chapter__header">
			<p class="mab-eyebrow">
				<?php /* translators: %s: Chapter number. */ ?>
				<?php echo esc_html( sprintf( __( 'Chapter %s', 'make-a-book' ), get_post_meta( $chapter_id, '_mab_order', true ) ) ); ?>
				<span class="mab-eyebrow__meta" aria-hidden="true">·</span>
				<span class="mab-eyebrow__meta">
					<?php
					$reading_minutes = mab_reading_time( $chapter_id );
					echo esc_html(
						sprintf(
							/* translators: %d: estimated reading time in minutes. */
							_n( '%d min read', '%d min read', $reading_minutes, 'make-a-book' ),
							$reading_minutes
						)
					);
					?>
				</span>
			</p>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) { ?>
				<p class="mab-chapter__deck"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php } ?>
		</header>
		<?php if ( has_post_thumbnail() ) { ?>
			<figure class="mab-chapter__image"><?php the_post_thumbnail( 'full' ); ?></figure>
		<?php } ?>
		<div class="mab-chapter__content"><?php the_content(); ?></div>
	</article>
	<nav class="mab-reader__next" aria-label="<?php esc_attr_e( 'Chapter pagination', 'make-a-book' ); ?>">
		<?php if ( $previous ) { ?>
			<a href="<?php echo esc_url( get_permalink( $previous ) ); ?>"><small><?php esc_html_e( 'Previous', 'make-a-book' ); ?></small><span>← <?php echo esc_html( get_the_title( $previous ) ); ?></span></a>
		<?php } else { ?>
			<span></span>
		<?php } ?>
		<?php if ( $next ) { ?>
			<a class="is-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><small><?php esc_html_e( 'Next', 'make-a-book' ); ?></small><span><?php echo esc_html( get_the_title( $next ) ); ?> →</span></a>
		<?php } elseif ( $book_id ) { ?>
			<a class="is-next" href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><small><?php esc_html_e( 'Finished', 'make-a-book' ); ?></small><span><?php esc_html_e( 'Back to contents', 'make-a-book' ); ?> →</span></a>
		<?php } ?>
	</nav>
	<?php mab_render_credit(); ?>
	<?php if ( $show_drawer_toc ) : ?>
		<a
			class="mab-toc-jump"
			href="<?php echo esc_url( get_permalink( $book_id ) . '#mab-toc-eyebrow' ); ?>"
			aria-controls="mab-toc-drawer"
			aria-expanded="false"
			aria-haspopup="dialog"
			data-mab-toc-trigger
			aria-label="<?php esc_attr_e( 'Table of contents', 'make-a-book' ); ?>"
			data-tooltip="<?php esc_attr_e( 'Table of contents', 'make-a-book' ); ?>"
		>
			<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="7" x2="20" y2="7"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="17" x2="14" y2="17"></line></svg>
		</a>
		<div class="mab-toc-drawer-backdrop" data-mab-toc-backdrop hidden></div>
		<aside class="mab-toc-drawer" id="mab-toc-drawer" role="dialog" aria-modal="true" aria-labelledby="mab-toc-drawer-title" hidden>
			<div class="mab-toc-drawer__header">
				<p class="mab-eyebrow" id="mab-toc-drawer-title"><?php esc_html_e( 'Table of contents', 'make-a-book' ); ?></p>
				<button type="button" class="mab-toc-drawer__close" data-mab-toc-close aria-label="<?php esc_attr_e( 'Close table of contents', 'make-a-book' ); ?>">
					<span aria-hidden="true">×</span>
				</button>
			</div>
			<div class="mab-toc-drawer__body">
				<?php require MAKE_A_BOOK_PATH . 'templates/partials/toc-list.php'; ?>
			</div>
		</aside>
	<?php endif; ?>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

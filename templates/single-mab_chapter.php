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
$current    = false;
$previous   = null;
$next       = null;
$accent     = $book_id ? get_post_meta( $book_id, '_mab_accent', true ) : '';

foreach ( $chapters as $index => $chapter ) {
	if ( $chapter->ID === $chapter_id ) {
		$current  = $index;
		$previous = $index > 0 ? $chapters[ $index - 1 ] : null;
		$next     = isset( $chapters[ $index + 1 ] ) ? $chapters[ $index + 1 ] : null;
		break;
	}
}
?>
<a class="mab-skip-link" href="#mab-chapter-content"><?php esc_html_e( 'Skip to chapter content', 'make-a-book' ); ?></a>
<div class="mab-reading-progress" aria-hidden="true"><span data-mab-reading-progress></span></div>
<main id="mab-chapter-content" class="mab-page mab-reader" style="--mab-accent:<?php echo esc_attr( $accent ? $accent : '#f45d48' ); ?>" tabindex="-1">
	<nav class="mab-reader__bar" aria-label="<?php esc_attr_e( 'Book navigation', 'make-a-book' ); ?>">
		<?php if ( $book_id ) : ?>
			<a class="mab-reader__book" href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( get_the_title( $book_id ) ); ?></a>
		<?php endif; ?>
		<?php if ( false !== $current && count( $chapters ) > 0 ) : ?>
			<span><?php echo esc_html( sprintf( __( '%1$d of %2$d', 'make-a-book' ), $current + 1, count( $chapters ) ) ); ?></span>
		<?php endif; ?>
		<?php if ( mab_show_mode_toggle() ) : ?>
			<button class="mab-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-mab-mode-label><?php esc_html_e( 'Color mode', 'make-a-book' ); ?></span></button>
		<?php endif; ?>
	</nav>
	<article class="mab-chapter">
		<header class="mab-chapter__header">
			<p class="mab-eyebrow"><?php echo esc_html( sprintf( __( 'Chapter %s', 'make-a-book' ), get_post_meta( $chapter_id, '_mab_order', true ) ) ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p class="mab-chapter__deck"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<div class="mab-chapter__meta">
				<?php echo get_avatar( (int) get_post_field( 'post_author', $chapter_id ), 40, '', get_the_author(), array( 'class' => 'mab-chapter__avatar' ) ); ?>
				<div>
					<span><?php the_author(); ?></span>
					<small>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: estimated reading minutes, 2: publication date. */
								__( '%1$d min read · %2$s', 'make-a-book' ),
								mab_reading_time( $chapter_id ),
								get_the_date()
							)
						);
						?>
					</small>
				</div>
			</div>
		</header>
		<?php if ( has_post_thumbnail() ) : ?><figure class="mab-chapter__image"><?php the_post_thumbnail( 'full' ); ?></figure><?php endif; ?>
		<div class="mab-chapter__content"><?php the_content(); ?></div>
	</article>
	<nav class="mab-reader__next" aria-label="<?php esc_attr_e( 'Chapter pagination', 'make-a-book' ); ?>">
		<?php if ( $previous ) : ?><a href="<?php echo esc_url( get_permalink( $previous ) ); ?>"><small><?php esc_html_e( 'Previous', 'make-a-book' ); ?></small><span>← <?php echo esc_html( get_the_title( $previous ) ); ?></span></a><?php else : ?><span></span><?php endif; ?>
		<?php if ( $next ) : ?><a class="is-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><small><?php esc_html_e( 'Next', 'make-a-book' ); ?></small><span><?php echo esc_html( get_the_title( $next ) ); ?> →</span></a><?php elseif ( $book_id ) : ?><a class="is-next" href="<?php echo esc_url( get_permalink( $book_id ) ); ?>"><small><?php esc_html_e( 'Finished', 'make-a-book' ); ?></small><span><?php esc_html_e( 'Back to contents', 'make-a-book' ); ?> →</span></a><?php endif; ?>
	</nav>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

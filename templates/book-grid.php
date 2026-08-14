<?php
/**
 * Reusable book grid.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mab-library">
	<?php if ( $query->have_posts() ) : ?>
		<div class="mab-book-grid">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				?>
				<?php
				$accent      = get_post_meta( get_the_ID(), '_mab_accent', true );
				$coming_soon = (bool) get_post_meta( get_the_ID(), '_mab_coming_soon', true );
				?>
				<article class="mab-book-card<?php echo $coming_soon ? ' mab-book-card--coming-soon' : ''; ?>" style="--mab-accent:<?php echo esc_attr( $accent ? $accent : '#f45d48' ); ?>">
					<?php /* translators: %s: Book title. */ ?>
					<a class="mab-book-card__cover" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read %s', 'make-a-book' ), get_the_title() ) ); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large' ); ?>
						<?php else : ?>
							<span class="mab-book-card__cover-label"><?php the_title(); ?></span>
						<?php endif; ?>
						<?php if ( $coming_soon ) : ?>
							<span class="mab-badge mab-badge--coming-soon mab-book-card__badge"><?php esc_html_e( 'Coming soon', 'make-a-book' ); ?></span>
						<?php endif; ?>
					</a>
					<div class="mab-book-card__body">
						<p class="mab-eyebrow"><?php esc_html_e( 'An online book', 'make-a-book' ); ?></p>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php the_excerpt(); ?>
						<a class="mab-text-link" href="<?php the_permalink(); ?>">
							<?php $coming_soon ? esc_html_e( 'Learn more', 'make-a-book' ) : esc_html_e( 'Explore the book', 'make-a-book' ); ?>
							<span aria-hidden="true">→</span>
						</a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No books have been published yet.', 'make-a-book' ); ?></p>
	<?php endif; ?>
</div>

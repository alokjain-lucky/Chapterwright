<?php
/**
 * Reusable book grid.
 *
 * @package Chapterwright
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hsrtech-library">
	<?php if ( $hsrtech_query->have_posts() ) : ?>
		<div class="hsrtech-book-grid">
			<?php
			while ( $hsrtech_query->have_posts() ) :
				$hsrtech_query->the_post();
				?>
				<?php
				$hsrtech_accent      = get_post_meta( get_the_ID(), '_hsrtech_accent', true );
				$hsrtech_coming_soon = (bool) get_post_meta( get_the_ID(), '_hsrtech_coming_soon', true );
				?>
				<article class="hsrtech-book-card<?php echo $hsrtech_coming_soon ? ' hsrtech-book-card--coming-soon' : ''; ?>" style="--hsrtech-accent:<?php echo esc_attr( $hsrtech_accent ? $hsrtech_accent : '#f45d48' ); ?>">
					<?php /* translators: %s: Book title. */ ?>
					<a class="hsrtech-book-card__cover" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read %s', 'chapterwright' ), get_the_title() ) ); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'large' ); ?>
						<?php else : ?>
							<span class="hsrtech-book-card__cover-label"><?php the_title(); ?></span>
						<?php endif; ?>
						<?php if ( $hsrtech_coming_soon ) : ?>
							<span class="hsrtech-badge hsrtech-badge--coming-soon hsrtech-book-card__badge"><?php esc_html_e( 'Coming soon', 'chapterwright' ); ?></span>
						<?php endif; ?>
					</a>
					<div class="hsrtech-book-card__body">
						<p class="hsrtech-eyebrow"><?php esc_html_e( 'An online book', 'chapterwright' ); ?></p>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php the_excerpt(); ?>
						<a class="hsrtech-text-link" href="<?php the_permalink(); ?>">
							<?php $hsrtech_coming_soon ? esc_html_e( 'Learn more', 'chapterwright' ) : esc_html_e( 'Explore the book', 'chapterwright' ); ?>
							<span aria-hidden="true">→</span>
						</a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No books have been published yet.', 'chapterwright' ); ?></p>
	<?php endif; ?>
</div>

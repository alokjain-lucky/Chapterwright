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

$book_id  = get_the_ID();
$chapters = mab_get_chapters( $book_id );
$subtitle = get_post_meta( $book_id, '_mab_subtitle', true );
$accent   = get_post_meta( $book_id, '_mab_accent', true );
$sections = array();

foreach ( $chapters as $chapter ) {
	$section = get_post_meta( $chapter->ID, '_mab_section', true );
	$section = $section ? $section : __( 'Chapters', 'make-a-book' );
	$sections[ $section ][] = $chapter;
}
?>
<a class="mab-skip-link" href="#mab-main-content"><?php esc_html_e( 'Skip to book content', 'make-a-book' ); ?></a>
<main id="mab-main-content" class="mab-page mab-book" style="--mab-accent:<?php echo esc_attr( $accent ? $accent : '#f45d48' ); ?>" tabindex="-1">
	<div class="mab-display-controls">
		<button class="mab-mode-toggle" type="button" aria-live="polite"><span aria-hidden="true">◐</span> <span data-mab-mode-label><?php esc_html_e( 'Color mode', 'make-a-book' ); ?></span></button>
	</div>
	<section class="mab-book-hero">
		<div class="mab-book-hero__copy">
			<p class="mab-eyebrow"><?php esc_html_e( 'A book by', 'make-a-book' ); ?> <?php the_author(); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( $subtitle ) : ?>
				<p class="mab-book-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<div class="mab-book-hero__description"><?php the_excerpt(); ?></div>
			<?php if ( $chapters ) : ?>
				<a class="mab-button" href="<?php echo esc_url( get_permalink( $chapters[0] ) ); ?>"><?php esc_html_e( 'Start reading', 'make-a-book' ); ?> <span aria-hidden="true">→</span></a>
			<?php endif; ?>
		</div>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mab-book-hero__cover"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>
	</section>

	<?php if ( trim( get_the_content() ) ) : ?>
		<section class="mab-book-intro"><?php the_content(); ?></section>
	<?php endif; ?>

	<section class="mab-toc" aria-labelledby="mab-toc-title">
		<div class="mab-toc__heading">
			<p class="mab-eyebrow"><?php esc_html_e( 'Table of contents', 'make-a-book' ); ?></p>
			<h2 id="mab-toc-title"><?php esc_html_e( 'Read at your own pace', 'make-a-book' ); ?></h2>
		</div>
		<?php if ( $sections ) : ?>
			<?php foreach ( $sections as $section_name => $section_chapters ) : ?>
				<div class="mab-toc-section">
					<h3><?php echo esc_html( $section_name ); ?></h3>
					<ol start="<?php echo esc_attr( (int) get_post_meta( $section_chapters[0]->ID, '_mab_order', true ) ); ?>">
						<?php foreach ( $section_chapters as $chapter ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $chapter ) ); ?>">
									<span><?php echo esc_html( get_the_title( $chapter ) ); ?></span>
									<small><?php echo esc_html( get_the_excerpt( $chapter ) ); ?></small>
									<b aria-hidden="true"><?php echo esc_html( get_post_meta( $chapter->ID, '_mab_order', true ) ); ?></b>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Chapters are coming soon.', 'make-a-book' ); ?></p>
		<?php endif; ?>
	</section>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

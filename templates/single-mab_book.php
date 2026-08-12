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

// Build the table of contents as an ordered list of section groups, each
// with its own optional description text (from the mab_sections table —
// see includes/sections.php), plus a final unlabeled "Chapters" group for
// any chapter that is not assigned to a section. A section with no
// currently-visible chapters (e.g. all its chapters are still drafts) is
// left out of the rendered list entirely rather than shown empty.
$sections      = array();
$index_by_id   = array();
$section_rows  = mab_get_book_sections( $book_id );
$unassigned    = array();

foreach ( $section_rows as $row ) {
	$index_by_id[ $row['id'] ] = count( $sections );
	$sections[]                = array(
		'name'        => $row['name'],
		'description' => $row['description'],
		'chapters'    => array(),
	);
}

foreach ( $chapters as $chapter ) {
	$section_id = absint( get_post_meta( $chapter->ID, '_mab_section_id', true ) );
	if ( $section_id && isset( $index_by_id[ $section_id ] ) ) {
		$sections[ $index_by_id[ $section_id ] ]['chapters'][] = $chapter;
	} else {
		$unassigned[] = $chapter;
	}
}

if ( $unassigned ) {
	$sections[] = array(
		'name'        => __( 'Chapters', 'make-a-book' ),
		'description' => '',
		'chapters'    => $unassigned,
	);
}

$sections = array_values(
	array_filter(
		$sections,
		static function ( $section ) {
			return ! empty( $section['chapters'] );
		}
	)
);

$toc_heading = mab_get_text( 'toc_heading' );
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

	<section class="mab-toc" aria-labelledby="mab-toc-eyebrow<?php echo $toc_heading ? ' mab-toc-title' : ''; ?>">
		<div class="mab-toc__heading">
			<p class="mab-eyebrow" id="mab-toc-eyebrow"><?php esc_html_e( 'Table of contents', 'make-a-book' ); ?></p>
			<?php if ( $toc_heading ) : ?><h2 id="mab-toc-title"><?php echo esc_html( $toc_heading ); ?></h2><?php endif; ?>
		</div>
		<?php if ( $sections ) : ?>
			<?php foreach ( $sections as $section ) : ?>
				<div class="mab-toc-section">
					<div class="mab-toc-section__heading">
						<h3><?php echo esc_html( $section['name'] ); ?></h3>
						<?php if ( $section['description'] ) : ?>
							<p class="mab-toc-section__description"><?php echo esc_html( $section['description'] ); ?></p>
						<?php endif; ?>
					</div>
					<ol start="<?php echo esc_attr( (int) get_post_meta( $section['chapters'][0]->ID, '_mab_order', true ) ); ?>">
						<?php foreach ( $section['chapters'] as $chapter ) : ?>
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
	<?php mab_render_credit(); ?>
</main>
<?php require MAKE_A_BOOK_PATH . 'templates/partials/document-end.php'; ?>

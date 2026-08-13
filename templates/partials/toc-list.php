<?php
/**
 * Renders the table-of-contents section list.
 *
 * The shared innermost markup used both by the book page's own table of
 * contents (templates/single-mab_book.php) and the chapter page's
 * table-of-contents drawer (templates/single-mab_chapter.php). Neither
 * caller's version of this markup is allowed to drift from the other's —
 * see mab_build_toc_sections(), includes/queries.php, for the shared data
 * this renders.
 *
 * Does not compute anything itself; every variable it reads is expected to
 * already be set in scope by whichever template `require`s this file.
 *
 * @package Make_A_Book
 *
 * @var array<int,array>  $sections           From mab_build_toc_sections().
 * @var bool              $show_toc_excerpt   From mab_show_toc_excerpt().
 * @var int                $current_chapter_id The chapter currently being read, or 0 on the book page itself (nothing is ever "current" there). Used only to mark that one row with aria-current="page".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_chapter_id = isset( $current_chapter_id ) ? (int) $current_chapter_id : 0;
?>
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
					<?php
					// A draft chapter only ever reaches this list at all when
					// the site owner turned on "Draft chapters in the table of
					// contents" (mab_show_draft_chapters(), admin/settings.php)
					// — see the $toc_chapters variable in the two templates
					// that require this partial. It renders as a <span>, never
					// an <a>: get_permalink() on a draft either 404s or leaks a
					// private-preview URL for a logged-out visitor, neither of
					// which is a real, working link.
					$is_draft = 'publish' !== $chapter->post_status;
					?>
					<li>
						<?php if ( $is_draft ) : ?>
							<span class="mab-toc-row-link mab-toc-row-link--draft" aria-disabled="true">
						<?php else : ?>
							<a class="mab-toc-row-link" href="<?php echo esc_url( get_permalink( $chapter ) ); ?>" <?php echo $chapter->ID === $current_chapter_id ? 'aria-current="page"' : ''; ?>>
						<?php endif; ?>
							<span class="mab-toc-row">
								<span class="mab-toc-row__title"><?php echo esc_html( get_the_title( $chapter ) ); ?></span>
								<span class="mab-toc-row__leader" aria-hidden="true"></span>
								<?php
								/*
								 * aria-hidden only for the order number — it's purely
								 * decorative/redundant with the chapter's position in
								 * this <ol> (already conveyed to assistive tech via list
								 * semantics). "Draft" is the opposite: it's the ONLY
								 * signal that this row isn't a working link, so hiding
								 * it here (as this element used to do unconditionally)
								 * silently dropped that information for screen reader
								 * users, who'd hear a normal-sounding chapter title with
								 * no indication it wasn't actually available yet.
								 */
								?>
								<b<?php echo $is_draft ? '' : ' aria-hidden="true"'; ?>><?php echo $is_draft ? esc_html__( 'Draft', 'make-a-book' ) : esc_html( get_post_meta( $chapter->ID, '_mab_order', true ) ); ?></b>
							</span>
							<?php if ( $show_toc_excerpt && ! $is_draft && get_the_excerpt( $chapter ) ) : ?>
								<small><?php echo esc_html( get_the_excerpt( $chapter ) ); ?></small>
							<?php endif; ?>
						<?php echo $is_draft ? '</span>' : '</a>'; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endforeach; ?>
<?php else : ?>
	<p><?php esc_html_e( 'Chapters are coming soon.', 'make-a-book' ); ?></p>
<?php endif; ?>

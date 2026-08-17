<?php
/**
 * Renders the table-of-contents section list.
 *
 * The shared innermost markup used both by the book page's own table of
 * contents (templates/single-hsrtech_book.php) and the chapter page's
 * table-of-contents drawer (templates/single-hsrtech_chapter.php). Neither
 * caller's version of this markup is allowed to drift from the other's —
 * see hsrtech_build_toc_sections(), includes/queries.php, for the shared data
 * this renders.
 *
 * Does not compute anything itself; every variable it reads is expected to
 * already be set in scope by whichever template `require`s this file.
 *
 * @package Chapterwright
 *
 * @var array<int,array>  $hsrtech_sections           From hsrtech_build_toc_sections().
 * @var bool              $hsrtech_show_toc_excerpt   From hsrtech_show_toc_excerpt().
 * @var int                $hsrtech_current_chapter_id The chapter currently being read, or 0 on the book page itself (nothing is ever "current" there). Used only to mark that one row with aria-current="page".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hsrtech_current_chapter_id = isset( $hsrtech_current_chapter_id ) ? (int) $hsrtech_current_chapter_id : 0;
?>
<?php if ( $hsrtech_sections ) : ?>
	<?php foreach ( $hsrtech_sections as $hsrtech_section ) : ?>
		<div class="hsrtech-toc-section">
			<div class="hsrtech-toc-section__heading">
				<h3><?php echo esc_html( $hsrtech_section['name'] ); ?></h3>
				<?php if ( $hsrtech_section['description'] ) : ?>
					<p class="hsrtech-toc-section__description"><?php echo esc_html( $hsrtech_section['description'] ); ?></p>
				<?php endif; ?>
			</div>
			<ol start="<?php echo esc_attr( (int) get_post_meta( $hsrtech_section['chapters'][0]->ID, '_hsrtech_order', true ) ); ?>">
				<?php foreach ( $hsrtech_section['chapters'] as $hsrtech_chapter ) : ?>
					<?php
					// A draft chapter only ever reaches this list at all when
					// the site owner turned on "Draft chapters in the table of
					// contents" (hsrtech_show_draft_chapters(), admin/settings.php)
					// — see the $hsrtech_toc_chapters variable in the two templates
					// that require this partial. It renders as a <span>, never
					// an <a>: get_permalink() on a draft either 404s or leaks a
					// private-preview URL for a logged-out visitor, neither of
					// which is a real, working link.
					$hsrtech_is_draft = 'publish' !== $hsrtech_chapter->post_status;
					?>
					<li>
						<?php if ( $hsrtech_is_draft ) : ?>
							<span class="hsrtech-toc-row-link hsrtech-toc-row-link--draft" aria-disabled="true">
						<?php else : ?>
							<a class="hsrtech-toc-row-link" href="<?php echo esc_url( get_permalink( $hsrtech_chapter ) ); ?>" <?php echo $hsrtech_chapter->ID === $hsrtech_current_chapter_id ? 'aria-current="page"' : ''; ?>>
						<?php endif; ?>
							<span class="hsrtech-toc-row">
								<span class="hsrtech-toc-row__title"><?php echo esc_html( get_the_title( $hsrtech_chapter ) ); ?></span>
								<span class="hsrtech-toc-row__leader" aria-hidden="true"></span>
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
								<b<?php echo $hsrtech_is_draft ? '' : ' aria-hidden="true"'; ?>><?php echo $hsrtech_is_draft ? esc_html__( 'Draft', 'chapterwright' ) : esc_html( get_post_meta( $hsrtech_chapter->ID, '_hsrtech_order', true ) ); ?></b>
							</span>
							<?php if ( $hsrtech_show_toc_excerpt && ! $hsrtech_is_draft && get_the_excerpt( $hsrtech_chapter ) ) : ?>
								<small><?php echo esc_html( get_the_excerpt( $hsrtech_chapter ) ); ?></small>
							<?php endif; ?>
						<?php echo $hsrtech_is_draft ? '</span>' : '</a>'; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endforeach; ?>
<?php else : ?>
	<p><?php esc_html_e( 'Chapters are coming soon.', 'chapterwright' ); ?></p>
<?php endif; ?>

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
 * @var array<int,array>  $hsrtech_sections                       From hsrtech_build_toc_sections().
 * @var bool              $hsrtech_show_toc_excerpt               From hsrtech_show_toc_excerpt().
 * @var bool              $hsrtech_show_toc_section_description   From hsrtech_show_toc_section_description().
 * @var int                $hsrtech_current_chapter_id The chapter currently being read, or 0 on the book page itself (nothing is ever "current" there). Used only to mark that one row with aria-current="page".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hsrtech_current_chapter_id            = isset( $hsrtech_current_chapter_id ) ? (int) $hsrtech_current_chapter_id : 0;
$hsrtech_show_toc_section_description = isset( $hsrtech_show_toc_section_description ) ? $hsrtech_show_toc_section_description : true;
?>
<?php if ( $hsrtech_sections ) : ?>
	<?php foreach ( $hsrtech_sections as $hsrtech_section ) : ?>
		<div class="hsrtech-toc-section">
			<div class="hsrtech-toc-section__heading">
				<h3>
					<?php if ( ! empty( $hsrtech_section['url'] ) ) : ?>
						<a class="hsrtech-toc-section__link" href="<?php echo esc_url( $hsrtech_section['url'] ); ?>"><?php echo esc_html( $hsrtech_section['name'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $hsrtech_section['name'] ); ?>
					<?php endif; ?>
				</h3>
				<?php if ( $hsrtech_section['description'] && $hsrtech_show_toc_section_description ) : ?>
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
								<?php
								/*
								 * A separate element from the order number below, not a
								 * replacement for it — a draft chapter keeps its real
								 * place in the numbered list instead of having "Draft"
								 * printed where its number would be, so the numbering
								 * stays consistent whether or not draft chapters are
								 * visible at all (hsrtech_show_draft_chapters(),
								 * admin/settings.php). Not aria-hidden: this is the one
								 * signal that this row isn't a working link, so hiding it
								 * would silently drop that information for screen reader
								 * users, who'd otherwise hear a normal-sounding chapter
								 * title with no indication it wasn't actually available
								 * yet.
								 */
								?>
								<?php if ( $hsrtech_is_draft ) : ?>
									<span class="hsrtech-toc-row__draft-flag"><?php esc_html_e( 'Draft', 'chapterwright' ); ?></span>
								<?php endif; ?>
								<span class="hsrtech-toc-row__leader" aria-hidden="true"></span>
								<?php
								// Always the order number, never replaced by "Draft" (see
								// above), and always aria-hidden, same as a published row:
								// purely decorative/redundant with the chapter's position in
								// this <ol>, already conveyed to assistive tech via list
								// semantics.
								?>
								<b aria-hidden="true"><?php echo esc_html( get_post_meta( $hsrtech_chapter->ID, '_hsrtech_order', true ) ); ?></b>
							</span>
							<?php if ( $hsrtech_show_toc_excerpt && get_the_excerpt( $hsrtech_chapter ) ) : ?>
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

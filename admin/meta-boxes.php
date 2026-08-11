<?php
/**
 * Book and Chapter editor meta boxes: rendering, saving, and validation.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'mab_add_meta_boxes' );
add_action( 'save_post_' . MAB_BOOK_POST_TYPE, 'mab_save_book_meta' );
add_action( 'save_post_' . MAB_CHAPTER_POST_TYPE, 'mab_save_chapter_meta' );

/**
 * Register the Book and Chapter sidebar panels.
 */
function mab_add_meta_boxes() {
	add_meta_box( 'mab_book_details', __( 'Book Details', 'make-a-book' ), 'mab_render_book_meta_box', MAB_BOOK_POST_TYPE, 'side' );
	add_meta_box( 'mab_chapter_details', __( 'Chapter Details', 'make-a-book' ), 'mab_render_chapter_meta_box', MAB_CHAPTER_POST_TYPE, 'side' );
}

/**
 * Render subtitle, accent color, and a chapter summary for a Book.
 *
 * The chapter summary and "Add chapter" link let an author build out a book
 * end to end without leaving the Book screen — new chapters created from
 * here arrive with this book and the next chapter number already filled in.
 *
 * @param WP_Post $post Current Book post.
 */
function mab_render_book_meta_box( $post ) {
	$subtitle = get_post_meta( $post->ID, '_mab_subtitle', true );
	$color    = get_post_meta( $post->ID, '_mab_accent', true );
	$chapters = 'auto-draft' !== $post->post_status ? mab_get_all_chapters_for_admin( $post->ID ) : array();

	wp_nonce_field( 'mab_save_book', 'mab_book_nonce' );
	?>
	<p>
		<label for="mab_subtitle"><strong><?php esc_html_e( 'Subtitle', 'make-a-book' ); ?></strong></label>
		<textarea class="widefat" id="mab_subtitle" name="mab_subtitle" rows="3"><?php echo esc_textarea( $subtitle ); ?></textarea>
	</p>
	<p>
		<label for="mab_accent"><strong><?php esc_html_e( 'Accent color', 'make-a-book' ); ?></strong></label><br>
		<input id="mab_accent" name="mab_accent" type="color" value="<?php echo esc_attr( $color ? $color : '#f45d48' ); ?>">
	</p>
	<p class="description"><?php esc_html_e( 'Use the featured image as this book’s cover.', 'make-a-book' ); ?></p>

	<?php if ( 'auto-draft' !== $post->post_status ) : ?>
		<hr>
		<p><strong><?php esc_html_e( 'Chapters in this book', 'make-a-book' ); ?></strong></p>
		<?php if ( $chapters ) : ?>
			<ol class="mab-admin-chapter-list">
				<?php foreach ( $chapters as $chapter ) : ?>
					<li>
						<a href="<?php echo esc_url( (string) get_edit_post_link( $chapter->ID ) ); ?>">
							<?php echo esc_html( $chapter->post_title ? $chapter->post_title : __( '(no title)', 'make-a-book' ) ); ?>
						</a>
						<?php
						if ( 'publish' !== $chapter->post_status ) :
							$status = get_post_status_object( $chapter->post_status );
							?>
							<span class="mab-admin-chapter-status">(<?php echo esc_html( $status ? $status->label : $chapter->post_status ); ?>)</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No chapters yet.', 'make-a-book' ); ?></p>
		<?php endif; ?>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MAB_CHAPTER_POST_TYPE . '&mab_book_id=' . $post->ID ) ); ?>">
				<?php esc_html_e( '+ Add chapter to this book', 'make-a-book' ); ?>
			</a>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Render book relationship, section, and order fields for a Chapter.
 *
 * When a new chapter is created from a Book's "+ Add chapter" link, the
 * target book and a suggested next order number are prefilled from the
 * `mab_book_id` query argument (read-only convenience, not a state-changing
 * request), so authors do not have to reselect the book or look up the last
 * chapter number by hand. `assets/js/chapter-order.js` keeps the suggestion
 * current if the author changes the Book dropdown before saving.
 *
 * @param WP_Post $post Current Chapter post.
 */
function mab_render_chapter_meta_box( $post ) {
	$is_new         = 'auto-draft' === $post->post_status;
	$requested_book = isset( $_GET['mab_book_id'] ) ? absint( wp_unslash( $_GET['mab_book_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only prefill convenience.

	$book_id = absint( get_post_meta( $post->ID, '_mab_book_id', true ) );
	if ( $is_new && $requested_book && MAB_BOOK_POST_TYPE === get_post_type( $requested_book ) ) {
		$book_id = $requested_book;
	}

	$section = get_post_meta( $post->ID, '_mab_section', true );
	$order   = absint( get_post_meta( $post->ID, '_mab_order', true ) );
	if ( $is_new && ! $order && $book_id ) {
		$order = mab_get_next_chapter_order( $book_id );
	}

	$books = get_posts(
		array(
			'post_type'   => MAB_BOOK_POST_TYPE,
			'post_status' => array( 'publish', 'draft', 'private' ),
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);

	wp_nonce_field( 'mab_save_chapter', 'mab_chapter_nonce' );
	?>
	<p>
		<label for="mab_book_id"><strong><?php esc_html_e( 'Book', 'make-a-book' ); ?></strong></label>
		<select class="widefat" id="mab_book_id" name="mab_book_id" required>
			<option value=""><?php esc_html_e( 'Select a book', 'make-a-book' ); ?></option>
			<?php foreach ( $books as $book ) : ?>
				<option value="<?php echo esc_attr( $book->ID ); ?>" <?php selected( $book_id, $book->ID ); ?>><?php echo esc_html( $book->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! $books ) : ?>
			<span class="description"><?php esc_html_e( 'Create a Book first, then chapters can be assigned to it.', 'make-a-book' ); ?></span>
		<?php endif; ?>
	</p>
	<p>
		<label for="mab_section"><strong><?php esc_html_e( 'Section name', 'make-a-book' ); ?></strong></label>
		<input class="widefat" id="mab_section" name="mab_section" type="text" value="<?php echo esc_attr( $section ); ?>" placeholder="<?php esc_attr_e( 'e.g. Getting Started', 'make-a-book' ); ?>">
	</p>
	<p>
		<label for="mab_order"><strong><?php esc_html_e( 'Chapter number / order', 'make-a-book' ); ?></strong></label><br>
		<input class="small-text" id="mab_order" min="0" name="mab_order" type="number" value="<?php echo esc_attr( (string) $order ); ?>">
		<?php if ( $is_new ) : ?>
			<span class="description"><?php esc_html_e( 'Suggested — change if needed.', 'make-a-book' ); ?></span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Sanitize and save Book-specific metadata.
 *
 * @param int $post_id Book post ID.
 */
function mab_save_book_meta( $post_id ) {
	if ( ! mab_can_save_meta( $post_id, 'mab_book_nonce', 'mab_save_book' ) ) {
		return;
	}

	$subtitle = isset( $_POST['mab_subtitle'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mab_subtitle'] ) ) : '';
	$color    = isset( $_POST['mab_accent'] ) ? sanitize_hex_color( wp_unslash( $_POST['mab_accent'] ) ) : '';

	update_post_meta( $post_id, '_mab_subtitle', $subtitle );
	update_post_meta( $post_id, '_mab_accent', $color ? $color : '#f45d48' );
}

/**
 * Validate, sanitize, and save Chapter-specific metadata.
 *
 * @param int $post_id Chapter post ID.
 */
function mab_save_chapter_meta( $post_id ) {
	if ( ! mab_can_save_meta( $post_id, 'mab_chapter_nonce', 'mab_save_chapter' ) ) {
		return;
	}

	$book_id = isset( $_POST['mab_book_id'] ) ? absint( wp_unslash( $_POST['mab_book_id'] ) ) : 0;
	$section = isset( $_POST['mab_section'] ) ? sanitize_text_field( wp_unslash( $_POST['mab_section'] ) ) : '';
	$order   = isset( $_POST['mab_order'] ) ? absint( wp_unslash( $_POST['mab_order'] ) ) : 0;

	if ( $book_id && MAB_BOOK_POST_TYPE === get_post_type( $book_id ) ) {
		update_post_meta( $post_id, '_mab_book_id', $book_id );
	} else {
		delete_post_meta( $post_id, '_mab_book_id' );
	}

	update_post_meta( $post_id, '_mab_section', $section );
	update_post_meta( $post_id, '_mab_order', $order );
}

/**
 * Confirm a metadata request is intentional and authorized.
 *
 * @param int    $post_id Post ID being saved.
 * @param string $field   Nonce field name.
 * @param string $action  Expected nonce action.
 * @return bool Whether metadata may be saved.
 */
function mab_can_save_meta( $post_id, $field, $action ) {
	return ! wp_is_post_autosave( $post_id )
		&& ! wp_is_post_revision( $post_id )
		&& isset( $_POST[ $field ] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action )
		&& current_user_can( 'edit_post', $post_id );
}

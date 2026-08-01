<?php
/**
 * WordPress administration screens and metadata persistence.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages book and chapter editor fields.
 */
final class Make_A_Book_Admin {
	/** Register admin hooks. */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_mab_book', array( $this, 'save_book_meta' ) );
		add_action( 'save_post_mab_chapter', array( $this, 'save_chapter_meta' ) );
		add_filter( 'manage_mab_chapter_posts_columns', array( $this, 'chapter_columns' ) );
		add_action( 'manage_mab_chapter_posts_custom_column', array( $this, 'chapter_column_content' ), 10, 2 );
	}

	/** Register the Book and Chapter sidebar panels. */
	public function add_meta_boxes() {
		add_meta_box( 'mab_book_details', __( 'Book Details', 'make-a-book' ), array( $this, 'render_book_meta_box' ), Make_A_Book_Content_Types::BOOK_POST_TYPE, 'side' );
		add_meta_box( 'mab_chapter_details', __( 'Chapter Details', 'make-a-book' ), array( $this, 'render_chapter_meta_box' ), Make_A_Book_Content_Types::CHAPTER_POST_TYPE, 'side' );
	}

	/**
	 * Render subtitle and accent color fields for a Book.
	 *
	 * @param WP_Post $post Current Book post.
	 */
	public function render_book_meta_box( $post ) {
		$subtitle = get_post_meta( $post->ID, '_mab_subtitle', true );
		$color    = get_post_meta( $post->ID, '_mab_accent', true );
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
		<?php
	}

	/**
	 * Render book relationship, section, and order fields for a Chapter.
	 *
	 * @param WP_Post $post Current Chapter post.
	 */
	public function render_chapter_meta_box( $post ) {
		$book_id = absint( get_post_meta( $post->ID, '_mab_book_id', true ) );
		$section = get_post_meta( $post->ID, '_mab_section', true );
		$order   = absint( get_post_meta( $post->ID, '_mab_order', true ) );
		$books   = get_posts(
			array(
				'post_type'   => Make_A_Book_Content_Types::BOOK_POST_TYPE,
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
		</p>
		<p>
			<label for="mab_section"><strong><?php esc_html_e( 'Section name', 'make-a-book' ); ?></strong></label>
			<input class="widefat" id="mab_section" name="mab_section" type="text" value="<?php echo esc_attr( $section ); ?>">
		</p>
		<p>
			<label for="mab_order"><strong><?php esc_html_e( 'Chapter number / order', 'make-a-book' ); ?></strong></label>
			<input class="small-text" id="mab_order" min="0" name="mab_order" type="number" value="<?php echo esc_attr( $order ); ?>">
		</p>
		<?php
	}

	/**
	 * Sanitize and save Book-specific metadata.
	 *
	 * @param int $post_id Book post ID.
	 */
	public function save_book_meta( $post_id ) {
		if ( ! $this->can_save( $post_id, 'mab_book_nonce', 'mab_save_book' ) ) {
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
	public function save_chapter_meta( $post_id ) {
		if ( ! $this->can_save( $post_id, 'mab_chapter_nonce', 'mab_save_chapter' ) ) {
			return;
		}

		$book_id = isset( $_POST['mab_book_id'] ) ? absint( $_POST['mab_book_id'] ) : 0;
		$section = isset( $_POST['mab_section'] ) ? sanitize_text_field( wp_unslash( $_POST['mab_section'] ) ) : '';
		$order   = isset( $_POST['mab_order'] ) ? absint( $_POST['mab_order'] ) : 0;

		if ( $book_id && Make_A_Book_Content_Types::BOOK_POST_TYPE === get_post_type( $book_id ) ) {
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
	private function can_save( $post_id, $field, $action ) {
		return ! wp_is_post_autosave( $post_id )
			&& ! wp_is_post_revision( $post_id )
			&& isset( $_POST[ $field ] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action )
			&& current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Add the parent Book and reading order to the Chapters list table.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string> Filtered columns.
	 */
	public function chapter_columns( $columns ) {
		$columns['mab_book']  = __( 'Book', 'make-a-book' );
		$columns['mab_order'] = __( 'Order', 'make-a-book' );
		return $columns;
	}

	/**
	 * Output one custom Chapters list-table cell.
	 *
	 * @param string $column  Column identifier.
	 * @param int    $post_id Chapter post ID.
	 */
	public function chapter_column_content( $column, $post_id ) {
		if ( 'mab_book' === $column ) {
			$book_id = absint( get_post_meta( $post_id, '_mab_book_id', true ) );
			echo $book_id ? esc_html( get_the_title( $book_id ) ) : '&mdash;';
		} elseif ( 'mab_order' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_mab_order', true ) );
		}
	}
}

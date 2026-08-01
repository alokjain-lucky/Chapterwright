<?php
/**
 * Public templates, assets, and shortcodes.
 *
 * @package Make_A_Book
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controls the visitor-facing reading experience.
 */
final class Make_A_Book_Public {
	/**
	 * Estimate focused reading time from a post's visible word count.
	 *
	 * The estimate uses 220 words per minute, a comfortable rate for technical
	 * material, and always returns at least one minute.
	 *
	 * @param int $post_id Post ID.
	 * @return int Estimated whole minutes.
	 */
	public static function reading_time( $post_id ) {
		$content = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) );
		$words   = str_word_count( $content );

		return max( 1, (int) ceil( $words / 220 ) );
	}

	/** Register front-end hooks. */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'print_schema' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_shortcode( 'make_a_book', array( $this, 'library_shortcode' ) );
	}

	/**
	 * Load styles only on plugin views or pages containing the library shortcode.
	 */
	public function enqueue_assets() {
		$queried_id = get_queried_object_id();
		$content    = $queried_id ? get_post_field( 'post_content', $queried_id ) : '';
		$is_view    = is_singular( array( Make_A_Book_Content_Types::BOOK_POST_TYPE, Make_A_Book_Content_Types::CHAPTER_POST_TYPE ) )
			|| is_post_type_archive( Make_A_Book_Content_Types::BOOK_POST_TYPE );

		if ( $is_view || ( $content && has_shortcode( $content, 'make_a_book' ) ) ) {
			wp_enqueue_style( 'make-a-book', MAKE_A_BOOK_URL . 'assets/css/make-a-book.css', array(), MAKE_A_BOOK_VERSION );
			wp_enqueue_script( 'make-a-book-reader', MAKE_A_BOOK_URL . 'assets/js/make-a-book-reader.js', array(), MAKE_A_BOOK_VERSION, true );
			wp_localize_script(
				'make-a-book-reader',
				'makeABookReader',
				array(
					'modeAuto'  => __( 'Use system color mode', 'make-a-book' ),
					'modeLight' => __( 'Use light color mode', 'make-a-book' ),
					'modeDark'  => __( 'Use dark color mode', 'make-a-book' ),
				)
			);
		}
	}

	/**
	 * Print Book or Chapter JSON-LD on singular plugin views.
	 *
	 * Values are produced by WordPress APIs and encoded with `wp_json_encode()`.
	 * This keeps structured data synchronized with the visible title, excerpt,
	 * author, cover, publication date, parent book, and chapter position.
	 */
	public function print_schema() {
		if ( ! is_singular( array( Make_A_Book_Content_Types::BOOK_POST_TYPE, Make_A_Book_Content_Types::CHAPTER_POST_TYPE ) ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		$schema  = array(
			'@context'      => 'https://schema.org',
			'@type'         => Make_A_Book_Content_Types::BOOK_POST_TYPE === get_post_type( $post_id ) ? 'Book' : 'Chapter',
			'name'          => get_the_title( $post_id ),
			'url'           => get_permalink( $post_id ),
			'description'   => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
			'datePublished' => get_the_date( DATE_W3C, $post_id ),
			'dateModified'  => get_the_modified_date( DATE_W3C, $post_id ),
			'author'        => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
				'url'   => get_the_author_meta( 'url', (int) get_post_field( 'post_author', $post_id ) ),
			),
		);

		if ( has_post_thumbnail( $post_id ) ) {
			$schema['image'] = get_the_post_thumbnail_url( $post_id, 'full' );
		}

		if ( Make_A_Book_Content_Types::CHAPTER_POST_TYPE === get_post_type( $post_id ) ) {
			$book_id = absint( get_post_meta( $post_id, '_mab_book_id', true ) );
			if ( $book_id ) {
				$schema['isPartOf'] = array( '@type' => 'Book', 'name' => get_the_title( $book_id ), 'url' => get_permalink( $book_id ) );
				$schema['position'] = absint( get_post_meta( $post_id, '_mab_order', true ) );
			}
		}
		?>
		<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safely encoded for a script data block. ?></script>
		<?php
	}

	/**
	 * Select bundled templates for plugin-owned routes.
	 *
	 * Themes remain responsible for the surrounding header and footer. A theme or
	 * extension can override the final result with a later `template_include` filter.
	 *
	 * @param string $template Absolute path to the theme-selected template.
	 * @return string Absolute path to the selected template.
	 */
	public function template_include( $template ) {
		$plugin_template = '';

		if ( is_singular( Make_A_Book_Content_Types::BOOK_POST_TYPE ) ) {
			$plugin_template = MAKE_A_BOOK_PATH . 'templates/single-mab_book.php';
		} elseif ( is_singular( Make_A_Book_Content_Types::CHAPTER_POST_TYPE ) ) {
			$plugin_template = MAKE_A_BOOK_PATH . 'templates/single-mab_chapter.php';
		} elseif ( is_post_type_archive( Make_A_Book_Content_Types::BOOK_POST_TYPE ) ) {
			$plugin_template = MAKE_A_BOOK_PATH . 'templates/archive-mab_book.php';
		}

		return $plugin_template && file_exists( $plugin_template ) ? $plugin_template : $template;
	}

	/**
	 * Render a grid of published books.
	 *
	 * Usage: `[make_a_book]` or `[make_a_book limit="6"]`.
	 *
	 * @param array<string,mixed> $atts User-supplied shortcode attributes.
	 * @return string Escaped library HTML rendered by the book-grid template.
	 */
	public function library_shortcode( $atts ) {
		$atts  = shortcode_atts( array( 'limit' => 12 ), $atts, 'make_a_book' );
		$limit = max( 1, min( 100, absint( $atts['limit'] ) ) );
		$query = new WP_Query(
			array(
				'post_type'      => Make_A_Book_Content_Types::BOOK_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		ob_start();
		include MAKE_A_BOOK_PATH . 'templates/book-grid.php';
		wp_reset_postdata();

		return (string) ob_get_clean();
	}
}

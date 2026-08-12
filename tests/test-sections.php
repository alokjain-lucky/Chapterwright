<?php
/**
 * Tests for includes/sections.php — the mab_sections custom table CRUD.
 *
 * Covers the behavior that's hardest to safely change without a regression
 * catching it: menu-order sequencing, cross-book validation, unassigning
 * (not deleting) a section's chapters, and cascading a book's sections when
 * the book itself is permanently deleted.
 *
 * @package Make_A_Book
 */

/**
 * @group sections
 */
class Test_Sections extends WP_UnitTestCase {

	/**
	 * A Book post created fresh for every test.
	 *
	 * @var int
	 */
	protected $book_id;

	public function set_up() {
		parent::set_up();
		$this->book_id = self::factory()->post->create( array( 'post_type' => MAB_BOOK_POST_TYPE ) );
	}

	public function test_insert_and_get_section() {
		$section_id = mab_insert_section(
			$this->book_id,
			array(
				'name'        => 'Part One',
				'description' => 'Getting started.',
			)
		);

		$this->assertIsInt( $section_id );

		$section = mab_get_section( $section_id );
		$this->assertNotNull( $section );
		$this->assertSame( 'Part One', $section['name'] );
		$this->assertSame( 'Getting started.', $section['description'] );
		$this->assertSame( $this->book_id, (int) $section['book_id'] );
	}

	public function test_insert_requires_a_non_empty_name() {
		$result = mab_insert_section( $this->book_id, array( 'name' => '   ' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'mab_section_name_required', $result->get_error_code() );
	}

	public function test_get_section_returns_null_when_not_found() {
		$this->assertNull( mab_get_section( 999999 ) );
	}

	public function test_new_sections_append_to_the_end_of_menu_order() {
		$first  = mab_insert_section( $this->book_id, array( 'name' => 'First' ) );
		$second = mab_insert_section( $this->book_id, array( 'name' => 'Second' ) );

		$sections = mab_get_book_sections( $this->book_id );

		$this->assertSame( array( $first, $second ), wp_list_pluck( $sections, 'id' ) );
	}

	public function test_reorder_sections_persists_the_new_order() {
		$first  = mab_insert_section( $this->book_id, array( 'name' => 'First' ) );
		$second = mab_insert_section( $this->book_id, array( 'name' => 'Second' ) );

		$result = mab_reorder_sections( $this->book_id, array( $second, $first ) );

		$this->assertTrue( $result );
		$sections = mab_get_book_sections( $this->book_id );
		$this->assertSame( array( $second, $first ), wp_list_pluck( $sections, 'id' ) );
	}

	public function test_reorder_rejects_a_section_belonging_to_another_book() {
		$other_book_id = self::factory()->post->create( array( 'post_type' => MAB_BOOK_POST_TYPE ) );
		$foreign_id     = mab_insert_section( $other_book_id, array( 'name' => 'Not this book' ) );

		$result = mab_reorder_sections( $this->book_id, array( $foreign_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'mab_section_mismatch', $result->get_error_code() );
	}

	public function test_deleting_a_section_unassigns_but_does_not_delete_its_chapters() {
		$section_id = mab_insert_section( $this->book_id, array( 'name' => 'Part One' ) );
		$chapter_id = self::factory()->post->create( array( 'post_type' => MAB_CHAPTER_POST_TYPE ) );
		update_post_meta( $chapter_id, '_mab_section_id', $section_id );

		$result = mab_delete_section( $section_id );

		$this->assertTrue( $result );
		$this->assertNull( mab_get_section( $section_id ) );
		$this->assertSame( '', get_post_meta( $chapter_id, '_mab_section_id', true ) );
		$this->assertSame( 'publish', get_post_status( $chapter_id ) );
	}

	public function test_deleting_a_missing_section_returns_a_wp_error() {
		$result = mab_delete_section( 999999 );

		$this->assertWPError( $result );
		$this->assertSame( 'mab_section_not_found', $result->get_error_code() );
	}

	public function test_permanently_deleting_a_book_cascades_to_its_sections() {
		mab_insert_section( $this->book_id, array( 'name' => 'Part One' ) );
		mab_insert_section( $this->book_id, array( 'name' => 'Part Two' ) );

		wp_delete_post( $this->book_id, true );

		$this->assertSame( array(), mab_get_book_sections( $this->book_id ) );
	}

	public function test_trashing_a_book_does_not_delete_its_sections() {
		$section_id = mab_insert_section( $this->book_id, array( 'name' => 'Part One' ) );

		wp_trash_post( $this->book_id );

		$this->assertNotNull( mab_get_section( $section_id ) );
	}
}

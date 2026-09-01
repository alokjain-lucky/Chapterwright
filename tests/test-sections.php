<?php
/**
 * Tests for includes/sections.php — the hsrtech_sections custom table CRUD.
 *
 * Covers the behavior that's hardest to safely change without a regression
 * catching it: menu-order sequencing, cross-book validation, unassigning
 * (not deleting) a section's chapters, and cascading a book's sections when
 * the book itself is permanently deleted.
 *
 * @package Chapterwright
 */

/**
 * Tests the hsrtech_sections custom table CRUD functions.
 *
 * @group sections
 */
class Test_Sections extends WP_UnitTestCase {

	/**
	 * A Book post created fresh for every test.
	 *
	 * @var int
	 */
	protected $book_id;

	/**
	 * Creates a fresh Book post before every test.
	 */
	public function set_up() {
		parent::set_up();
		$this->book_id = self::factory()->post->create( array( 'post_type' => HSRTECH_BOOK_POST_TYPE ) );
	}

	/**
	 * A section can be inserted and read back with the same data.
	 */
	public function test_insert_and_get_section() {
		$section_id = hsrtech_insert_section(
			$this->book_id,
			array(
				'name'        => 'Part One',
				'description' => 'Getting started.',
			)
		);

		$this->assertIsInt( $section_id );

		$section = hsrtech_get_section( $section_id );
		$this->assertNotNull( $section );
		$this->assertSame( 'Part One', $section['name'] );
		$this->assertSame( 'Getting started.', $section['description'] );
		$this->assertSame( $this->book_id, (int) $section['book_id'] );
	}

	/**
	 * Inserting a section with a blank name is rejected.
	 */
	public function test_insert_requires_a_non_empty_name() {
		$result = hsrtech_insert_section( $this->book_id, array( 'name' => '   ' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'hsrtech_section_name_required', $result->get_error_code() );
	}

	/**
	 * Looking up a section that doesn't exist returns null.
	 */
	public function test_get_section_returns_null_when_not_found() {
		$this->assertNull( hsrtech_get_section( 999999 ) );
	}

	/**
	 * New sections are appended to the end of the existing menu order.
	 *
	 * A newly inserted section is a draft (see test_new_sections_are_created_as_drafts()
	 * below), so hsrtech_get_book_sections() is called with the same
	 * any-status array the admin app itself uses — this test is about menu
	 * order, not publish status, matching hsrtech_reorder_sections()'s own
	 * any-status lookups in includes/sections.php.
	 */
	public function test_new_sections_append_to_the_end_of_menu_order() {
		$first  = hsrtech_insert_section( $this->book_id, array( 'name' => 'First' ) );
		$second = hsrtech_insert_section( $this->book_id, array( 'name' => 'Second' ) );

		$sections = hsrtech_get_book_sections( $this->book_id, array( 'publish', 'draft', 'pending', 'private', 'future' ) );

		$this->assertSame( array( $first, $second ), wp_list_pluck( $sections, 'id' ) );
	}

	/**
	 * Reordering sections persists the new order.
	 *
	 * Same any-status lookup as the test above, for the same reason.
	 */
	public function test_reorder_sections_persists_the_new_order() {
		$first  = hsrtech_insert_section( $this->book_id, array( 'name' => 'First' ) );
		$second = hsrtech_insert_section( $this->book_id, array( 'name' => 'Second' ) );

		$result = hsrtech_reorder_sections( $this->book_id, array( $second, $first ) );

		$this->assertTrue( $result );
		$sections = hsrtech_get_book_sections( $this->book_id, array( 'publish', 'draft', 'pending', 'private', 'future' ) );
		$this->assertSame( array( $second, $first ), wp_list_pluck( $sections, 'id' ) );
	}

	/**
	 * A newly inserted section starts as a draft, matching a newly inserted
	 * chapter's own draft-first workflow — it must be explicitly published
	 * before it appears in a publish-only lookup like the public table of
	 * contents.
	 */
	public function test_new_sections_are_created_as_drafts() {
		$section_id = hsrtech_insert_section( $this->book_id, array( 'name' => 'Part One' ) );

		$this->assertSame( 'draft', get_post_status( $section_id ) );
		$this->assertSame( array(), hsrtech_get_book_sections( $this->book_id ) );
	}

	/**
	 * Reordering rejects a section id that belongs to a different book.
	 */
	public function test_reorder_rejects_a_section_belonging_to_another_book() {
		$other_book_id = self::factory()->post->create( array( 'post_type' => HSRTECH_BOOK_POST_TYPE ) );
		$foreign_id    = hsrtech_insert_section( $other_book_id, array( 'name' => 'Not this book' ) );

		$result = hsrtech_reorder_sections( $this->book_id, array( $foreign_id ) );

		$this->assertWPError( $result );
		$this->assertSame( 'hsrtech_section_mismatch', $result->get_error_code() );
	}

	/**
	 * Deleting a section unassigns, but does not delete, its chapters.
	 */
	public function test_deleting_a_section_unassigns_but_does_not_delete_its_chapters() {
		$section_id = hsrtech_insert_section( $this->book_id, array( 'name' => 'Part One' ) );
		$chapter_id = self::factory()->post->create( array( 'post_type' => HSRTECH_CHAPTER_POST_TYPE ) );
		update_post_meta( $chapter_id, '_hsrtech_section_id', $section_id );

		$result = hsrtech_delete_section( $section_id );

		$this->assertTrue( $result );
		$this->assertNull( hsrtech_get_section( $section_id ) );
		$this->assertSame( '', get_post_meta( $chapter_id, '_hsrtech_section_id', true ) );
		$this->assertSame( 'publish', get_post_status( $chapter_id ) );
	}

	/**
	 * Deleting a section that doesn't exist returns a WP_Error.
	 */
	public function test_deleting_a_missing_section_returns_a_wp_error() {
		$result = hsrtech_delete_section( 999999 );

		$this->assertWPError( $result );
		$this->assertSame( 'hsrtech_section_not_found', $result->get_error_code() );
	}

	/**
	 * Permanently deleting a book cascades to its sections.
	 */
	public function test_permanently_deleting_a_book_cascades_to_its_sections() {
		hsrtech_insert_section( $this->book_id, array( 'name' => 'Part One' ) );
		hsrtech_insert_section( $this->book_id, array( 'name' => 'Part Two' ) );

		wp_delete_post( $this->book_id, true );

		$this->assertSame( array(), hsrtech_get_book_sections( $this->book_id ) );
	}

	/**
	 * Trashing a book (not permanently deleting it) leaves its sections intact.
	 */
	public function test_trashing_a_book_does_not_delete_its_sections() {
		$section_id = hsrtech_insert_section( $this->book_id, array( 'name' => 'Part One' ) );

		wp_trash_post( $this->book_id );

		$this->assertNotNull( hsrtech_get_section( $section_id ) );
	}
}

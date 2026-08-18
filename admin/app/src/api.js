/**
 * REST access for the admin app.
 *
 * Book/chapter title, excerpt, cover, and the small scalar meta fields
 * (subtitle, accent color, book/section/order) all go through the core
 * `/wp/v2/hsrtech_book` and `/wp/v2/hsrtech_chapter` endpoints — those meta keys are
 * registered for REST in includes/content-types.php, so no custom
 * controller is needed for them. Sections, and the one bulk chapter-reorder
 * operation, go through the small custom `chapterwright/v1` namespace
 * registered in admin/rest/.
 *
 * WordPress automatically wires the REST nonce into every apiFetch() call
 * once the `wp-api-fetch` script is enqueued as a dependency (see
 * admin/app.php) — nothing to configure here.
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Every status an author might want to see in the admin app. The public
 * site only ever queries 'publish' (see includes/queries.php) — this list
 * is specifically for the authoring UI, which should show drafts too.
 */
const EDITABLE_STATUSES = [ 'publish', 'draft', 'pending', 'private', 'future' ];

/**
 * A throwaway value appended as a `_ts` query arg to every GET below.
 *
 * Confirmed live: what earlier looked like brief read-after-write replication
 * lag on the host (a chapter's create request came back 201 with the right
 * data, but the very next GET of the chapter list didn't have it yet) was
 * actually a page/edge cache in front of WordPress caching that GET request's
 * URL — clearing the server-side cache made the missing chapter reappear
 * immediately, with no other change. WordPress core does send
 * `Cache-Control: no-cache` on REST responses for logged-in requests, but a
 * cache sitting in front of PHP can still key purely on the request URL and
 * ignore that response header entirely. The book-detail/books-list screens
 * already avoid re-fetching right after a write they made themselves — this
 * covers every other GET (initial load, focus-triggered re-sync, and any
 * refresh-on-failure) by making each of those requests a distinct URL the
 * cache has never seen before, so it can't serve a stale hit no matter how
 * long its TTL is.
 *
 * @return {number} Current timestamp, unique enough for this purpose.
 */
function bustCache() {
	return Date.now();
}

/**
 * Fetch every book, newest-edited first isn't useful here — alphabetical is
 * easier to scan in a management list.
 *
 * @return {Promise<Array>} Book post objects (edit context).
 */
export function getBooks() {
	return apiFetch( {
		path: addQueryArgs( '/wp/v2/hsrtech_book', {
			per_page: 100,
			status: EDITABLE_STATUSES,
			orderby: 'title',
			order: 'asc',
			context: 'edit',
			_embed: 'wp:featuredmedia',
			_ts: bustCache(),
		} ),
	} );
}

/**
 * Fetch a single book (edit context, so raw title/meta are present).
 *
 * @param {number} bookId Book post ID.
 * @return {Promise<Object>} Book post object.
 */
export function getBook( bookId ) {
	return apiFetch( {
		path: addQueryArgs( `/wp/v2/hsrtech_book/${ bookId }`, {
			context: 'edit',
			_embed: 'wp:featuredmedia',
			_ts: bustCache(),
		} ),
	} );
}

/**
 * Create a new draft book with just a title. The author fills in cover,
 * excerpt, subtitle, and introduction afterward — in the block editor for
 * content, or via updateBook() for the meta fields.
 *
 * @param {string} title Book title.
 * @return {Promise<Object>} The new book post object.
 */
export function createBook( title ) {
	return apiFetch( {
		path: '/wp/v2/hsrtech_book',
		method: 'POST',
		data: { title, status: 'draft' },
	} );
}

/**
 * Patch a book's fields (title, meta, status, etc.).
 *
 * @param {number} bookId Book post ID.
 * @param {Object} data   Fields to update.
 * @return {Promise<Object>} The updated book post object.
 */
export function updateBook( bookId, data ) {
	return apiFetch( { path: `/wp/v2/hsrtech_book/${ bookId }`, method: 'POST', data } );
}

/**
 * Move a book to the trash.
 *
 * @param {number} bookId Book post ID.
 * @return {Promise<Object>}
 */
export function trashBook( bookId ) {
	return apiFetch( { path: `/wp/v2/hsrtech_book/${ bookId }`, method: 'DELETE' } );
}

/**
 * Fetch every trashed book the current user can see (their own, or every
 * author's with 'edit_others_hsrtech_books' — see admin/rest/books.php).
 *
 * Goes through the chapterwright/v1 namespace rather than the core
 * `/wp/v2/hsrtech_book` collection endpoint with `status=trash` — core has
 * no dedicated "list the trash" contract this plugin can rely on the same
 * way it already avoided relying on multi-status collection queries for
 * chapters (see getBookChapters() above); a small, purpose-built route is
 * one door instead of a guess about how the core one behaves for this
 * status.
 *
 * @return {Promise<Array>} Trashed book objects: `{ id, title, trashed }`.
 */
export function getTrashedBooks() {
	return apiFetch( { path: addQueryArgs( '/chapterwright/v1/books/trash', { _ts: bustCache() } ) } );
}

/**
 * Restore a trashed book. Core's REST posts controller has no "untrash"
 * verb of its own (DELETE either trashes or, with `force`, permanently
 * deletes) — this is the one custom route admin/rest/books.php adds.
 *
 * @param {number} bookId Book post ID.
 * @return {Promise<Object>} The restored book: `{ id, title, status }`.
 */
export function restoreBook( bookId ) {
	return apiFetch( { path: `/chapterwright/v1/books/${ bookId }/restore`, method: 'POST' } );
}

/**
 * Permanently delete a trashed book — bypasses the trash entirely, no
 * custom route needed: core's own DELETE handler already supports this via
 * `force`, gated on the same `delete_post` capability restoring is.
 *
 * @param {number} bookId Book post ID.
 * @return {Promise<Object>}
 */
export function deleteBookPermanently( bookId ) {
	return apiFetch( { path: addQueryArgs( `/wp/v2/hsrtech_book/${ bookId }`, { force: true } ), method: 'DELETE' } );
}

/**
 * Fetch every chapter belonging to one book, in `_hsrtech_order` order,
 * regardless of status (draft, pending, private, future, or published).
 *
 * Goes through the chapterwright/v1 namespace rather than the core
 * `/wp/v2/hsrtech_chapter` collection endpoint — this used to fetch every
 * chapter the current user could edit (across every book) with `status` as
 * an array and `context=edit`, then filter client-side by `_hsrtech_book_id`.
 * That relied on the collection endpoint's own status/context capability
 * handling behaving as expected for every status in the array on every
 * request, and a newly created draft chapter could silently fail to come
 * back — no error, just missing from the list. The server-side route this
 * calls instead wraps `hsrtech_get_all_chapters_for_admin()`
 * (includes/queries.php), the same plain `get_posts()` query already
 * trusted elsewhere in this plugin.
 *
 * @param {number} bookId Book post ID.
 * @return {Promise<Array>} Chapter post objects belonging to this book, already ordered.
 */
export function getBookChapters( bookId ) {
	return apiFetch( { path: addQueryArgs( `/chapterwright/v1/books/${ bookId }/chapters`, { _ts: bustCache() } ) } );
}

/**
 * Create a new draft chapter already assigned to a book (and optionally a
 * section), with the next chapter order pre-computed from the chapters
 * already passed in.
 *
 * Meta can be sent in this same create request — no need to split it into a
 * separate update call. (An earlier version of this function did split it,
 * on the theory that `create_item()` couldn't resolve capabilities for a
 * brand-new post in time; that theory was wrong. The real cause was that
 * neither `hsrtech_book` nor `hsrtech_chapter` declared `'custom-fields'` support,
 * so `WP_REST_Posts_Controller::get_item_schema()` never added a `meta`
 * property to either post type's REST schema in the first place — every
 * `meta` value sent to either endpoint, in a create or an update, was
 * silently ignored with no error. Fixed in `hsrtech_register_post_types()`,
 * includes/content-types.php; see that function's docblock and the
 * "Unreleased" entry in AGENTS.md for how this was actually root-caused.)
 *
 * @param {Object} args
 * @param {number} args.bookId       Parent book ID.
 * @param {string} args.title        Chapter title.
 * @param {number} [args.sectionId]  Section ID, if any.
 * @param {Array}  args.siblings     Chapters already fetched for this book (via getBookChapters), used to compute the next order number.
 * @return {Promise<Object>} The new chapter post object, with meta set.
 */
export function createChapter( { bookId, title, sectionId, siblings } ) {
	// Guard against a non-array `siblings` (e.g. a chapter list that failed to
	// load, or hasn't finished loading yet) throwing synchronously here. That
	// throw would happen before this function returns a Promise at all, so
	// the caller's `.catch()` never sees it — the "Add chapter" button would
	// just silently stop working with nothing in the UI to explain why.
	const chapterList = Array.isArray( siblings ) ? siblings : [];
	const nextOrder =
		1 + chapterList.reduce( ( max, chapter ) => Math.max( max, Number( chapter.meta?._hsrtech_order || 0 ) ), 0 );

	return apiFetch( {
		path: '/wp/v2/hsrtech_chapter',
		method: 'POST',
		data: {
			title,
			status: 'draft',
			meta: {
				_hsrtech_book_id: bookId,
				_hsrtech_order: nextOrder,
				...( sectionId ? { _hsrtech_section_id: sectionId } : {} ),
			},
		},
	} );
}

/**
 * Move a chapter to the trash.
 *
 * @param {number} chapterId Chapter post ID.
 * @return {Promise<Object>}
 */
export function trashChapter( chapterId ) {
	return apiFetch( { path: `/wp/v2/hsrtech_chapter/${ chapterId }`, method: 'DELETE' } );
}

/**
 * Persist a new section/order arrangement for a book's chapters in one
 * request, after a drag-and-drop reorder in the UI.
 *
 * @param {number} bookId   Book post ID.
 * @param {Array}  chapters `{ id, sectionId, order }` entries for every chapter whose position changed.
 * @return {Promise<Object>}
 */
export function reorderChapters( bookId, chapters ) {
	return apiFetch( {
		path: `/chapterwright/v1/books/${ bookId }/chapters/reorder`,
		method: 'POST',
		data: {
			chapters: chapters.map( ( chapter ) => ( {
				id: chapter.id,
				section_id: chapter.sectionId || 0,
				order: chapter.order,
			} ) ),
		},
	} );
}

/**
 * Fetch a book's sections, in display order.
 *
 * @param {number} bookId Book post ID.
 * @return {Promise<Array>} Section rows.
 */
export function getSections( bookId ) {
	return apiFetch( { path: addQueryArgs( `/chapterwright/v1/books/${ bookId }/sections`, { _ts: bustCache() } ) } );
}

/**
 * Create a section.
 *
 * @param {number} bookId      Book post ID.
 * @param {string} name        Section name.
 * @param {string} description Optional descriptive text.
 * @return {Promise<Object>} The new section row.
 */
export function createSection( bookId, name, description = '' ) {
	return apiFetch( {
		path: `/chapterwright/v1/books/${ bookId }/sections`,
		method: 'POST',
		data: { name, description },
	} );
}

/**
 * Update a section's name and/or description.
 *
 * @param {number} sectionId Section ID.
 * @param {Object} data      Fields to change: name and/or description.
 * @return {Promise<Object>} The updated section row.
 */
export function updateSection( sectionId, data ) {
	return apiFetch( { path: `/chapterwright/v1/sections/${ sectionId }`, method: 'POST', data } );
}

/**
 * Delete a section. Chapters assigned to it become unassigned server-side.
 *
 * @param {number} sectionId Section ID.
 * @return {Promise<Object>}
 */
export function deleteSection( sectionId ) {
	return apiFetch( { path: `/chapterwright/v1/sections/${ sectionId }`, method: 'DELETE' } );
}

/**
 * Persist a new section order for a book.
 *
 * @param {number}   bookId       Book post ID.
 * @param {number[]} orderedIds   Section IDs in the desired order.
 * @return {Promise<Array>} The reordered section rows.
 */
export function reorderSections( bookId, orderedIds ) {
	return apiFetch( {
		path: `/chapterwright/v1/books/${ bookId }/sections/reorder`,
		method: 'POST',
		data: { order: orderedIds },
	} );
}

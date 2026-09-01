/**
 * Book detail screen: quick-edit subtitle/accent, manage sections, and
 * organize chapters (assign to a section, reorder, add, remove).
 *
 * Writing a chapter's, section's, or book's actual content still happens in
 * the normal block editor — this screen only manages the structural pieces
 * (which book, which section, what order, and — for a section — its name and
 * short description) that used to live in scattered meta boxes. "Edit
 * content" links open post.php in a new tab for that reason.
 *
 * A section is a single hsrtech_section post (includes/sections.php): its
 * title is the heading shown in the table of contents, its excerpt is the
 * description shown under that heading (both editable inline right here,
 * SectionRow below), and its content is an optional fuller introduction page
 * — when there's content, the table of contents links the heading to it.
 * Section has no native admin list screen of its own ('show_in_menu' =>
 * false, includes/content-types.php) to avoid a second, disconnected way to
 * find the same content — this app is the only place a section is surfaced,
 * same as it already is for books and chapters.
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	Card,
	CardHeader,
	CardBody,
	CheckboxControl,
	TextControl,
	TextareaControl,
	Spinner,
	Notice,
	SelectControl,
} from '@wordpress/components';
import {
	getBook,
	updateBook,
	trashBook,
	getBookChapters,
	getSections,
	createSection,
	updateSection,
	deleteSection,
	reorderSections,
	createChapter,
	trashChapter,
	reorderChapters,
} from '../api';

const UNASSIGNED = 0;

export default function BookDetail( { bookId } ) {
	const [ book, setBook ] = useState( null );
	const [ sections, setSections ] = useState( [] );
	const [ chapters, setChapters ] = useState( [] );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );
	const [ trashing, setTrashing ] = useState( false );

	const load = useCallback( () => {
		setError( '' );
		Promise.all( [ getBook( bookId ), getSections( bookId ), getBookChapters( bookId ) ] )
			.then( ( [ bookData, sectionData, chapterData ] ) => {
				setBook( bookData );
				setSections( sectionData );
				setChapters( chapterData );
			} )
			.catch( ( err ) => setError( err.message || __( 'This book could not be loaded.', 'chapterwright' ) ) );
	}, [ bookId ] );

	useEffect( load, [ load ] );

	// Timestamp of the most recent local change (add/save/reorder/trash),
	// via markSections()/markChapters()/markBook() below — used to skip a
	// focus-triggered re-sync for a few seconds after one, so it can't
	// clobber a just-added row with a stale read. See the focus effect
	// further down for why that race is real, not hypothetical.
	const lastMutatedAtRef = useRef( 0 );
	const REFRESH_COOLDOWN_MS = 8000;

	const markBook = useCallback( ( updater ) => {
		lastMutatedAtRef.current = Date.now();
		setBook( updater );
	}, [] );
	const markSections = useCallback( ( updater ) => {
		lastMutatedAtRef.current = Date.now();
		setSections( updater );
	}, [] );
	const markChapters = useCallback( ( updater ) => {
		lastMutatedAtRef.current = Date.now();
		setChapters( updater );
	}, [] );

	// Re-sync whenever this tab regains focus. "Edit" (on a book, section
	// header, or chapter row) opens the Block Editor in a new tab for the
	// actual content/title — renaming a chapter there, for instance — and
	// this screen had no way to notice a change made somewhere else. Without
	// this, the rename is real and saved, but this list keeps showing the
	// old title until the whole admin app page is manually reloaded, which
	// looks exactly like the rename silently not working.
	//
	// The cooldown guards a real interaction with that fix: addChapter()/
	// addSection() below append the row the create request already handed
	// back, specifically because an immediate re-fetch right after a create
	// can come back without it yet (read-after-write lag on this host — see
	// their comments). A focus event landing in that same short window would
	// otherwise call load(), overwrite the correctly-updated local list with
	// that stale, lagging fetch, and make the just-added row flash and
	// disappear — reported live as "I added a new chapter, it appeared for a
	// second, and disappeared after that."
	useEffect( () => {
		const onFocus = () => {
			if ( Date.now() - lastMutatedAtRef.current < REFRESH_COOLDOWN_MS ) {
				return;
			}
			load();
		};
		window.addEventListener( 'focus', onFocus );
		return () => window.removeEventListener( 'focus', onFocus );
	}, [ load ] );

	// Only replace the whole screen with the error when there's nothing else
	// to show (the initial load itself failed, so there's no book/sections/
	// chapters to render around it). Once a book has loaded, a later action
	// failing (save, add section, add chapter, reorder, trash, …) surfaces as
	// a dismissible banner above the existing UI instead — see the render
	// below — so one failed request doesn't hide everything else on the page.
	if ( error && ! book ) {
		return (
			<Notice status="error" isDismissible={ false } className="hsrtech-notice">
				{ error }
			</Notice>
		);
	}

	if ( ! book ) {
		return (
			<div className="hsrtech-loading">
				<Spinner />
			</div>
		);
	}

	const adminUrl = window.hsrtechApp?.adminUrl || '/wp-admin/';
	// A section IS its post — see the file docblock above — so this same
	// helper reaches a section's own edit screen too (SectionRow below),
	// exactly like it already does for a book or chapter, with no separate
	// "does this section have a linked page yet" branch needed.
	const editLink = ( postId ) => `${ adminUrl }post.php?post=${ postId }&action=edit`;

	const handleTrashBook = () => {
		const title = book.title?.raw || book.title?.rendered || '';
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Move "%s" to the trash? Its chapters and sections are not affected — restore the book from Trash to bring it back.', 'chapterwright' ), title ) ) ) {
			return;
		}
		setTrashing( true );
		trashBook( book.id )
			.then( () => {
				window.location.hash = '#/books';
			} )
			.catch( ( err ) => {
				setTrashing( false );
				setError( err.message || __( 'The book could not be trashed.', 'chapterwright' ) );
			} );
	};

	return (
		<div className="hsrtech-book-detail">
			<a className="hsrtech-back-link" href="#/books">
				<span aria-hidden="true">&larr;</span> { __( 'All books', 'chapterwright' ) }
			</a>

			{ error && (
				<Notice status="error" className="hsrtech-notice" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }

			{ notice && (
				<Notice status="success" className="hsrtech-notice" onRemove={ () => setNotice( '' ) }>
					{ notice }
				</Notice>
			) }

			<div className="hsrtech-book-detail__title-row">
				<div>
					<h2 className="hsrtech-book-detail__title">{ book.title?.raw || book.title?.rendered }</h2>
					<p className="hsrtech-book-detail__hint">
						{ __( 'Write the introduction, set the cover image, and add an excerpt in the Block Editor.', 'chapterwright' ) }
					</p>
				</div>
				<div className="hsrtech-book-detail__title-actions">
					{ /* Same book.link the books-list.js card footer uses — see
					     hsrtech_rest_prepare_book_view_link() (content-types.php) for
					     why this already works for a draft book too. */ }
					{ book.link && (
						<Button
							__next40pxDefaultSize
							variant="tertiary"
							icon="external"
							label={ __( 'View', 'chapterwright' ) }
							href={ book.link }
							target="_blank"
							rel="noopener"
						/>
					) }
					<Button __next40pxDefaultSize variant="secondary" href={ editLink( book.id ) } target="_blank">
						{ __( 'Open in Block Editor →', 'chapterwright' ) }
					</Button>
				</div>
			</div>

			<BookFields
				book={ book }
				onSaved={ ( updated ) => {
					markBook( updated );
					setNotice( __( 'Book details saved.', 'chapterwright' ) );
				} }
				onError={ setError }
			/>

			<SectionsManager
				bookId={ bookId }
				sections={ sections }
				onChange={ markSections }
				onError={ setError }
				editLink={ editLink }
			/>

			<ChaptersManager
				bookId={ bookId }
				sections={ sections }
				chapters={ chapters }
				onChange={ markChapters }
				onError={ setError }
				editLink={ editLink }
			/>

			<Card className="hsrtech-panel hsrtech-panel--danger">
				<CardHeader>
					<h3 className="hsrtech-panel__title">{ __( 'Danger zone', 'chapterwright' ) }</h3>
				</CardHeader>
				<CardBody>
					<p className="hsrtech-panel__description">
						{ __( 'Moves the book to the trash. Its chapters and sections are kept and unaffected — restore the book from Trash to bring it back, or permanently delete it from there.', 'chapterwright' ) }
					</p>
					<Button
						__next40pxDefaultSize
						variant="secondary"
						isDestructive
						isBusy={ trashing }
						disabled={ trashing }
						onClick={ handleTrashBook }
					>
						{ __( 'Move book to Trash', 'chapterwright' ) }
					</Button>
				</CardBody>
			</Card>
		</div>
	);
}

/**
 * Subtitle + accent color quick-edit, replacing the old "Book Details" meta box.
 */
function BookFields( { book, onSaved, onError } ) {
	const [ subtitle, setSubtitle ] = useState( book.meta?._hsrtech_subtitle || '' );
	const [ accent, setAccent ] = useState( book.meta?._hsrtech_accent || '#f45d48' );
	const [ comingSoon, setComingSoon ] = useState( !! book.meta?._hsrtech_coming_soon );
	const [ saving, setSaving ] = useState( false );

	const save = () => {
		setSaving( true );
		const data = { meta: { _hsrtech_subtitle: subtitle, _hsrtech_accent: accent, _hsrtech_coming_soon: comingSoon } };
		// Checking "Coming soon" is meant to work as one self-contained action
		// from this screen — publish the book (if it isn't already) in the
		// same request, since an unpublished draft would not appear in the
		// library at all and the flag would have no visible effect. Unchecking
		// it never un-publishes the book back to draft — that would hide a
		// book an author may have already been sharing a link to.
		if ( comingSoon && 'publish' !== book.status ) {
			data.status = 'publish';
		}
		updateBook( book.id, data )
			.then( ( updated ) => {
				setSaving( false );
				onSaved( updated );
			} )
			.catch( ( err ) => {
				setSaving( false );
				// This used to just stop the spinner with no explanation at all
				// when the save failed — the button would look like it did
				// nothing, with no clue why. Surface it the same way every
				// other action on this screen does.
				onError( err.message || __( 'Book details could not be saved.', 'chapterwright' ) );
			} );
	};

	return (
		<Card className="hsrtech-panel">
			<CardHeader>
				<h3 className="hsrtech-panel__title">{ __( 'Book details', 'chapterwright' ) }</h3>
			</CardHeader>
			<CardBody>
				<div className="hsrtech-field-row">
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Subtitle', 'chapterwright' ) }
						value={ subtitle }
						onChange={ setSubtitle }
						rows={ 2 }
					/>
				</div>
				<div className="hsrtech-field-row">
					<BaseControl
						__nextHasNoMarginBottom
						id="hsrtech-accent-input"
						label={ __( 'Accent color', 'chapterwright' ) }
						help={ __( 'Colors links, hover states, blockquote/callout borders, and the reading-progress bar on this book’s pages.', 'chapterwright' ) }
					>
						<input
							id="hsrtech-accent-input"
							className="hsrtech-color-input"
							type="color"
							value={ accent }
							onChange={ ( event ) => setAccent( event.target.value ) }
						/>
					</BaseControl>
				</div>
				<div className="hsrtech-field-row">
					<CheckboxControl
						__nextHasNoMarginBottom
						label={ __( 'Coming soon', 'chapterwright' ) }
						checked={ comingSoon }
						onChange={ setComingSoon }
						help={
							'publish' === book.status
								? __( 'Shows a "Coming soon" badge on the library and book page instead of a reading link — for announcing a book before its chapters are ready.', 'chapterwright' )
								: __( 'Shows a "Coming soon" badge instead of a reading link, and publishes the book so it actually appears in your library.', 'chapterwright' )
						}
					/>
				</div>
				<Button
					__next40pxDefaultSize
					variant="primary"
					isBusy={ saving }
					disabled={ saving }
					onClick={ save }
				>
					{ __( 'Save book details', 'chapterwright' ) }
				</Button>
			</CardBody>
		</Card>
	);
}

/**
 * Add, rename, describe, reorder, and delete a book's sections.
 */
function SectionsManager( { bookId, sections, onChange, onError, editLink } ) {
	const [ name, setName ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ busy, setBusy ] = useState( false );

	const refresh = () => getSections( bookId ).then( onChange ).catch( ( err ) => onError( err.message ) );

	const addSection = ( event ) => {
		event.preventDefault();
		if ( ! name.trim() ) {
			return;
		}
		setBusy( true );
		createSection( bookId, name.trim(), description.trim() )
			.then( ( section ) => {
				setName( '' );
				setDescription( '' );
				setBusy( false );
				// Append the section the create request already handed
				// back, rather than immediately re-fetching the list. A
				// live-site report showed a newly created chapter not
				// showing up right after an otherwise-successful create —
				// the re-fetch that followed simply hadn't caught up yet
				// (most likely brief read-after-write lag on the host, not
				// anything wrong with what was actually saved). The create
				// response already has everything the list needs to show
				// the new row, so use that directly instead of trusting an
				// immediate re-read to already reflect it.
				onChange( ( previous ) => [ ...previous, section ] );
			} )
			.catch( ( err ) => {
				setBusy( false );
				onError( err.message );
			} );
	};

	const saveSection = ( section, fields ) => {
		updateSection( section.id, fields )
			.then( ( updated ) => {
				// Apply the update response directly instead of refreshing
				// the whole list — same reasoning as addSection()/
				// addChapter() above: an immediate re-fetch right after a
				// write can lag behind on this host and show the pre-edit
				// name/description for a moment (or longer). The update
				// response already has the saved fields.
				onChange( ( previous ) => previous.map( ( s ) => ( s.id === updated.id ? updated : s ) ) );
			} )
			.catch( ( err ) => {
				onError( err.message || __( 'The section could not be saved.', 'chapterwright' ) );
				// Still refresh on failure — if this section was already
				// deleted elsewhere (another tab, a previous request that
				// actually went through despite looking like it failed),
				// the stale section would otherwise sit in the list forever
				// with every retry hitting the same error.
				refresh();
			} );
	};

	const removeSection = ( section ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Delete "%s"? Its chapters will stay, unassigned.', 'chapterwright' ), section.name ) ) ) {
			return;
		}
		deleteSection( section.id )
			.then( () => {
				// Remove it locally rather than refreshing — the same
				// read-after-write lag that can hide a newly created row can
				// just as easily show a just-deleted one for a moment
				// longer, which reads as "delete didn't work."
				onChange( ( previous ) => previous.filter( ( s ) => s.id !== section.id ) );
			} )
			.catch( ( err ) => {
				onError( err.message || __( 'The section could not be deleted.', 'chapterwright' ) );
				refresh();
			} );
	};

	const move = ( index, direction ) => {
		const target = index + direction;
		if ( target < 0 || target >= sections.length ) {
			return;
		}
		const reordered = [ ...sections ];
		[ reordered[ index ], reordered[ target ] ] = [ reordered[ target ], reordered[ index ] ];
		reorderSections( bookId, reordered.map( ( section ) => section.id ) )
			.then( onChange )
			.catch( ( err ) => {
				onError( err.message || __( 'The section order could not be saved.', 'chapterwright' ) );
				// Re-sync from the server rather than leaving the optimistic
				// (already-swapped) order on screen when the save failed.
				refresh();
			} );
	};

	return (
		<Card className="hsrtech-panel">
			<CardHeader>
				<h3 className="hsrtech-panel__title">{ __( 'Sections', 'chapterwright' ) }</h3>
			</CardHeader>
			<CardBody>
				<p className="hsrtech-panel__description">
					{ __( 'Group chapters under a heading, such as "Part I" or "Getting Started". The description shows under the heading in the table of contents. Optional — chapters with no section appear under a default "Chapters" heading. Open a section in the Block Editor to give it its own longer introduction page — the heading links there automatically once it has content.', 'chapterwright' ) }
				</p>

				{ 0 === sections.length && (
					<p className="hsrtech-empty-state hsrtech-empty-state--inline">
						{ __( 'No sections yet — chapters will appear under a default "Chapters" heading until you add one.', 'chapterwright' ) }
					</p>
				) }

				{ sections.length > 0 && (
					<div className="hsrtech-row-list">
						{ sections.map( ( section, index ) => (
							<SectionRow
								key={ section.id }
								section={ section }
								onSave={ ( fields ) => saveSection( section, fields ) }
								onDelete={ () => removeSection( section ) }
								onMoveUp={ index > 0 ? () => move( index, -1 ) : null }
								onMoveDown={ index < sections.length - 1 ? () => move( index, 1 ) : null }
								editLink={ editLink }
							/>
						) ) }
					</div>
				) }

				<form onSubmit={ addSection } className="hsrtech-inline-form hsrtech-inline-form--section">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'New section name', 'chapterwright' ) }
						hideLabelFromVision
						placeholder={ __( 'e.g. Getting Started', 'chapterwright' ) }
						value={ name }
						onChange={ setName }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'New section description', 'chapterwright' ) }
						hideLabelFromVision
						placeholder={ __( 'Optional description', 'chapterwright' ) }
						value={ description }
						onChange={ setDescription }
					/>
					<Button
						__next40pxDefaultSize
						variant="secondary"
						type="submit"
						isBusy={ busy }
						disabled={ busy || ! name.trim() }
					>
						{ __( 'Add section', 'chapterwright' ) }
					</Button>
				</form>
			</CardBody>
		</Card>
	);
}

function SectionRow( { section, onSave, onDelete, onMoveUp, onMoveDown, editLink } ) {
	const [ editing, setEditing ] = useState( false );
	const [ name, setName ] = useState( section.name );
	const [ description, setDescription ] = useState( section.description );

	if ( ! editing ) {
		return (
			<div className="hsrtech-row">
				<div className="hsrtech-row__reorder">
					<Button icon="arrow-up-alt2" label={ __( 'Move up', 'chapterwright' ) } onClick={ onMoveUp } disabled={ ! onMoveUp } size="small" />
					<Button icon="arrow-down-alt2" label={ __( 'Move down', 'chapterwright' ) } onClick={ onMoveDown } disabled={ ! onMoveDown } size="small" />
				</div>
				<div className="hsrtech-row__text">
					<strong>{ section.name }</strong>
					{ /* Same status pill as a chapter row (ChaptersManager below) —
					     a new section starts as a draft, same as a new chapter, so
					     this is the one place that shows whether it's actually live
					     in the public table of contents yet. */ }
					<span className={ `hsrtech-status-pill hsrtech-status-pill--${ section.status }` }>{ section.status }</span>
					{ section.description && <p className="hsrtech-row__meta">{ section.description }</p> }
				</div>
				<div className="hsrtech-row__actions">
					{ /* A section is a real post (see the file docblock above) — this
					     opens its own edit screen for writing the optional longer
					     introduction, same link pattern and wording as the Book
					     title row's own "Open in Block Editor" button. */ }
					<Button
						variant="tertiary"
						size="small"
						href={ editLink( section.id ) }
						target="_blank"
						rel="noopener"
					>
						{ __( 'Open in Block Editor →', 'chapterwright' ) }
					</Button>
					<Button variant="tertiary" size="small" onClick={ () => setEditing( true ) }>{ __( 'Edit', 'chapterwright' ) }</Button>
					<Button variant="tertiary" isDestructive size="small" onClick={ onDelete }>{ __( 'Delete', 'chapterwright' ) }</Button>
				</div>
			</div>
		);
	}

	return (
		<div className="hsrtech-row hsrtech-row--editing">
			<TextControl __next40pxDefaultSize label={ __( 'Name', 'chapterwright' ) } value={ name } onChange={ setName } />
			<TextareaControl label={ __( 'Description', 'chapterwright' ) } value={ description } onChange={ setDescription } rows={ 2 } />
			<div className="hsrtech-row__actions">
				<Button
					__next40pxDefaultSize
					variant="primary"
					size="small"
					onClick={ () => {
						onSave( { name, description } );
						setEditing( false );
					} }
				>
					{ __( 'Save', 'chapterwright' ) }
				</Button>
				<Button variant="tertiary" size="small" onClick={ () => setEditing( false ) }>{ __( 'Cancel', 'chapterwright' ) }</Button>
			</div>
		</div>
	);
}

/**
 * Chapters grouped by section, with reordering, section reassignment,
 * quick-add, and removal.
 */
function ChaptersManager( { bookId, sections, chapters, onChange, onError, editLink } ) {
	const [ title, setTitle ] = useState( '' );
	const [ newChapterSection, setNewChapterSection ] = useState( '' );
	const [ busy, setBusy ] = useState( false );

	const refresh = () => getBookChapters( bookId ).then( onChange ).catch( ( err ) => onError( err.message ) );

	const persist = ( ordered ) => {
		const payload = ordered.map( ( chapter, index ) => ( {
			id: chapter.id,
			sectionId: chapter.meta?._hsrtech_section_id || UNASSIGNED,
			order: index + 1,
		} ) );

		// Apply the same section/order values locally that are being sent,
		// instead of trusting an immediate re-fetch after the request
		// succeeds — the reorder endpoint only returns the list of changed
		// ids, not full chapter objects, but this is already exactly what
		// the server is about to be told to set, so there's nothing an
		// extra round trip would add except a chance to show a stale order
		// for a moment (or longer, on this host).
		const updated = ordered.map( ( chapter, index ) => ( {
			...chapter,
			meta: { ...chapter.meta, _hsrtech_section_id: chapter.meta?._hsrtech_section_id || UNASSIGNED, _hsrtech_order: index + 1 },
		} ) );

		reorderChapters( bookId, payload )
			.then( () => onChange( updated ) )
			.catch( ( err ) => {
				onError( err.message || __( 'The chapter order could not be saved.', 'chapterwright' ) );
				// Re-sync from the server rather than leaving the optimistic
				// (already-reordered) list on screen when the save failed.
				refresh();
			} );
	};

	const move = ( index, direction ) => {
		const target = index + direction;
		if ( target < 0 || target >= chapters.length ) {
			return;
		}
		const reordered = [ ...chapters ];
		[ reordered[ index ], reordered[ target ] ] = [ reordered[ target ], reordered[ index ] ];
		persist( reordered );
	};

	const changeSection = ( chapterId, sectionId ) => {
		const updated = chapters.map( ( chapter ) =>
			chapter.id === chapterId
				? { ...chapter, meta: { ...chapter.meta, _hsrtech_section_id: sectionId } }
				: chapter
		);
		persist( updated );
	};

	const removeChapter = ( chapter ) => {
		const chapterTitle = chapter.title?.raw || chapter.title?.rendered || '';
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Move "%s" to the trash?', 'chapterwright' ), chapterTitle ) ) ) {
			return;
		}
		trashChapter( chapter.id )
			.then( () => {
				// Remove it locally rather than refreshing — same reasoning
				// as SectionsManager's removeSection: an immediate re-fetch
				// can lag behind a write on this host, and could show the
				// just-trashed chapter for a moment longer, reading as
				// "trash didn't work."
				onChange( ( previous ) => previous.filter( ( c ) => c.id !== chapter.id ) );
			} )
			.catch( ( err ) => {
				onError( err.message || __( 'The chapter could not be trashed.', 'chapterwright' ) );
				// Still refresh on failure — same reasoning as
				// SectionsManager's removeSection/saveSection: a chapter
				// that's already gone server-side would otherwise stay
				// stuck in the list looking trashable, with every retry
				// just repeating the same error.
				refresh();
			} );
	};

	const addChapter = ( event ) => {
		event.preventDefault();
		if ( ! title.trim() ) {
			return;
		}
		setBusy( true );
		createChapter( {
			bookId,
			title: title.trim(),
			sectionId: newChapterSection ? Number( newChapterSection ) : undefined,
			siblings: chapters,
		} )
			.then( ( chapter ) => {
				setTitle( '' );
				setNewChapterSection( '' );
				setBusy( false );
				// Append the chapter the create request already handed
				// back, rather than immediately re-fetching the list.
				// Reported live: a chapter's create request succeeded
				// (201, correct id and meta in the response) but the
				// re-fetch that immediately followed it came back without
				// that chapter — most likely brief read-after-write lag on
				// the host, not anything wrong with what was actually
				// saved. The create response already has everything the
				// list needs to show the new row, so use that directly
				// instead of trusting an immediate re-read to already
				// reflect it.
				onChange( ( previous ) => [ ...previous, chapter ] );
			} )
			.catch( ( err ) => {
				setBusy( false );
				onError( err.message );
			} );
	};

	const sectionOptions = [
		{ label: __( 'Chapters (no section)', 'chapterwright' ), value: '' },
		...sections.map( ( section ) => ( { label: section.name, value: String( section.id ) } ) ),
	];

	return (
		<Card className="hsrtech-panel">
			<CardHeader>
				<h3 className="hsrtech-panel__title">{ __( 'Chapters', 'chapterwright' ) }</h3>
			</CardHeader>
			<CardBody>
				{ 0 === chapters.length && (
					<p className="hsrtech-empty-state hsrtech-empty-state--inline">
						{ __( 'No chapters yet. Add the first one below.', 'chapterwright' ) }
					</p>
				) }

				{ chapters.length > 0 && (
					<div className="hsrtech-row-list">
						{ chapters.map( ( chapter, index ) => (
							<div className="hsrtech-row hsrtech-row--chapter" key={ chapter.id }>
								<div className="hsrtech-row__reorder">
									<Button icon="arrow-up-alt2" label={ __( 'Move up', 'chapterwright' ) } size="small" disabled={ 0 === index } onClick={ () => move( index, -1 ) } />
									<Button icon="arrow-down-alt2" label={ __( 'Move down', 'chapterwright' ) } size="small" disabled={ index === chapters.length - 1 } onClick={ () => move( index, 1 ) } />
								</div>
								<div className="hsrtech-row__text">
									<strong>
										{ /* Reflects this chapter's position in the list as shown, not the
										     saved _hsrtech_order meta directly — the two only ever differ for
										     the moment between a reorder request being sent and its response
										     landing, since persist() (above) already applies the new order to
										     local state immediately either way. */ }
										<span className="hsrtech-row__number">{ sprintf( '%d.', index + 1 ) }</span>{ ' ' }
										{ chapter.title?.raw || chapter.title?.rendered || __( '(no title)', 'chapterwright' ) }
									</strong>
									<span className={ `hsrtech-status-pill hsrtech-status-pill--${ chapter.status }` }>{ chapter.status }</span>
									{ /* Mirrors the front-end table of contents' own "Show excerpt"
									     setting (window.hsrtechApp.showTocExcerpt, localized from
									     hsrtech_show_toc_excerpt() in admin/app.php) rather than always
									     showing it or adding a second, separate toggle for the same
									     thing — and unlike the front end, shown for a draft chapter too:
									     there's no reason to hide it here just because a chapter isn't
									     published yet, since this screen already manages every chapter
									     regardless of status. */ }
									{ window.hsrtechApp?.showTocExcerpt && chapter.excerpt && (
										<p className="hsrtech-row__meta">{ chapter.excerpt }</p>
									) }
								</div>
								<SelectControl
									__next40pxDefaultSize
									className="hsrtech-row__section-select"
									label={ __( 'Section', 'chapterwright' ) }
									hideLabelFromVision
									value={ String( chapter.meta?._hsrtech_section_id || '' ) }
									options={ sectionOptions }
									onChange={ ( value ) => changeSection( chapter.id, value ? Number( value ) : UNASSIGNED ) }
								/>
								<div className="hsrtech-row__actions">
									{ /* Comes from admin/rest/chapters.php's hsrtech_prepare_chapter_for_response(),
									     which already resolves to a preview link for a chapter
									     that isn't published yet — works the same regardless of status. */ }
									{ chapter.link && (
										<Button
											variant="tertiary"
											size="small"
											icon="external"
											label={ __( 'View', 'chapterwright' ) }
											href={ chapter.link }
											target="_blank"
											rel="noopener"
										/>
									) }
									<Button variant="tertiary" size="small" href={ editLink( chapter.id ) } target="_blank">
										{ __( 'Edit', 'chapterwright' ) }
									</Button>
									<Button variant="tertiary" isDestructive size="small" onClick={ () => removeChapter( chapter ) }>
										{ __( 'Trash', 'chapterwright' ) }
									</Button>
								</div>
							</div>
						) ) }
					</div>
				) }

				<form onSubmit={ addChapter } className="hsrtech-inline-form hsrtech-inline-form--chapter">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'New chapter title', 'chapterwright' ) }
						hideLabelFromVision
						placeholder={ __( 'New chapter title…', 'chapterwright' ) }
						value={ title }
						onChange={ setTitle }
					/>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Section', 'chapterwright' ) }
						hideLabelFromVision
						value={ newChapterSection }
						options={ sectionOptions }
						onChange={ setNewChapterSection }
					/>
					<Button
						__next40pxDefaultSize
						variant="primary"
						type="submit"
						isBusy={ busy }
						disabled={ busy || ! title.trim() }
					>
						{ __( '+ Add chapter', 'chapterwright' ) }
					</Button>
				</form>
			</CardBody>
		</Card>
	);
}

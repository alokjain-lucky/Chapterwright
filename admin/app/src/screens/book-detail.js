/**
 * Book detail screen: quick-edit subtitle/accent, manage sections, and
 * organize chapters (assign to a section, reorder, add, remove).
 *
 * Writing a chapter's or book's actual content still happens in the normal
 * block editor — this screen only manages the structural pieces (which
 * book, which section, what order) that used to live in scattered meta
 * boxes. "Edit content" links open post.php in a new tab for that reason.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
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
				<Button __next40pxDefaultSize variant="secondary" href={ editLink( book.id ) } target="_blank">
					{ __( 'Open in Block Editor →', 'chapterwright' ) }
				</Button>
			</div>

			<BookFields
				book={ book }
				onSaved={ ( updated ) => {
					setBook( updated );
					setNotice( __( 'Book details saved.', 'chapterwright' ) );
				} }
				onError={ setError }
			/>

			<SectionsManager
				bookId={ bookId }
				sections={ sections }
				onChange={ setSections }
				onError={ setError }
			/>

			<ChaptersManager
				bookId={ bookId }
				sections={ sections }
				chapters={ chapters }
				onChange={ setChapters }
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
function SectionsManager( { bookId, sections, onChange, onError } ) {
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
			.catch( ( err ) => onError( err.message || __( 'The section could not be saved.', 'chapterwright' ) ) )
			// Refresh either way, not just on success. If this section was
			// already deleted elsewhere (another tab, a previous request that
			// actually went through despite looking like it failed), the
			// server correctly rejects the edit — but the old code left the
			// stale section sitting in the list afterward with no way to
			// notice it was gone, so "Save" (or "Delete", just below) would
			// silently repeat the exact same failure forever. Refreshing
			// syncs the list to what the server actually has either way.
			.then( refresh );
	};

	const removeSection = ( section ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Delete "%s"? Its chapters will stay, unassigned.', 'chapterwright' ), section.name ) ) ) {
			return;
		}
		deleteSection( section.id )
			.catch( ( err ) => onError( err.message || __( 'The section could not be deleted.', 'chapterwright' ) ) )
			.then( refresh );
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
					{ __( 'Group chapters under a heading, such as "Part I" or "Getting Started". The description shows under the heading in the table of contents. Optional — chapters with no section appear under a default "Chapters" heading.', 'chapterwright' ) }
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

function SectionRow( { section, onSave, onDelete, onMoveUp, onMoveDown } ) {
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
					{ section.description && <p className="hsrtech-row__meta">{ section.description }</p> }
				</div>
				<div className="hsrtech-row__actions">
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
		reorderChapters( bookId, payload )
			.catch( ( err ) => onError( err.message || __( 'The chapter order could not be saved.', 'chapterwright' ) ) )
			.then( refresh );
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
			.catch( ( err ) => onError( err.message || __( 'The chapter could not be trashed.', 'chapterwright' ) ) )
			// Same reasoning as SectionsManager's removeSection/saveSection:
			// refresh regardless of outcome, so a chapter that's already
			// gone server-side doesn't stay stuck in the list looking
			// trashable when trying again would just repeat the same error.
			.then( refresh );
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
									<strong>{ chapter.title?.raw || chapter.title?.rendered || __( '(no title)', 'chapterwright' ) }</strong>
									<span className={ `hsrtech-status-pill hsrtech-status-pill--${ chapter.status }` }>{ chapter.status }</span>
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

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
	TextControl,
	TextareaControl,
	Spinner,
	Notice,
	SelectControl,
} from '@wordpress/components';
import {
	getBook,
	updateBook,
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

	const load = useCallback( () => {
		setError( '' );
		Promise.all( [ getBook( bookId ), getSections( bookId ), getBookChapters( bookId ) ] )
			.then( ( [ bookData, sectionData, chapterData ] ) => {
				setBook( bookData );
				setSections( sectionData );
				setChapters( chapterData );
			} )
			.catch( ( err ) => setError( err.message || __( 'This book could not be loaded.', 'make-a-book' ) ) );
	}, [ bookId ] );

	useEffect( load, [ load ] );

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false } className="mab-notice">
				{ error }
			</Notice>
		);
	}

	if ( ! book ) {
		return (
			<div className="mab-loading">
				<Spinner />
			</div>
		);
	}

	const adminUrl = window.makeABookApp?.adminUrl || '/wp-admin/';
	const editLink = ( postId ) => `${ adminUrl }post.php?post=${ postId }&action=edit`;

	return (
		<div className="mab-book-detail">
			<a className="mab-back-link" href="#/books">
				<span aria-hidden="true">&larr;</span> { __( 'All books', 'make-a-book' ) }
			</a>

			{ notice && (
				<Notice status="success" className="mab-notice" onRemove={ () => setNotice( '' ) }>
					{ notice }
				</Notice>
			) }

			<div className="mab-book-detail__title-row">
				<h2 className="mab-book-detail__title">{ book.title?.raw || book.title?.rendered }</h2>
				<Button __next40pxDefaultSize variant="secondary" href={ editLink( book.id ) } target="_blank">
					{ __( 'Edit content, cover & excerpt →', 'make-a-book' ) }
				</Button>
			</div>

			<BookFields
				book={ book }
				onSaved={ ( updated ) => {
					setBook( updated );
					setNotice( __( 'Book details saved.', 'make-a-book' ) );
				} }
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
		</div>
	);
}

/**
 * Subtitle + accent color quick-edit, replacing the old "Book Details" meta box.
 */
function BookFields( { book, onSaved } ) {
	const [ subtitle, setSubtitle ] = useState( book.meta?._mab_subtitle || '' );
	const [ accent, setAccent ] = useState( book.meta?._mab_accent || '#f45d48' );
	const [ saving, setSaving ] = useState( false );

	const save = () => {
		setSaving( true );
		updateBook( book.id, { meta: { _mab_subtitle: subtitle, _mab_accent: accent } } )
			.then( ( updated ) => {
				setSaving( false );
				onSaved( updated );
			} )
			.catch( () => setSaving( false ) );
	};

	return (
		<Card className="mab-panel">
			<CardHeader>
				<h3 className="mab-panel__title">{ __( 'Book details', 'make-a-book' ) }</h3>
			</CardHeader>
			<CardBody>
				<div className="mab-field-row">
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Subtitle', 'make-a-book' ) }
						value={ subtitle }
						onChange={ setSubtitle }
						rows={ 2 }
					/>
				</div>
				<div className="mab-field-row">
					<BaseControl
						__nextHasNoMarginBottom
						id="mab-accent-input"
						label={ __( 'Accent color', 'make-a-book' ) }
					>
						<input
							id="mab-accent-input"
							className="mab-color-input"
							type="color"
							value={ accent }
							onChange={ ( event ) => setAccent( event.target.value ) }
						/>
					</BaseControl>
				</div>
				<Button
					__next40pxDefaultSize
					variant="primary"
					isBusy={ saving }
					disabled={ saving }
					onClick={ save }
				>
					{ __( 'Save book details', 'make-a-book' ) }
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
			.then( () => {
				setName( '' );
				setDescription( '' );
				setBusy( false );
				refresh();
			} )
			.catch( ( err ) => {
				setBusy( false );
				onError( err.message );
			} );
	};

	const saveSection = ( section, fields ) => {
		updateSection( section.id, fields ).then( refresh ).catch( ( err ) => onError( err.message ) );
	};

	const removeSection = ( section ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Delete "%s"? Its chapters will stay, unassigned.', 'make-a-book' ), section.name ) ) ) {
			return;
		}
		deleteSection( section.id ).then( refresh ).catch( ( err ) => onError( err.message ) );
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
			.catch( ( err ) => onError( err.message ) );
	};

	return (
		<Card className="mab-panel">
			<CardHeader>
				<h3 className="mab-panel__title">{ __( 'Sections', 'make-a-book' ) }</h3>
			</CardHeader>
			<CardBody>
				<p className="mab-panel__description">
					{ __( 'Group chapters under a heading, such as "Part I" or "Getting Started". The description shows under the heading in the table of contents. Optional — chapters with no section appear under a default "Chapters" heading.', 'make-a-book' ) }
				</p>

				{ 0 === sections.length && (
					<p className="mab-empty-state mab-empty-state--inline">
						{ __( 'No sections yet — chapters will appear under a default "Chapters" heading until you add one.', 'make-a-book' ) }
					</p>
				) }

				{ sections.length > 0 && (
					<div className="mab-row-list">
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

				<form onSubmit={ addSection } className="mab-inline-form mab-inline-form--section">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'New section name', 'make-a-book' ) }
						hideLabelFromVision
						placeholder={ __( 'e.g. Getting Started', 'make-a-book' ) }
						value={ name }
						onChange={ setName }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'New section description', 'make-a-book' ) }
						hideLabelFromVision
						placeholder={ __( 'Optional description', 'make-a-book' ) }
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
						{ __( 'Add section', 'make-a-book' ) }
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
			<div className="mab-row">
				<div className="mab-row__reorder">
					<Button icon="arrow-up-alt2" label={ __( 'Move up', 'make-a-book' ) } onClick={ onMoveUp } disabled={ ! onMoveUp } size="small" />
					<Button icon="arrow-down-alt2" label={ __( 'Move down', 'make-a-book' ) } onClick={ onMoveDown } disabled={ ! onMoveDown } size="small" />
				</div>
				<div className="mab-row__text">
					<strong>{ section.name }</strong>
					{ section.description && <p className="mab-row__meta">{ section.description }</p> }
				</div>
				<div className="mab-row__actions">
					<Button variant="tertiary" size="small" onClick={ () => setEditing( true ) }>{ __( 'Edit', 'make-a-book' ) }</Button>
					<Button variant="tertiary" isDestructive size="small" onClick={ onDelete }>{ __( 'Delete', 'make-a-book' ) }</Button>
				</div>
			</div>
		);
	}

	return (
		<div className="mab-row mab-row--editing">
			<TextControl __next40pxDefaultSize label={ __( 'Name', 'make-a-book' ) } value={ name } onChange={ setName } />
			<TextareaControl label={ __( 'Description', 'make-a-book' ) } value={ description } onChange={ setDescription } rows={ 2 } />
			<div className="mab-row__actions">
				<Button
					__next40pxDefaultSize
					variant="primary"
					size="small"
					onClick={ () => {
						onSave( { name, description } );
						setEditing( false );
					} }
				>
					{ __( 'Save', 'make-a-book' ) }
				</Button>
				<Button variant="tertiary" size="small" onClick={ () => setEditing( false ) }>{ __( 'Cancel', 'make-a-book' ) }</Button>
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
			sectionId: chapter.meta?._mab_section_id || UNASSIGNED,
			order: index + 1,
		} ) );
		reorderChapters( bookId, payload ).then( refresh ).catch( ( err ) => onError( err.message ) );
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
				? { ...chapter, meta: { ...chapter.meta, _mab_section_id: sectionId } }
				: chapter
		);
		persist( updated );
	};

	const removeChapter = ( chapter ) => {
		const chapterTitle = chapter.title?.raw || chapter.title?.rendered || '';
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Move "%s" to the trash?', 'make-a-book' ), chapterTitle ) ) ) {
			return;
		}
		trashChapter( chapter.id ).then( refresh ).catch( ( err ) => onError( err.message ) );
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
				setBusy( false );
				refresh();
				window.open( editLink( chapter.id ), '_blank' );
			} )
			.catch( ( err ) => {
				setBusy( false );
				onError( err.message );
			} );
	};

	const sectionOptions = [
		{ label: __( 'Chapters (no section)', 'make-a-book' ), value: '' },
		...sections.map( ( section ) => ( { label: section.name, value: String( section.id ) } ) ),
	];

	return (
		<Card className="mab-panel">
			<CardHeader>
				<h3 className="mab-panel__title">{ __( 'Chapters', 'make-a-book' ) }</h3>
			</CardHeader>
			<CardBody>
				{ 0 === chapters.length && (
					<p className="mab-empty-state mab-empty-state--inline">
						{ __( 'No chapters yet. Add the first one below.', 'make-a-book' ) }
					</p>
				) }

				{ chapters.length > 0 && (
					<div className="mab-row-list">
						{ chapters.map( ( chapter, index ) => (
							<div className="mab-row mab-row--chapter" key={ chapter.id }>
								<div className="mab-row__reorder">
									<Button icon="arrow-up-alt2" label={ __( 'Move up', 'make-a-book' ) } size="small" disabled={ 0 === index } onClick={ () => move( index, -1 ) } />
									<Button icon="arrow-down-alt2" label={ __( 'Move down', 'make-a-book' ) } size="small" disabled={ index === chapters.length - 1 } onClick={ () => move( index, 1 ) } />
								</div>
								<div className="mab-row__text">
									<a href={ editLink( chapter.id ) } target="_blank" rel="noreferrer">
										{ chapter.title?.raw || chapter.title?.rendered || __( '(no title)', 'make-a-book' ) }
									</a>
									{ 'publish' !== chapter.status && (
										<span className={ `mab-status-pill mab-status-pill--${ chapter.status }` }>{ chapter.status }</span>
									) }
								</div>
								<SelectControl
									__next40pxDefaultSize
									className="mab-row__section-select"
									label={ __( 'Section', 'make-a-book' ) }
									hideLabelFromVision
									value={ String( chapter.meta?._mab_section_id || '' ) }
									options={ sectionOptions }
									onChange={ ( value ) => changeSection( chapter.id, value ? Number( value ) : UNASSIGNED ) }
								/>
								<div className="mab-row__actions">
									<Button variant="tertiary" isDestructive size="small" onClick={ () => removeChapter( chapter ) }>
										{ __( 'Trash', 'make-a-book' ) }
									</Button>
								</div>
							</div>
						) ) }
					</div>
				) }

				<form onSubmit={ addChapter } className="mab-inline-form mab-inline-form--chapter">
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'New chapter title', 'make-a-book' ) }
						hideLabelFromVision
						placeholder={ __( 'New chapter title…', 'make-a-book' ) }
						value={ title }
						onChange={ setTitle }
					/>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Section', 'make-a-book' ) }
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
						{ __( '+ Add chapter', 'make-a-book' ) }
					</Button>
				</form>
			</CardBody>
		</Card>
	);
}

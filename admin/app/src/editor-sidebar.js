/**
 * Block editor sidebar panel for Book and Chapter meta.
 *
 * Replaces the classic add_meta_box() panels this plugin used before
 * 2.0.0. Structural organizing (which section, chapter order, moving
 * chapters between books) is better done from the admin app's Book detail
 * screen, which sees every chapter in a book at once — but the fields still
 * need to exist here too, since an author editing a chapter's content in
 * the block editor directly (rather than arriving from the admin app)
 * should still be able to see and change them without leaving the editor.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp, useEntityRecords } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseControl, TextareaControl, SelectControl, TextControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import './editor-sidebar.css';

const BOOK_POST_TYPE = 'mab_book';
const CHAPTER_POST_TYPE = 'mab_chapter';

function BookPanel() {
	// useEntityProp() can return `undefined` for `meta` on the very first
	// render, before the post entity has finished resolving — reading
	// meta._mab_subtitle below would throw and crash this whole sidebar
	// (Gutenberg's plugin error boundary is what the user actually sees:
	// "The 'make-a-book' plugin has encountered an error"). Defaulting to
	// {} here is required, not just tidy.
	const [ meta = {}, setMeta ] = useEntityProp( 'postType', BOOK_POST_TYPE, 'meta' );

	return (
		<PluginDocumentSettingPanel name="mab-book-details" title={ __( 'Book Details', 'make-a-book' ) }>
			<TextareaControl
				label={ __( 'Subtitle', 'make-a-book' ) }
				value={ meta._mab_subtitle || '' }
				onChange={ ( value ) => setMeta( { ...meta, _mab_subtitle: value } ) }
				rows={ 3 }
			/>
			<BaseControl id="mab-sidebar-accent" label={ __( 'Accent color', 'make-a-book' ) }>
				<input
					id="mab-sidebar-accent"
					className="mab-color-input"
					type="color"
					value={ meta._mab_accent || '#f45d48' }
					onChange={ ( event ) => setMeta( { ...meta, _mab_accent: event.target.value } ) }
				/>
			</BaseControl>
			<p className="description">
				{ __( 'Manage this book\'s sections and chapter order from the Make a Book admin page.', 'make-a-book' ) }
			</p>
		</PluginDocumentSettingPanel>
	);
}

function ChapterPanel() {
	// See the comment in BookPanel() above — same reason for the default.
	const [ meta = {}, setMeta ] = useEntityProp( 'postType', CHAPTER_POST_TYPE, 'meta' );
	const [ sections, setSections ] = useState( [] );

	const { records: books } = useEntityRecords( 'postType', BOOK_POST_TYPE, {
		per_page: -1,
		status: [ 'publish', 'draft', 'pending', 'private', 'future' ],
		orderby: 'title',
		order: 'asc',
	} );

	const bookId = meta._mab_book_id || 0;

	useEffect( () => {
		if ( ! bookId ) {
			setSections( [] );
			return;
		}
		apiFetch( { path: `/make-a-book/v1/books/${ bookId }/sections` } )
			.then( setSections )
			.catch( () => setSections( [] ) );
	}, [ bookId ] );

	const bookOptions = [
		{ label: __( 'Select a book', 'make-a-book' ), value: '' },
		...( books || [] ).map( ( book ) => ( { label: book.title?.rendered || book.title?.raw, value: String( book.id ) } ) ),
	];

	const sectionOptions = [
		{ label: __( 'No section', 'make-a-book' ), value: '' },
		...sections.map( ( section ) => ( { label: section.name, value: String( section.id ) } ) ),
	];

	return (
		<PluginDocumentSettingPanel name="mab-chapter-details" title={ __( 'Chapter Details', 'make-a-book' ) }>
			<SelectControl
				label={ __( 'Book', 'make-a-book' ) }
				value={ String( bookId || '' ) }
				options={ bookOptions }
				onChange={ ( value ) =>
					setMeta( { ...meta, _mab_book_id: value ? Number( value ) : 0, _mab_section_id: 0 } )
				}
			/>
			<SelectControl
				label={ __( 'Section', 'make-a-book' ) }
				value={ String( meta._mab_section_id || '' ) }
				options={ sectionOptions }
				disabled={ ! bookId }
				onChange={ ( value ) => setMeta( { ...meta, _mab_section_id: value ? Number( value ) : 0 } ) }
			/>
			<TextControl
				label={ __( 'Chapter number / order', 'make-a-book' ) }
				type="number"
				min="0"
				value={ meta._mab_order || '' }
				onChange={ ( value ) => setMeta( { ...meta, _mab_order: value ? Number( value ) : 0 } ) }
			/>
			<p className="description">
				{ __( 'Add and reorder chapters more easily from the Make a Book admin page.', 'make-a-book' ) }
			</p>
		</PluginDocumentSettingPanel>
	);
}

function MakeABookSidebar() {
	const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );

	if ( BOOK_POST_TYPE === postType ) {
		return <BookPanel />;
	}

	if ( CHAPTER_POST_TYPE === postType ) {
		return <ChapterPanel />;
	}

	return null;
}

registerPlugin( 'make-a-book', { render: MakeABookSidebar } );

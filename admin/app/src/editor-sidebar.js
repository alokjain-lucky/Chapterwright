/**
 * Block editor sidebar panel for Book and Chapter meta.
 *
 * Replaces the classic add_meta_box() panels this plugin used before
 * 2.0.0. `BookPanel` keeps its two content fields (Subtitle, Accent color)
 * fully editable here — same as in the admin app's Book details panel; both
 * are just two views onto one book's own fields, so duplication is fine.
 *
 * `ChapterPanel` is different, as of 2.3.2: Book, Section, and Order are
 * shown read-only, not as editable controls. These are structural,
 * book-wide relationships (which book, where in it) that the admin app's
 * Book detail screen already manages well, since it can see every chapter
 * and section in a book at once to keep order and section assignment sane.
 * An editable copy of the same fields here was found confusing in practice
 * — with the book already correctly assigned, the control still looked like
 * an empty "please choose one" dropdown, and changing it here bypassed the
 * app's book-wide view entirely. See the 2.3.2 note under Notable history
 * in AGENTS.md for the reasoning in full.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseControl, TextareaControl } from '@wordpress/components';
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
			<BaseControl
				id="mab-sidebar-accent"
				label={ __( 'Accent color', 'make-a-book' ) }
				help={ __( 'Colors links, hover states, blockquote/callout borders, and the reading-progress bar on this book’s pages.', 'make-a-book' ) }
			>
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
	const [ meta = {} ] = useEntityProp( 'postType', CHAPTER_POST_TYPE, 'meta' );
	const [ sections, setSections ] = useState( [] );
	const [ bookTitle, setBookTitle ] = useState( '' );

	const bookId = meta._mab_book_id || 0;

	// Which book this chapter belongs to, its section, and its reading order
	// are shown here read-only, not editable — reassigning a chapter to a
	// different book or section is structural, book-wide work the admin
	// app's Book detail screen already does well (it can see every chapter
	// and section in the book at once to keep order sane), and duplicating
	// that as an editable control here risked exactly the confusion an
	// explicit report raised: it looked like an empty "make a selection"
	// control even for a chapter the admin app had already assigned
	// correctly, since nothing here visually distinguished "already set" from
	// "please choose one." Showing the current values as plain text next to
	// a link back to the admin app is unambiguous either way.
	useEffect( () => {
		if ( ! bookId ) {
			setSections( [] );
			setBookTitle( '' );
			return;
		}
		apiFetch( { path: `/wp/v2/mab_book/${ bookId }?_fields=id,title` } )
			.then( ( book ) => setBookTitle( book.title?.rendered || book.title?.raw || '' ) )
			.catch( () => setBookTitle( '' ) );
		apiFetch( { path: `/make-a-book/v1/books/${ bookId }/sections` } )
			.then( setSections )
			.catch( () => setSections( [] ) );
	}, [ bookId ] );

	const adminUrl = window.makeABookApp?.adminUrl || '/wp-admin/';
	const section = sections.find( ( candidate ) => candidate.id === Number( meta._mab_section_id ) );

	return (
		<PluginDocumentSettingPanel name="mab-chapter-details" title={ __( 'Chapter Details', 'make-a-book' ) }>
			{ bookId ? (
				<>
					<p className="mab-sidebar-field">
						<strong>{ __( 'Book:', 'make-a-book' ) }</strong>{ ' ' }
						{ bookTitle || __( '…', 'make-a-book' ) }
					</p>
					<p className="mab-sidebar-field">
						<strong>{ __( 'Section:', 'make-a-book' ) }</strong>{ ' ' }
						{ section ? section.name : __( 'None', 'make-a-book' ) }
					</p>
					<p className="mab-sidebar-field">
						<strong>{ __( 'Order:', 'make-a-book' ) }</strong>{ ' ' }
						{ meta._mab_order || '—' }
					</p>
					<p className="description">
						<a href={ `${ adminUrl }admin.php?page=make-a-book#/books/${ bookId }` }>
							{ __( 'Change book, section, or order →', 'make-a-book' ) }
						</a>
					</p>
				</>
			) : (
				<p className="description">
					{ __( 'This chapter isn\'t assigned to a book yet. Add it to a book from the Make a Book admin page.', 'make-a-book' ) }
				</p>
			) }
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

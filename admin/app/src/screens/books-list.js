/**
 * Books overview screen: every book as a card, plus a quick "Add Book" form.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button, Card, CardBody, CardFooter, TextControl, Spinner, Notice } from '@wordpress/components';
import { getBooks, createBook, trashBook } from '../api';

export default function BooksList() {
	const [ books, setBooks ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ newTitle, setNewTitle ] = useState( '' );
	const [ creating, setCreating ] = useState( false );

	const loadBooks = useCallback( () => {
		setError( '' );
		getBooks()
			.then( setBooks )
			.catch( ( err ) => setError( err.message || __( 'Books could not be loaded.', 'chapterwright' ) ) );
	}, [] );

	useEffect( loadBooks, [ loadBooks ] );

	const handleTrash = ( book ) => {
		const title = book.title?.raw || book.title?.rendered || '';
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Move "%s" to the trash? Its chapters and sections are not affected — restore the book from Trash to bring it back.', 'chapterwright' ), title ) ) ) {
			return;
		}
		trashBook( book.id )
			.then( loadBooks )
			.catch( ( err ) => setError( err.message || __( 'The book could not be trashed.', 'chapterwright' ) ) );
	};

	const handleCreate = ( event ) => {
		event.preventDefault();
		if ( ! newTitle.trim() ) {
			return;
		}
		setCreating( true );
		createBook( newTitle.trim() )
			.then( ( book ) => {
				setNewTitle( '' );
				setCreating( false );
				window.location.hash = `#/books/${ book.id }`;
			} )
			.catch( ( err ) => {
				setCreating( false );
				setError( err.message || __( 'The book could not be created.', 'chapterwright' ) );
			} );
	};

	return (
		<div className="hsrtech-books-list">
			{ error && (
				<Notice status="error" className="hsrtech-notice" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }

			<Card className="hsrtech-panel hsrtech-add-book">
				<CardBody>
					<form onSubmit={ handleCreate } className="hsrtech-inline-form">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'New book title', 'chapterwright' ) }
							hideLabelFromVision
							placeholder={ __( 'New book title…', 'chapterwright' ) }
							value={ newTitle }
							onChange={ setNewTitle }
						/>
						<Button
							__next40pxDefaultSize
							variant="primary"
							type="submit"
							isBusy={ creating }
							disabled={ creating || ! newTitle.trim() }
						>
							{ __( 'Add Book', 'chapterwright' ) }
						</Button>
					</form>
				</CardBody>
			</Card>

			{ null === books && (
				<div className="hsrtech-loading">
					<Spinner />
				</div>
			) }

			{ books && 0 === books.length && (
				<div className="hsrtech-empty-state">
					<p>{ __( 'No books yet. Add your first one above.', 'chapterwright' ) }</p>
				</div>
			) }

			{ books && books.length > 0 && (
				<>
					<p className="hsrtech-books-list__count">
						{ sprintf(
							/* translators: %d: number of books. */
							_n( '%d book', '%d books', books.length, 'chapterwright' ),
							books.length
						) }
					</p>
					<div className="hsrtech-book-grid">
						{ books.map( ( book ) => (
							<Card key={ book.id } className="hsrtech-panel hsrtech-book-card">
								<a className="hsrtech-book-card__cover-link" href={ `#/books/${ book.id }` } tabIndex={ -1 } aria-hidden="true">
									{ book._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url ? (
										<img
											className="hsrtech-book-card__cover"
											src={ book._embedded[ 'wp:featuredmedia' ][ 0 ].source_url }
											alt=""
										/>
									) : (
										<span className="hsrtech-book-card__cover hsrtech-book-card__cover--placeholder">
											<span className="dashicons dashicons-book-alt" aria-hidden="true"></span>
										</span>
									) }
								</a>
								<CardBody>
									<h2 className="hsrtech-book-card__title">
										<a href={ `#/books/${ book.id }` }>
											{ book.title?.raw || book.title?.rendered || __( '(no title)', 'chapterwright' ) }
										</a>
									</h2>
									{ 'publish' !== book.status && (
										<span className={ `hsrtech-status-pill hsrtech-status-pill--${ book.status }` }>{ book.status }</span>
									) }
									{ book.meta?._hsrtech_coming_soon && (
										<span className="hsrtech-status-pill hsrtech-status-pill--coming-soon">
											{ __( 'Coming soon', 'chapterwright' ) }
										</span>
									) }
								</CardBody>
								<CardFooter>
									<Button __next40pxDefaultSize variant="secondary" href={ `#/books/${ book.id }` }>
										{ __( 'Manage', 'chapterwright' ) }
									</Button>
									<Button __next40pxDefaultSize variant="tertiary" isDestructive onClick={ () => handleTrash( book ) }>
										{ __( 'Trash', 'chapterwright' ) }
									</Button>
								</CardFooter>
							</Card>
						) ) }
					</div>

					<p className="hsrtech-books-list__trash-link">
						<a href={ `${ window.hsrtechApp?.adminUrl || '/wp-admin/' }edit.php?post_type=hsrtech_book&post_status=trash` }>
							{ __( 'View trashed books →', 'chapterwright' ) }
						</a>
					</p>
				</>
			) }
		</div>
	);
}

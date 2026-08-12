/**
 * Books overview screen: every book as a card, plus a quick "Add Book" form.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button, Card, CardBody, CardFooter, TextControl, Spinner, Notice } from '@wordpress/components';
import { getBooks, createBook } from '../api';

export default function BooksList() {
	const [ books, setBooks ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ newTitle, setNewTitle ] = useState( '' );
	const [ creating, setCreating ] = useState( false );

	const loadBooks = useCallback( () => {
		setError( '' );
		getBooks()
			.then( setBooks )
			.catch( ( err ) => setError( err.message || __( 'Books could not be loaded.', 'make-a-book' ) ) );
	}, [] );

	useEffect( loadBooks, [ loadBooks ] );

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
				setError( err.message || __( 'The book could not be created.', 'make-a-book' ) );
			} );
	};

	return (
		<div className="mab-books-list">
			{ error && (
				<Notice status="error" isDismissible={ false } className="mab-notice">
					{ error }
				</Notice>
			) }

			<Card className="mab-panel mab-add-book">
				<CardBody>
					<form onSubmit={ handleCreate } className="mab-inline-form">
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'New book title', 'make-a-book' ) }
							hideLabelFromVision
							placeholder={ __( 'New book title…', 'make-a-book' ) }
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
							{ __( 'Add Book', 'make-a-book' ) }
						</Button>
					</form>
				</CardBody>
			</Card>

			{ null === books && (
				<div className="mab-loading">
					<Spinner />
				</div>
			) }

			{ books && 0 === books.length && (
				<div className="mab-empty-state">
					<p>{ __( 'No books yet. Add your first one above.', 'make-a-book' ) }</p>
				</div>
			) }

			{ books && books.length > 0 && (
				<>
					<p className="mab-books-list__count">
						{ sprintf(
							/* translators: %d: number of books. */
							_n( '%d book', '%d books', books.length, 'make-a-book' ),
							books.length
						) }
					</p>
					<div className="mab-book-grid">
						{ books.map( ( book ) => (
							<Card key={ book.id } className="mab-panel mab-book-card">
								<a className="mab-book-card__cover-link" href={ `#/books/${ book.id }` } tabIndex={ -1 } aria-hidden="true">
									{ book._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url ? (
										<img
											className="mab-book-card__cover"
											src={ book._embedded[ 'wp:featuredmedia' ][ 0 ].source_url }
											alt=""
										/>
									) : (
										<span className="mab-book-card__cover mab-book-card__cover--placeholder">
											<span className="dashicons dashicons-book-alt" aria-hidden="true"></span>
										</span>
									) }
								</a>
								<CardBody>
									<h2 className="mab-book-card__title">
										<a href={ `#/books/${ book.id }` }>
											{ book.title?.raw || book.title?.rendered || __( '(no title)', 'make-a-book' ) }
										</a>
									</h2>
									{ 'publish' !== book.status && (
										<span className={ `mab-status-pill mab-status-pill--${ book.status }` }>{ book.status }</span>
									) }
								</CardBody>
								<CardFooter>
									<Button __next40pxDefaultSize variant="secondary" href={ `#/books/${ book.id }` }>
										{ __( 'Manage', 'make-a-book' ) }
									</Button>
								</CardFooter>
							</Card>
						) ) }
					</div>
				</>
			) }
		</div>
	);
}

/**
 * Books overview screen: every book as a card, plus a quick "Add Book" form.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

			<Card className="mab-add-book">
				<CardBody>
					<form onSubmit={ handleCreate } className="mab-add-book__form">
						<TextControl
							label={ __( 'New book title', 'make-a-book' ) }
							hideLabelFromVision
							placeholder={ __( 'New book title…', 'make-a-book' ) }
							value={ newTitle }
							onChange={ setNewTitle }
						/>
						<Button variant="primary" type="submit" isBusy={ creating } disabled={ creating || ! newTitle.trim() }>
							{ __( 'Add Book', 'make-a-book' ) }
						</Button>
					</form>
				</CardBody>
			</Card>

			{ null === books && <Spinner /> }

			{ books && 0 === books.length && (
				<p>{ __( 'No books yet. Add your first one above.', 'make-a-book' ) }</p>
			) }

			{ books && books.length > 0 && (
				<div className="mab-book-grid">
					{ books.map( ( book ) => (
						<Card key={ book.id } className="mab-book-card">
							{ book._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url && (
								<img
									className="mab-book-card__cover"
									src={ book._embedded[ 'wp:featuredmedia' ][ 0 ].source_url }
									alt=""
								/>
							) }
							<CardBody>
								<h2 className="mab-book-card__title">
									<a href={ `#/books/${ book.id }` }>
										{ book.title?.raw || book.title?.rendered || __( '(no title)', 'make-a-book' ) }
									</a>
								</h2>
								{ 'publish' !== book.status && (
									<span className="mab-book-card__status">{ book.status }</span>
								) }
							</CardBody>
							<CardFooter>
								<Button variant="secondary" href={ `#/books/${ book.id }` }>
									{ __( 'Manage', 'make-a-book' ) }
								</Button>
							</CardFooter>
						</Card>
					) ) }
				</div>
			) }
		</div>
	);
}

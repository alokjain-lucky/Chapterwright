/**
 * Books overview screen: every book as a card, plus a quick "Add Book" form.
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button, Card, CardBody, CardFooter, TextControl, Spinner, Notice } from '@wordpress/components';
import { getBooks, createBook, trashBook } from '../api';

const REFRESH_COOLDOWN_MS = 8000;

export default function BooksList() {
	const [ books, setBooks ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ newTitle, setNewTitle ] = useState( '' );
	const [ creating, setCreating ] = useState( false );

	// Timestamp of the most recent local change (currently just trashing a
	// book), via markBooks() below — used to skip a focus-triggered re-sync
	// for a few seconds afterward. Same reasoning as book-detail.js's
	// identical guard: an immediate re-fetch right after a write can lag
	// behind on this host, and a focus event landing in that window would
	// otherwise clobber a correct optimistic update with a stale read.
	const lastMutatedAtRef = useRef( 0 );
	const markBooks = useCallback( ( updater ) => {
		lastMutatedAtRef.current = Date.now();
		setBooks( updater );
	}, [] );

	const loadBooks = useCallback( () => {
		setError( '' );
		getBooks()
			.then( setBooks )
			.catch( ( err ) => setError( err.message || __( 'Books could not be loaded.', 'chapterwright' ) ) );
	}, [] );

	useEffect( loadBooks, [ loadBooks ] );

	// Re-sync whenever this tab regains focus, so a book renamed via "Open in
	// Block Editor →" (book-detail.js) in another tab shows its new title
	// here without a manual reload — same reasoning as the identical effect
	// in book-detail.js. Gated by the cooldown above for the same reason
	// book-detail.js's version is.
	useEffect( () => {
		const onFocus = () => {
			if ( Date.now() - lastMutatedAtRef.current < REFRESH_COOLDOWN_MS ) {
				return;
			}
			loadBooks();
		};
		window.addEventListener( 'focus', onFocus );
		return () => window.removeEventListener( 'focus', onFocus );
	}, [ loadBooks ] );

	// Re-fetch the list without clearing whatever error is already showing —
	// loadBooks() itself starts with setError(''), which would immediately
	// wipe out a trash failure's message the instant this runs. Used below
	// to sync the list to the server after a failed trash, silently, since
	// there's already a more specific error on screen explaining what
	// happened.
	const refreshBooksQuietly = () => getBooks().then( setBooks ).catch( () => {} );

	const handleTrash = ( book ) => {
		const title = book.title?.raw || book.title?.rendered || '';
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( sprintf( __( 'Move "%s" to the trash? Its chapters and sections are not affected — restore the book from Trash to bring it back.', 'chapterwright' ), title ) ) ) {
			return;
		}
		trashBook( book.id )
			.then( () => {
				// Remove it locally rather than refreshing — an immediate
				// re-fetch right after the write can lag behind on this
				// host and show the just-trashed book for a moment longer,
				// which reads as "trash didn't work."
				markBooks( ( previous ) => ( previous || [] ).filter( ( b ) => b.id !== book.id ) );
			} )
			.catch( ( err ) => {
				setError( err.message || __( 'The book could not be trashed.', 'chapterwright' ) );
				// Same fix as book-detail.js's section/chapter actions: a
				// book that's already gone server-side (deleted elsewhere,
				// already trashed) would otherwise stay stuck in this list
				// forever, with every retry hitting the same error and
				// nothing visibly changing.
				refreshBooksQuietly();
			} );
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
									{ /* Icon-only, same as the reorder arrows elsewhere in this app —
									     opens the book's own front-end page in a new tab so an author
									     can check how it actually looks without leaving this list.
									     book.link comes straight from the core /wp/v2/hsrtech_book REST
									     response; hsrtech_rest_prepare_book_view_link() (content-types.php)
									     swaps in a preview link for a book that isn't published yet, so
									     this works the same way regardless of status. */ }
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
									<Button __next40pxDefaultSize variant="tertiary" isDestructive onClick={ () => handleTrash( book ) }>
										{ __( 'Trash', 'chapterwright' ) }
									</Button>
								</CardFooter>
							</Card>
						) ) }
					</div>

					<p className="hsrtech-books-list__trash-link">
						<a href="#/books/trash">
							{ __( 'View trashed books →', 'chapterwright' ) }
						</a>
					</p>
				</>
			) }
		</div>
	);
}

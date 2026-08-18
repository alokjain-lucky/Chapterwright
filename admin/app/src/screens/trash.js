/**
 * Trashed books screen: restore or permanently delete, without leaving the
 * admin app.
 *
 * Before this screen, the books list's "View trashed books" link sent
 * authors out to wp-admin's own Books list table filtered to
 * `post_status=trash` — a context switch to a screen this plugin doesn't
 * otherwise use, styled and laid out completely differently from the rest of
 * this app. This screen covers the same two actions (restore, permanently
 * delete) in place.
 */

import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody, Spinner, Notice } from '@wordpress/components';
import { getTrashedBooks, restoreBook, deleteBookPermanently } from '../api';

export default function TrashedBooks() {
	const [ books, setBooks ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ busyId, setBusyId ] = useState( null );

	const load = () => {
		setError( '' );
		getTrashedBooks()
			.then( setBooks )
			.catch( ( err ) => setError( err.message || __( 'Trashed books could not be loaded.', 'chapterwright' ) ) );
	};

	useEffect( load, [] );

	const handleRestore = ( book ) => {
		setBusyId( book.id );
		restoreBook( book.id )
			.then( () => {
				setBusyId( null );
				// Remove it locally rather than reloading the whole trash
				// list — same reasoning as every other action in this app
				// (see book-detail.js/books-list.js): the request already
				// confirms it succeeded, so there's nothing an immediate
				// re-fetch would add except a chance to show it as still
				// trashed for a moment (or longer, behind a cache — see the
				// _ts cache-busting comment in api.js).
				setBooks( ( previous ) => ( previous || [] ).filter( ( b ) => b.id !== book.id ) );
			} )
			.catch( ( err ) => {
				setBusyId( null );
				setError( err.message || __( 'The book could not be restored.', 'chapterwright' ) );
			} );
	};

	const handleDeletePermanently = ( book ) => {
		const title = book.title?.raw || book.title?.rendered || '';
		// eslint-disable-next-line no-alert
		if (
			! window.confirm(
				sprintf(
					__( 'Permanently delete "%s"? Its chapters and sections are not affected, but this cannot be undone.', 'chapterwright' ),
					title
				)
			)
		) {
			return;
		}
		setBusyId( book.id );
		deleteBookPermanently( book.id )
			.then( () => {
				setBusyId( null );
				setBooks( ( previous ) => ( previous || [] ).filter( ( b ) => b.id !== book.id ) );
			} )
			.catch( ( err ) => {
				setBusyId( null );
				setError( err.message || __( 'The book could not be permanently deleted.', 'chapterwright' ) );
			} );
	};

	return (
		<div className="hsrtech-trash">
			<a className="hsrtech-back-link" href="#/books">
				<span aria-hidden="true">&larr;</span> { __( 'All books', 'chapterwright' ) }
			</a>

			{ error && (
				<Notice status="error" className="hsrtech-notice" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }

			<Card className="hsrtech-panel">
				<CardHeader>
					<h3 className="hsrtech-panel__title">{ __( 'Trashed books', 'chapterwright' ) }</h3>
				</CardHeader>
				<CardBody>
					<p className="hsrtech-panel__description">
						{ __( 'Books moved to the trash. Their chapters and sections are kept, unaffected — restore a book to bring it back, or delete it permanently.', 'chapterwright' ) }
					</p>

					{ null === books && (
						<div className="hsrtech-loading">
							<Spinner />
						</div>
					) }

					{ books && 0 === books.length && (
						<p className="hsrtech-empty-state hsrtech-empty-state--inline">
							{ __( 'Nothing in the trash.', 'chapterwright' ) }
						</p>
					) }

					{ books && books.length > 0 && (
						<div className="hsrtech-row-list">
							{ books.map( ( book ) => (
								<div className="hsrtech-row" key={ book.id }>
									<div className="hsrtech-row__text">
										<strong>{ book.title?.raw || book.title?.rendered || __( '(no title)', 'chapterwright' ) }</strong>
										{ book.trashed?.display && (
											<p className="hsrtech-row__meta">
												{ sprintf( __( 'Trashed on %s', 'chapterwright' ), book.trashed.display ) }
											</p>
										) }
									</div>
									<div className="hsrtech-row__actions">
										<Button
											__next40pxDefaultSize
											variant="secondary"
											size="small"
											isBusy={ busyId === book.id }
											disabled={ null !== busyId }
											onClick={ () => handleRestore( book ) }
										>
											{ __( 'Restore', 'chapterwright' ) }
										</Button>
										<Button
											__next40pxDefaultSize
											variant="tertiary"
											isDestructive
											size="small"
											disabled={ null !== busyId }
											onClick={ () => handleDeletePermanently( book ) }
										>
											{ __( 'Delete permanently', 'chapterwright' ) }
										</Button>
									</div>
								</div>
							) ) }
						</div>
					) }
				</CardBody>
			</Card>
		</div>
	);
}

/**
 * Top-level layout and a minimal hash router.
 *
 * A dependency-free hash router (`#/books`, `#/books/123`) is enough for
 * two screens and keeps this app in line with the plugin's existing
 * no-extra-runtime-dependencies preference — no router package to install
 * or keep updated.
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import BooksList from './screens/books-list';
import BookDetail from './screens/book-detail';

/**
 * Parse the current URL hash into a route.
 *
 * @return {{ screen: string, bookId: number|null }}
 */
function parseHash() {
	const hash = window.location.hash.replace( /^#\/?/, '' );
	const [ segment, id ] = hash.split( '/' );

	if ( 'books' === segment && id ) {
		return { screen: 'book', bookId: Number( id ) };
	}

	return { screen: 'books', bookId: null };
}

export default function App() {
	const [ route, setRoute ] = useState( parseHash() );

	useEffect( () => {
		const onHashChange = () => setRoute( parseHash() );
		window.addEventListener( 'hashchange', onHashChange );
		return () => window.removeEventListener( 'hashchange', onHashChange );
	}, [] );

	return (
		<div className="hsrtech-app">
			<header className="hsrtech-app__header">
				<h1 className="hsrtech-app__title">
					<a href="#/books">
						<span className="dashicons dashicons-book-alt" aria-hidden="true"></span>
						{ __( 'Chapterwright', 'chapterwright' ) }
					</a>
				</h1>
				{ 'books' === route.screen && (
					<p className="hsrtech-app__tagline">
						{ __( 'Manage every book’s sections and chapters in one place.', 'chapterwright' ) }
					</p>
				) }
			</header>
			<div className="hsrtech-app__body">
				{ 'book' === route.screen ? (
					<BookDetail bookId={ route.bookId } />
				) : (
					<BooksList />
				) }
			</div>
		</div>
	);
}

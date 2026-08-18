/**
 * Top-level layout and a minimal hash router.
 *
 * A dependency-free hash router (`#/books`, `#/books/123`) is enough for
 * two screens and keeps this app in line with the plugin's existing
 * no-extra-runtime-dependencies preference — no router package to install
 * or keep updated.
 */

import { Component, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import BooksList from './screens/books-list';
import BookDetail from './screens/book-detail';

/**
 * Last-resort safety net around each screen.
 *
 * Every action already reachable through the UI (save, add, reorder, trash…)
 * catches its own request failures and shows a Notice — see books-list.js and
 * book-detail.js. But a bug that throws synchronously, outside of a request
 * (a bad assumption about a shape of data, for instance), isn't a request
 * failure at all, so none of that per-action handling can catch it. Without
 * an error boundary, React unmounts the whole screen on an error like that
 * and the page just goes blank with nothing in the UI to explain why —
 * exactly the "silent failure" this hardening pass is meant to close off.
 * This can't recover the screen (React doesn't allow that once render has
 * thrown), but it does turn a blank page into a legible, WordPress-standard
 * error notice with a way back.
 */
class ScreenErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = { error: null };
	}

	static getDerivedStateFromError( error ) {
		return { error };
	}

	componentDidCatch( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Chapterwright admin app error:', error );
	}

	render() {
		if ( this.state.error ) {
			return (
				<Notice status="error" isDismissible={ false } className="hsrtech-notice">
					{ __( 'Something went wrong loading this screen.', 'chapterwright' ) }
					{ ' ' }
					{ this.state.error.message || '' }
					{ ' ' }
					<a href="#/books" onClick={ () => this.setState( { error: null } ) }>
						{ __( 'Go back to all books.', 'chapterwright' ) }
					</a>
				</Notice>
			);
		}

		return this.props.children;
	}
}

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
				<ScreenErrorBoundary key={ route.screen + route.bookId }>
					{ 'book' === route.screen ? (
						<BookDetail bookId={ route.bookId } />
					) : (
						<BooksList />
					) }
				</ScreenErrorBoundary>
			</div>
		</div>
	);
}

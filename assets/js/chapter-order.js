/**
 * Suggests the next chapter order number when the Book dropdown changes.
 *
 * Only overwrites the Order field while it still holds the value the plugin
 * itself suggested, so it never clobbers a number the author typed on
 * purpose.
 *
 * @package Make_A_Book
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var bookSelect = document.getElementById( 'mab_book_id' );
		var orderInput = document.getElementById( 'mab_order' );
		var settings   = window.makeABookChapterOrder || {};

		if ( ! bookSelect || ! orderInput || ! settings.ajaxUrl ) {
			return;
		}

		var lastSuggested = orderInput.value;

		bookSelect.addEventListener( 'change', function () {
			var bookId = bookSelect.value;
			if ( ! bookId ) {
				return;
			}

			// Leave the field alone if the author already typed something else.
			if ( orderInput.value !== '' && orderInput.value !== lastSuggested && orderInput.value !== '0' ) {
				return;
			}

			var body = new window.URLSearchParams();
			body.set( 'action', 'mab_next_chapter_order' );
			body.set( 'nonce', settings.nonce );
			body.set( 'book_id', bookId );

			window
				.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( result ) {
					if ( result && result.success && result.data && result.data.order ) {
						orderInput.value = result.data.order;
						lastSuggested = orderInput.value;
					}
				} )
				.catch( function () {
					// Silent failure — the author can still enter a number manually.
				} );
		} );
	} );
} )();

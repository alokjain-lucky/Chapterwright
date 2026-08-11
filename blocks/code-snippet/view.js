/**
 * Front-end copy-to-clipboard behavior for make-a-book/code-snippet.
 *
 * Dependency-free and loaded only on pages that actually contain the block
 * (block.json's viewScript is enqueued automatically and only then), matching
 * the rest of this plugin's front-end JavaScript.
 *
 * @package Make_A_Book
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.mab-code__copy' );
		if ( ! button ) {
			return;
		}

		var figure = button.closest( '.mab-code' );
		var code = figure ? figure.querySelector( 'code' ) : null;
		if ( ! code ) {
			return;
		}

		var text = code.textContent || '';
		var copiedLabel = button.getAttribute( 'data-mab-copied-label' ) || 'Copied!';
		var defaultLabel = button.getAttribute( 'data-mab-copy-label' ) || button.textContent;

		function showCopied() {
			button.textContent = copiedLabel;
			button.classList.add( 'is-copied' );
			window.setTimeout( function () {
				button.textContent = defaultLabel;
				button.classList.remove( 'is-copied' );
			}, 2000 );
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( showCopied, function () {
				// Clipboard permission denied or unavailable — leave the button as-is.
			} );
			return;
		}

		// Fallback for browsers without the async Clipboard API.
		var textarea = document.createElement( 'textarea' );
		textarea.value = text;
		textarea.setAttribute( 'readonly', '' );
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild( textarea );
		textarea.select();
		try {
			document.execCommand( 'copy' );
			showCopied();
		} catch ( error ) {
			// Copying silently fails; the code remains selectable by hand.
		}
		document.body.removeChild( textarea );
	} );
} )();

/**
 * Front-end copy-to-clipboard and wrap-toggle behavior for
 * chapterwright/code-snippet.
 *
 * Dependency-free and loaded only on pages that actually contain the block
 * (block.json's viewScript is enqueued automatically and only then), matching
 * the rest of this plugin's front-end JavaScript.
 *
 * @package Chapterwright
 */
( function () {
	'use strict';

	// Reader-facing "Wrap long lines" toggle: independent of the author's own
	// saved wrapLines attribute (which only sets the *initial* state render.php
	// outputs) — a visitor can flip it per block, per page view. Deliberately
	// not persisted (no localStorage): resets to the author's default on the
	// next page load, keeping this simple rather than remembering a per-block
	// preference across visits.
	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest( '.hsrtech-code__wrap-toggle' );
		if ( ! toggle ) {
			return;
		}

		var figure = toggle.closest( '.hsrtech-code' );
		if ( ! figure ) {
			return;
		}

		var isWrapped = figure.classList.toggle( 'hsrtech-code--wrap' );
		var wrapLabel = toggle.getAttribute( 'data-hsrtech-wrap-label' ) || 'Wrap long lines';
		var unwrapLabel = toggle.getAttribute( 'data-hsrtech-unwrap-label' ) || 'Scroll long lines';

		toggle.setAttribute( 'aria-pressed', isWrapped ? 'true' : 'false' );
		toggle.setAttribute( 'aria-label', isWrapped ? unwrapLabel : wrapLabel );
	} );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.hsrtech-code__copy' );
		if ( ! button ) {
			return;
		}

		var figure = button.closest( '.hsrtech-code' );
		if ( ! figure ) {
			return;
		}

		// When "Show line numbers" is on, code-highlight.js replaces the
		// <pre><code> pair with a .hsrtech-code__lines row-per-line
		// structure (see buildNumberedLines() there) that has no <code>
		// element at all — rebuild the original text by rejoining each
		// row's own code span with "\n", the same separator that structure
		// split on in the first place, rather than assuming <code> exists.
		var lineRows = figure.querySelectorAll( '.hsrtech-code__lines .hsrtech-code__line-code' );
		var text;
		if ( lineRows.length ) {
			text = Array.prototype.map.call( lineRows, function ( row ) {
				return row.textContent;
			} ).join( '\n' );
		} else {
			var code = figure.querySelector( 'code' );
			if ( ! code ) {
				return;
			}
			text = code.textContent || '';
		}
		var copiedLabel = button.getAttribute( 'data-hsrtech-copied-label' ) || 'Copied!';
		var defaultLabel = button.getAttribute( 'data-hsrtech-copy-label' ) || 'Copy code';

		// Icon-only button: the visible state change is a swapped icon (via
		// the .is-copied class, see style.css), with the accessible name
		// carried by aria-label rather than by textContent.
		function showCopied() {
			button.setAttribute( 'aria-label', copiedLabel );
			button.classList.add( 'is-copied' );
			window.setTimeout( function () {
				button.setAttribute( 'aria-label', defaultLabel );
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

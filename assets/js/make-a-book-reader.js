/**
 * Accessible, persistent three-state color mode control.
 *
 * @package Make_A_Book
 */
( function () {
	'use strict';

	const storageKey = 'make-a-book-color-mode';
	const modes = [ 'auto', 'light', 'dark' ];
	const labels = window.makeABookReader || {};
	const buttons = document.querySelectorAll( '.mab-mode-toggle' );

	if ( ! buttons.length ) {
		return;
	}

	/** Apply a mode to the document and update every visible control. */
	function applyMode( mode ) {
		const validMode = modes.includes( mode ) ? mode : 'auto';
		document.documentElement.dataset.mabMode = validMode;

		buttons.forEach( function ( button ) {
			const label = button.querySelector( '[data-mab-mode-label]' );
			const text = validMode === 'dark' ? labels.modeDark : ( validMode === 'light' ? labels.modeLight : labels.modeAuto );
			button.dataset.mode = validMode;
			button.setAttribute( 'aria-label', text );
			if ( label ) {
				label.textContent = text;
			}
		} );
	}

	let savedMode = 'auto';
	try {
		savedMode = window.localStorage.getItem( storageKey ) || 'auto';
	} catch ( error ) {
		// Storage may be unavailable in privacy-restricted browsing contexts.
	}
	applyMode( savedMode );

	buttons.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			const currentIndex = modes.indexOf( button.dataset.mode );
			const nextMode = modes[ ( currentIndex + 1 ) % modes.length ];
			try {
				window.localStorage.setItem( storageKey, nextMode );
			} catch ( error ) {
				// The control still works for this page view without persistence.
			}
			applyMode( nextMode );
		} );
	} );

	const progress = document.querySelector( '[data-mab-reading-progress]' );
	const article = document.querySelector( '.mab-chapter' );
	if ( progress && article ) {
		/** Update the quiet visual progress indicator without announcing every scroll. */
		function updateProgress() {
			const start = article.offsetTop;
			const distance = Math.max( 1, article.offsetHeight - window.innerHeight );
			const value = Math.max( 0, Math.min( 1, ( window.scrollY - start ) / distance ) );
			progress.style.transform = 'scaleX(' + value + ')';
		}
		window.addEventListener( 'scroll', updateProgress, { passive: true } );
		window.addEventListener( 'resize', updateProgress );
		updateProgress();
	}
}() );

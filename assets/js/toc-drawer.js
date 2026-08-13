/**
 * Table-of-contents drawer on chapter pages.
 *
 * Progressive enhancement over a plain link: templates/single-mab_chapter.php
 * renders `.mab-toc-jump` as a real `<a href="{book}#mab-toc-eyebrow">`, so
 * without this script (or with JS disabled) it still works exactly as
 * before — navigates to the book page and jumps straight to the table of
 * contents. With this script, its click is intercepted and it opens an
 * in-page slide-in panel instead, so reading isn't interrupted by a full
 * navigation just to glance at the chapter list.
 *
 * Deliberately its own file rather than folded into make-a-book-reader.js
 * — that file returns early when no `.mab-mode-toggle` button is on the
 * page (i.e. the "Color mode toggle" setting is off), which would have
 * silently taken this along with it.
 *
 * @package Make_A_Book
 */
( function () {
	'use strict';

	const trigger = document.querySelector( '[data-mab-toc-trigger]' );
	const drawer = document.getElementById( 'mab-toc-drawer' );
	const backdrop = document.querySelector( '[data-mab-toc-backdrop]' );
	const closeButton = document.querySelector( '[data-mab-toc-close]' );

	if ( ! trigger || ! drawer || ! backdrop ) {
		return;
	}

	let isOpen = false;

	/** Every element inside the drawer that can normally receive focus. */
	function getFocusable() {
		return Array.prototype.slice.call(
			drawer.querySelectorAll( 'a[href], button:not([disabled])' )
		);
	}

	function openDrawer() {
		if ( isOpen ) {
			return;
		}
		isOpen = true;

		drawer.hidden = false;
		backdrop.hidden = false;
		// Two rAFs so the browser paints the `hidden`-removed state first —
		// otherwise the transform/opacity transition below has nothing to
		// transition *from* and the panel just appears instantly.
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				drawer.classList.add( 'is-open' );
				backdrop.classList.add( 'is-open' );
			} );
		} );

		trigger.setAttribute( 'aria-expanded', 'true' );
		document.body.classList.add( 'mab-no-scroll' );

		const focusable = getFocusable();
		( closeButton || focusable[ 0 ] || drawer ).focus();

		document.addEventListener( 'keydown', onKeydown );
	}

	function closeDrawer() {
		if ( ! isOpen ) {
			return;
		}
		isOpen = false;

		drawer.classList.remove( 'is-open' );
		backdrop.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		document.body.classList.remove( 'mab-no-scroll' );
		document.removeEventListener( 'keydown', onKeydown );

		// Wait for the closing transition before actually hiding — matches
		// the CSS transition duration (.mab-toc-drawer / -backdrop, 250ms).
		window.setTimeout( function () {
			if ( ! isOpen ) {
				drawer.hidden = true;
				backdrop.hidden = true;
			}
		}, 300 );

		trigger.focus();
	}

	/** Escape closes; Tab is kept inside the drawer while it's open. */
	function onKeydown( event ) {
		if ( 'Escape' === event.key ) {
			closeDrawer();
			return;
		}
		if ( 'Tab' !== event.key ) {
			return;
		}

		const focusable = getFocusable();
		if ( ! focusable.length ) {
			return;
		}
		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	trigger.addEventListener( 'click', function ( event ) {
		event.preventDefault();
		openDrawer();
	} );

	backdrop.addEventListener( 'click', closeDrawer );

	if ( closeButton ) {
		closeButton.addEventListener( 'click', closeDrawer );
	}
} )();

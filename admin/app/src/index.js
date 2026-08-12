/**
 * Admin app entry point. Mounted into the #make-a-book-app container
 * rendered by admin/app.php on the plugin's top-level admin page.
 */

import { createRoot } from '@wordpress/element';
import App from './app';
import './style.css';

const container = document.getElementById( 'make-a-book-app' );

if ( container ) {
	createRoot( container ).render( <App /> );
}

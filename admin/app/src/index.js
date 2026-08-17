/**
 * Admin app entry point. Mounted into the #chapterwright-app container
 * rendered by admin/app.php on the plugin's top-level admin page.
 */

import { createRoot } from '@wordpress/element';
import App from './app';
import './style.css';

const container = document.getElementById( 'chapterwright-app' );

if ( container ) {
	createRoot( container ).render( <App /> );
}

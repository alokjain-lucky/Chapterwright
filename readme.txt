=== Make a Book ===
Contributors: alokjain
Tags: ebook, books, chapters, publishing, reader
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publish multiple web-native ebooks, each with sections, ordered chapters, a landing page and a focused reading experience.

== Description ==

Make a Book adds Books and Chapters to WordPress. Each book has its own cover, subtitle, accent color and table of contents. Chapters can be grouped into named sections and receive automatic previous/next navigation.

Features:

* Publish any number of books.
* Organize chapters into sections.
* Set a custom chapter order.
* Responsive book landing and reader templates.
* Book archive at `/books/`.
* Book library shortcode: `[make_a_book]` or `[make_a_book limit="6"]`.
* Block editor and REST API support.
* Theme-compatible header and footer.
* System, light and dark reading modes with a persistent accessible control.
* Book and Chapter schema.org structured data.
* Accessible skip links, focus indicators, landmarks and responsive content.
* Readable code blocks, tables and reusable note/warning callouts.
* Editorial chapter typography, author/date/read-time metadata and a quiet reading progress indicator.

== Installation ==

1. Upload the `make-a-book` folder to `/wp-content/plugins/`, or upload the plugin ZIP from Plugins > Add New > Upload Plugin.
2. Activate Make a Book from the Plugins screen.
3. Go to Books > Add New and publish your first book.
4. Go to Chapters > Add New, add the chapter content, assign it to the book and publish it.
5. Open the book's View link, visit `/books/`, or place `[make_a_book]` on any page.

If an existing site returns a 404 for a book URL, visit Settings > Permalinks and click Save Changes once.

== Creating a Book ==

Books provide the landing page and table of contents for a publication.

1. In the WordPress dashboard, go to Books > Add New.
2. Enter the book title.
3. Add an excerpt. It appears in the book hero and in book-library cards, and is also used in structured data. If it is left empty, WordPress may generate one from the main content.
4. Add an optional introduction or other front matter in the main editor. It appears between the book hero and table of contents.
5. In the Book Details panel, enter an optional subtitle and choose the accent color.
6. Set a featured image to use it as the book cover.
7. Choose the author and publish the book.

The book page builds its table of contents from all published Chapters assigned to that book. Draft and private chapters do not appear in the public table of contents.

== Adding and Organizing Chapters ==

1. Go to Chapters > Add New.
2. Use the title for the chapter name and the main editor for the complete chapter content. Normal blocks, headings, images, links, lists, tables, code and shortcodes can be used.
3. Add an optional excerpt. It is shown beneath the chapter title, in the book table of contents and in structured data.
4. Add an optional featured image. It appears below the chapter header.
5. In the Chapter Details panel, select the parent Book. A chapter must be assigned to a book to participate in that book's table of contents and previous/next navigation.
6. Enter an optional Section name, such as `Getting Started` or `Part II`. Chapters with the same exact section name are grouped together. A blank section is displayed under the default `Chapters` heading.
7. Enter the Chapter number / order as a non-negative whole number. Chapters are sorted from the lowest number to the highest; publication date breaks ties.
8. Publish the chapter.

The chapter page automatically displays its author, publication date, estimated reading time, reading progress, link back to the book and previous/next chapter links. The reading sequence includes only published chapters assigned to the same book.

For a predictable table of contents, give every chapter a unique order value. Changing the order or parent book in the Chapter Details panel updates the table of contents and navigation automatically.

== Displaying the Book Library ==

WordPress automatically provides a public book archive at `/books/`. The archive lists published books using the bundled book grid.

To place the library inside an existing page or post, insert a Shortcode block containing:

`[make_a_book]`

By default, the shortcode displays up to 12 published books, newest first. Set a limit from 1 to 100 with:

`[make_a_book limit="6"]`

The plugin loads its reader stylesheet and script only on Book pages, Chapter pages, the book archive and singular pages whose saved content contains the shortcode.

== Reader Features ==

Visitors can cycle among system, light and dark color modes. Their choice is stored in the browser under the `make-a-book-color-mode` local-storage key. The selected mode is exposed on the document root with the `data-mab-mode` attribute, which can also be targeted by custom CSS.

Book and Chapter pages include accessible landmarks, skip links, keyboard focus styles and reduced-motion behavior. Book and Chapter structured data is added automatically. The estimated chapter reading time uses the visible word count at approximately 220 words per minute.

== Using Your Own Styling ==

The bundled stylesheet is `assets/css/make-a-book.css`. Its visitor-facing selectors use the `.mab-` prefix. Do not edit this file inside the plugin because plugin updates will replace those changes.

Add overrides to a child theme's `style.css`, to your theme's supported custom-CSS area, or from a small site-specific plugin. Use the browser inspector to identify the relevant `.mab-*` selector. The book's chosen accent color is available inside plugin views as the `--mab-accent` custom property.

Example child-theme CSS:

`
.mab-page {
    --mab-accent: #2563eb;
}

.mab-chapter__content {
    font-family: Georgia, serif;
}

.mab-book-hero {
    border-radius: 0;
}

[data-mab-mode="dark"] .mab-chapter__content a {
    color: #93c5fd;
}
`

More specific selectors may be needed when a theme also styles headings, links, figures or content blocks. Preserve visible focus indicators, adequate color contrast, responsive layouts and reduced-motion behavior when overriding the defaults. The reader is intentionally designed around a roughly 680–720px text column for comfortable long-form reading.

== Using or Editing Templates ==

The plugin's presentation files are:

* `templates/single-mab_book.php` — single-book landing page and grouped table of contents.
* `templates/single-mab_chapter.php` — chapter reader and previous/next navigation.
* `templates/archive-mab_book.php` — `/books/` archive shell.
* `templates/book-grid.php` — reusable cards used by the archive and shortcode.
* `templates/partials/document-start.php` and `document-end.php` — classic- and block-theme header/footer compatibility.

Editing bundled templates directly is not update-safe. To override a routed single or archive template, copy the template to a child theme and use a late `template_include` filter from the child theme's `functions.php` or a site-specific plugin. For example, after copying the files to `your-child-theme/make-a-book/`:

`
function mysite_make_a_book_templates( $template ) {
    if ( is_singular( 'mab_book' ) ) {
        return get_stylesheet_directory() . '/make-a-book/single-mab_book.php';
    }

    if ( is_singular( 'mab_chapter' ) ) {
        return get_stylesheet_directory() . '/make-a-book/single-mab_chapter.php';
    }

    if ( is_post_type_archive( 'mab_book' ) ) {
        return get_stylesheet_directory() . '/make-a-book/archive-mab_book.php';
    }

    return $template;
}
add_filter( 'template_include', 'mysite_make_a_book_templates', 99 );
`

The plugin does not currently auto-discover template copies in a theme, so the filter is required. Check that each returned file exists if templates may be optional.

The bundled single and archive templates include the plugin's document-start and document-end partials. A copied template may continue requiring those plugin partials, or it may use the child theme's normal header and footer. Keep only one document header and footer in the final response.

`book-grid.php` is included directly by the plugin's shortcode and archive templates and has no separate lookup filter. To change the archive grid, copy and adapt both `archive-mab_book.php` and the grid it includes. To build a completely custom library elsewhere, query published `mab_book` posts in a child theme or site-specific plugin instead of editing the bundled file.

When editing templates:

* Escape attributes with `esc_attr()`, URLs with `esc_url()` and plain text with `esc_html()` at output time.
* Render editor content through `the_content()` so WordPress blocks, shortcodes and content filters continue to work.
* Retain accessible landmarks, navigation labels, skip links and keyboard focus behavior.
* Keep customizations in a child theme or site-specific plugin so plugin upgrades remain safe.

== Content and URL Reference ==

For integrations and advanced template work, the stable identifiers are:

* Book post type: `mab_book`
* Chapter post type: `mab_chapter`
* Book archive and single-book base: `/books/`
* Chapter base: `/book-chapter/`
* Chapter's parent-book metadata: `_mab_book_id`
* Chapter section metadata: `_mab_section`
* Chapter order metadata: `_mab_order`
* Book subtitle metadata: `_mab_subtitle`
* Book accent metadata: `_mab_accent`
* Library shortcode: `[make_a_book]`

Books and Chapters support the block editor, revisions and the WordPress REST API. Books also support author selection. Use the normal WordPress APIs when reading or writing these values, and validate that `_mab_book_id` points to a Book.

== Updating and Removing the Plugin ==

Back up the site before updating WordPress, a theme or any plugin. Custom edits made inside the Make a Book plugin directory will be overwritten by an update, which is why template and CSS changes should live in a child theme or site-specific plugin.

Uninstalling Make a Book intentionally retains all user-authored Books, Chapters and their metadata. This protects published content from accidental deletion. Remove retained content manually only after making a backup and confirming it is no longer needed.

== Frequently Asked Questions ==

= Can I publish more than one book? =

Yes. Each chapter is assigned to a specific book, and the library supports any number of published books.

= Can I show the books on an existing page? =

Yes. Add the `[make_a_book]` shortcode to a page. Use the optional `limit` attribute to control how many books appear.

= Why is a chapter missing from the table of contents? =

Confirm that the chapter is published, has the correct Book selected in Chapter Details and that the parent Book is accessible. Only published chapters assigned to that book are listed.

= How do I change chapter order? =

Edit each Chapter and set Chapter number / order in the Chapter Details panel. Use unique whole numbers for the clearest sequence.

= Can one chapter belong to more than one book? =

No. A Chapter has one parent Book. Duplicate or adapt the chapter if separate books require independently ordered versions.

= Can I change the `/books/` or `/book-chapter/` URL bases? =

Not from the plugin settings. Those bases are part of the plugin's stable public URL contract. Changing them requires custom development and a redirect and migration plan for existing links.

= Does my theme header and footer still appear? =

Yes. The bundled views support both classic and block themes and render the active theme's header and footer. Highly customized themes may need a child-theme template override.

= Is it safe to edit plugin templates or CSS directly? =

Those edits work temporarily but will be lost on update. Put CSS in a child theme or supported custom-CSS area, and copy routed templates to a child theme with the documented `template_include` filter.

== Developer Notes ==

The plugin is organized by responsibility:

* `includes/` contains the bootstrap coordinator and shared content model.
* `admin/` contains editor panels, metadata persistence and dashboard columns.
* `public/` contains template routing, conditional assets and shortcodes.
* `templates/` contains visitor-facing presentation files.
* `assets/` contains scoped visitor-facing styles.
* `docs/ARCHITECTURE.md` documents the component and content architecture.

Public post type names, metadata keys, routes and the shortcode are documented in the Content and URL Reference section above.

The repository includes a reproducible local WordPress environment. Run `npm install`, `npm run env:start`, and `npm run env:test`.

== Changelog ==

= 1.1.0 =

* Added accessible system, light and dark reading modes.
* Added Book and Chapter JSON-LD structured data.
* Added code, table, callout, keyboard-focus and skip-link styles.
* Refined chapter typography and added reading metadata and progress feedback.
* Added full compatibility with classic and block themes.
* Added wp-env configuration and runtime smoke-test tooling.

= 1.0.0 =

* Initial release.

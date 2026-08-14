=== Make a Book ===
Contributors: alokjain_lucky
Tags: ebook, books, publishing, reading, chapters
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publish multi-chapter ebooks in WordPress with tables of contents, sections, reading progress, and a distraction-free reader.

== Description ==

Make a Book turns WordPress into a home for multiple, beautifully readable ebooks — each with its own landing page, grouped table of contents, ordered chapters, and a focused reading experience.

It adds two content types, **Books** and **Chapters**. Each book can have its own cover, subtitle, accent color, introduction, and table of contents. Chapters can be grouped into sections — each with its own name and description — and get automatic previous/next navigation.

= Features =

* Publish any number of books.
* Organize chapters into sections, each with its own name and optional description shown in the table of contents.
* Control chapter order, with automatic previous/next navigation.
* Display a book library at `/books/`, or embed it anywhere with the `[make_a_book]` shortcode.
* Responsive book and chapter templates that use your active theme's header, footer, and fonts.
* Full block editor, revisions, and REST API support.
* Reader-selectable system, light, or dark color mode.
* Reading progress indicator and estimated reading time per chapter.
* Book and Chapter schema.org structured data.
* Accessible skip links, landmarks, focus indicators, and reduced-motion support.
* Styled code blocks (with a dedicated **Code Snippet** block: syntax coloring, a language label, an optional caption, and a copy button), tables, and reusable callouts for technical writing.
* A single **Make a Book** admin page to manage every book, its sections, and its chapters — adding, reordering, and reassigning them — without the classic post-type screens' back-and-forth.
* A Settings page to turn the reader's color-mode toggle on or off, and edit or remove page headings.
* Registers with the WordPress Abilities API (WordPress 6.9+) so AI agents and automation tools can discover and use the plugin's book/chapter/section operations in a standardized, permission-checked way.

= Who it's for =

Anyone publishing a technical guide, course, documentation set, or serialized book directly on their WordPress site — without standing up a separate platform.

== Installation ==

1. In your WordPress admin, go to **Plugins → Add New Plugin**, search for "Make a Book", and click **Install Now**, then **Activate**. (Or download the ZIP and upload it via **Plugins → Add New Plugin → Upload Plugin**.)
2. Go to **Make a Book** in the admin sidebar and select **Add Book**.
3. Open the new book, add a chapter from its detail screen, then follow the **Edit content →** link to write it in the block editor.
4. Open the book's **View** link, visit `/books/`, or add `[make_a_book]` to a page.

If a book URL returns a 404 after activation, go to **Settings → Permalinks** and click **Save Changes** once.

== Frequently Asked Questions ==

= Where do I manage my books and chapters? =

Under **Make a Book → Books & Chapters** in the admin sidebar — a single-page app for organizing your library: adding books, adding and reordering sections and chapters, and jumping into the block editor to write. The classic Book/Chapter list and edit screens still exist underneath it; nothing about how content is written has changed.

= Does this work with any theme? =

Yes. The reader uses `font-family: inherit` throughout, so headings and body text pick up your active theme's fonts and heading sizes automatically instead of a bundled font stack.

= Can I customize the design? =

Yes. Visitor-facing styles use a `.mab-` class prefix and expose a `--mab-accent` custom property for each book's accent color. See "Custom styling" in the plugin's full documentation for how to safely override the bundled stylesheet from a child theme or site-specific plugin without losing changes on update.

= Is there a limit to how many books or chapters I can create? =

No. Books and Chapters are regular WordPress post types under the hood, so the only limit is your hosting environment's usual limits.

= What happens if I uninstall the plugin? =

Deleting Make a Book from the **Plugins** screen performs a full clean sweep: every Book and Chapter (and their metadata), the sections database table, the Book/Chapter capabilities granted to any role, and the plugin's saved settings are all permanently deleted. Back up your site first if you want to keep any of it. Simply deactivating the plugin does not touch your data — only actually removing it does.

= Does this support translations? =

Yes. Every string (PHP and the admin app's JavaScript) is wrapped for translation under the `make-a-book` text domain.

== Screenshots ==

1. A book's landing page, with hero, cover, and "Start reading" / "Table of contents" actions.
2. A grouped table of contents with sections, chapter numbers, and dot leaders.
3. The chapter reader, with reading progress, estimated reading time, and previous/next navigation.
4. The **Books & Chapters** admin app for managing a book's sections and chapters.
5. The **Code Snippet** block with syntax coloring and a copy button.

== Changelog ==

= 2.5.0 =
* Added estimated reading time next to each chapter's number.
* The Code Snippet block can now be created by transforming an existing Code block or paragraph into it.
* Fixed the mobile menu not opening on book, chapter, and library pages.
* Fixed mobile layout issues with the book title/chapter counter and header spacing.
* Fixed the reading-progress bar using a default color instead of the book's own accent color.
* Chapters now preload the previous/next chapter in the background for faster navigation.
* Accessibility improvements to the library page and table of contents.
* Fixed the book cover image on the library page growing oversized with only one or two books.

= 2.4.0 =
* Fixed book/chapter details (accent color, subtitle, book/section assignment) silently failing to save.
* Code blocks now have real syntax highlighting; chapters have a floating button that opens the table of contents as a slide-in drawer.
* Refined reading typography throughout.
* Added a "Coming soon" flag for unpublished books, and an optional setting to show draft chapter titles in the table of contents.
* Added a whole-book progress bar alongside the per-chapter one.
* Fixed several visual bugs, including invisible badges/icons in light mode.

= 2.3.2 =
* Fixed newly created chapters sometimes not appearing in a book's chapter list.
* Chapter rows in the admin app now show a status pill and explicit Edit/Trash buttons.
* The block-editor sidebar's Chapter Details panel now shows book/section/order as read-only reference information.

= 2.3.1 =
* Redirects the native Books/Chapters list and "Add New" screens to the admin app.

= 2.3.0 =
* Added the ability to delete a book from the admin app.

= 2.2.1 =
* Fixed the Books & Chapters admin menu item disappearing after updating to 2.2.0.

= 2.2.0 =
* Books and Chapters now have their own WordPress capabilities instead of reusing generic post capabilities.

= 2.1.0 =
* Added working translation infrastructure and a ready-to-translate `.pot` file.

= 2.0.3 =
* Added helper text under the Accent color field explaining what it affects.

= 2.0.2 =
* Fixed the block-editor sidebar panel crashing on open.
* Renamed the book page's edit-content button for clarity.

= 2.0.1 =
* Fixed a critical bug that broke every custom REST route added in 2.0.0.
* Fixed the Settings page rendering blank.
* Refreshed the admin app's visual design.
* Uninstalling the plugin now performs a full clean sweep instead of retaining content.

= 2.0.0 =
* Added the **Books & Chapters** admin app and a block-editor sidebar panel for managing book/chapter details.
* Chapter sections are now their own database rows (with a name and description) instead of a free-text field; existing sites migrate automatically.
* Registered with the WordPress Abilities API (6.9+).
* Added REST API support for book/chapter meta and a small custom REST namespace for sections.

= 1.5.1 =
* Removed internal developer-only files from the distributed plugin.

= 1.5.0 =
* Added contextual Help tabs to the Book and Chapter screens.
* Reworded the optional credit line.
* Minor security/standards fixes.

= 1.4.0 =
* Added a "Back to library" link and an optional credit line.

= 1.3.1 =
* Fixed excess empty space above the book hero and archive header.

= 1.3.0 =
* Added a Settings page for the color-mode toggle and page headings.
* Grouped Books, Chapters, and Settings under one admin menu.
* Headings now inherit the active theme's font sizes instead of a bundled size.

= 1.2.0 =
* Rewrote the plugin from a class-based to a function-based architecture (internal change only).
* Added the Code Snippet block.
* Reader and block typography now inherit the active theme's fonts.

= 1.1.0 =
* Added accessible system/light/dark reading modes.
* Added Book and Chapter structured data.
* Added code, table, callout, focus, and skip-link styles.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.5.0 =
Includes an accessibility pass and a fix for the mobile menu not opening on book/chapter/library pages — recommended for all sites.

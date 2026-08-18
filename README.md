# Chapterwright

Publish multiple web-native ebooks in WordPress, with book landing pages, grouped tables of contents, ordered chapters, and a focused reading experience.

[![Try Chapterwright on WordPress Playground](https://img.shields.io/badge/-Try%20it%20on%20WordPress%20Playground-3858E9?style=for-the-badge&logo=wordpress&logoColor=white)](https://playground.wordpress.net/#%7B%22landingPage%22%3A%22%2Fwp-admin%2Fadmin.php%3Fpage%3Dchapterwright%22%2C%22preferredVersions%22%3A%7B%22php%22%3A%228.3%22%2C%22wp%22%3A%22latest%22%7D%2C%22features%22%3A%7B%22networking%22%3Atrue%7D%2C%22steps%22%3A%5B%7B%22step%22%3A%22login%22%2C%22username%22%3A%22admin%22%2C%22password%22%3A%22password%22%7D%2C%7B%22step%22%3A%22installPlugin%22%2C%22pluginData%22%3A%7B%22resource%22%3A%22url%22%2C%22url%22%3A%22https%3A%2F%2Fgithub.com%2Falokjain-lucky%2FChapterwright%2Freleases%2Flatest%2Fdownload%2Fchapterwright.zip%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%5D%7D)

Opens a temporary WordPress site in your browser with Chapterwright already installed and activated — no download or install needed. Uses [WordPress Playground](https://playground.wordpress.net/); the site and any changes you make disappear when you close the tab. The button always pulls the latest GitHub release, since the link points at GitHub's `releases/latest` redirect rather than a specific version.

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 6.4 or newer |
| PHP | 7.4 or newer |
| Tested through | WordPress 7.0 |
| Plugin version | 2.8.0 |

Chapterwright adds two content types to WordPress: **Books** and **Chapters**. Each book can have its own cover, subtitle, accent color, introduction, and table of contents. Chapters can be grouped into sections — each with its own name and description — and receive automatic previous/next navigation.

> [!NOTE]
> The Abilities API integration (see [Abilities API](#abilities-api)) only registers on WordPress 6.9 and newer, since that API doesn't exist on older versions. Everything else in this plugin works on the minimum WordPress 6.4 listed above.

## Features

- Publish any number of books.
- Organize chapters into sections, each with its own name and optional description shown in the table of contents.
- Control chapter order.
- Display a book library at `/books/` or with a shortcode.
- Use responsive book and chapter templates with theme-compatible headers and footers.
- Support the block editor, revisions, and the WordPress REST API.
- Let readers choose system, light, or dark color mode.
- Provide reading progress and estimated reading time.
- Add Book and Chapter schema.org structured data.
- Provide accessible skip links, landmarks, focus indicators, and reduced-motion support.
- Style code blocks, tables, and reusable note or warning callouts for comfortable technical reading.
- Add a **Code Snippet** block for formatted, copyable code examples with an optional caption and language label.
- A single **Chapterwright** admin page lists every book, and lets you manage a book's sections and chapters — adding, reordering, and reassigning them — in one place, without the classic post-type screens' back-and-forth. See [The admin app](#the-admin-app).
- Typography inherits the active theme's fonts and heading sizes, so the reader looks like a native part of the site instead of a bundled font stack.
- A Settings page lets you turn the reader's color-mode toggle on or off, and edit or remove the library page's heading text and each book's table-of-contents heading.
- Built-in usage instructions in the Book and Chapter screens' native WordPress "Help" tab — no separate documentation page to hunt for.
- Registers with the WordPress [Abilities API](#abilities-api) (WordPress 6.9+) so AI agents and automation tools can discover and use the plugin's book/chapter/section operations in a standardized, permission-checked way.

## Installation

1. Download the plugin ZIP.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and activate **Chapterwright**.
4. Go to **Chapterwright** in the admin sidebar and select **Add Book**.
5. Open the new book, add a chapter from its detail screen, then follow the **Edit content →** link to write it in the block editor.
6. Open the book's **View** link, visit `/books/`, or add `[hsrtech_books]` to a page.

You can also install the plugin manually by copying the `chapterwright` directory to `/wp-content/plugins/` and activating it from the **Plugins** screen.

> [!TIP]
> If a book URL returns a 404 after activation, go to **Settings → Permalinks** and click **Save Changes** once.

The admin app and Settings both live under one **Chapterwright** entry in the admin sidebar.

## The admin app

**Chapterwright → Books & Chapters** is a single-page app (built with the same `@wordpress/components` used throughout the block editor, so it looks and feels native) for organizing your library:

- The books list shows every book with its cover and status, and a quick "Add Book" field.
- Opening a book shows its subtitle and accent color, its sections (add, rename, describe, reorder, delete), and every chapter (add, reassign to a section, reorder, move to Trash).
- "Open in Block Editor →" opens the book or chapter in the normal block editor — the app manages structure, not the writing itself.

The classic Book and Chapter list/edit screens still exist (a "Chapter Details" panel in the block editor's own sidebar mirrors the same book/section/order fields), so nothing about how content is written has changed — only where you go to organize the library.

## Settings

Go to **Chapterwright → Settings** to:

- Turn the reader's own light/dark color-mode toggle on or off (shown on book and chapter pages). This is separate from any color-mode switch your theme puts in the site header — turn this off if the two feel redundant.
- Turn the "This book is created with Chapterwright" credit line at the bottom of book, chapter, and library pages on or off.
- Edit or remove the library page's (`/books/`) eyebrow label, heading, and subheading.
- Edit or remove the "Read at your own pace" heading shown above each book's table of contents.

Clearing a text field removes that line from the page entirely rather than falling back to the default wording — this is the intended way to drop placeholder copy you don't want.

## Creating a book

Books provide the landing page and table of contents for a publication.

1. Go to **Chapterwright**, and use **Add Book** to create one with a title.
2. Open the new book and follow **Open in Block Editor →** to write the introduction, set a featured image (used as the cover), and add an excerpt in the normal block editor. The excerpt appears in the book hero, library cards, and structured data; when omitted, WordPress may generate one from the main content.
3. Back on the book's admin-app page, fill in an optional subtitle and choose an accent color.
4. Choose the author and publish the book from the block editor.

The table of contents is built from all published Chapters assigned to the book. Draft and private chapters are not displayed publicly.

## Adding and organizing chapters

Open a book in **Chapterwright → Books & Chapters** to see its sections and chapters together. Use **+ Add chapter** to create one (optionally into a section right away) — it opens in the block editor as a draft, ready to write.

1. Use the title for the chapter name and the main editor for the complete chapter content. You can use normal blocks, headings, images, links, lists, tables, code, and shortcodes.
2. Add an optional excerpt. It appears beneath the chapter title, in the table of contents, and in structured data.
3. Add an optional featured image. It appears below the chapter header.
4. Publish the chapter.

Sections are their own thing, not just a text label: create one from the book's admin-app page with a name (such as `Getting Started` or `Part II`) and an optional description, which appears under the heading in the table of contents. Assign a chapter to a section — or move it between sections — from the same page, or from the "Chapter Details" panel in the chapter's own block-editor sidebar. Chapters left unassigned appear under the default **Chapters** heading.

Chapter order runs from the lowest number to the highest and controls both the table of contents and previous/next navigation; reorder chapters with the up/down controls on the book's admin-app page. Publication date breaks ties if two chapters ever share a number.

> [!IMPORTANT]
> A chapter must be published and assigned to a Book to participate in that book's table of contents and reading navigation.

Deleting a section never deletes its chapters — they simply become unassigned and fall back to the default **Chapters** heading.

## The Code Snippet block

For chapters that include code, add a **Code Snippet** block (search for "Code Snippet" in the block inserter) instead of a plain Code block. It provides:

- A monospace editor that preserves exact whitespace, like the core Code block.
- A language label (PHP, JavaScript, CSS, HTML, Shell, or Plain text).
- An optional caption, useful for a filename or a one-line description.
- A front-end **Copy** button so readers can copy the snippet without selecting text manually.

## Displaying the book library

The plugin automatically provides a public book archive at:

```text
/books/
```

To display the library inside an existing page or post, add a Shortcode block containing:

```text
[hsrtech_books]
```

The shortcode displays up to 12 published books, newest first. Set a limit between 1 and 100 with:

```text
[hsrtech_books limit="6"]
```

The reader stylesheet and script load only on Book pages, Chapter pages, the book archive, and singular pages whose saved content contains the shortcode.

## Reader experience

Chapter pages automatically include:

- The chapter author and publication date.
- An estimated reading time based on approximately 220 words per minute.
- A quiet reading-progress indicator.
- A link back to the parent book.
- Previous and next chapter navigation.
- A system, light, and dark color-mode control.

The reader stores the visitor's color preference under the `chapterwright-color-mode` local-storage key. The selected mode is exposed on the document root through `data-hsrtech-mode`, allowing custom styles for each mode.

Book pages also include a "← Back to library" link to `/books/`. Book, chapter, and library pages can optionally show a small credit at the bottom linking to the plugin's repository — "This book is created with Chapterwright" on book/chapter pages, "This library is powered by Chapterwright" on `/books/` — see [Settings](#settings).

## Custom styling

The bundled stylesheet is [`assets/css/chapterwright.css`](assets/css/chapterwright.css). Visitor-facing selectors use the `.hsrtech-` prefix, and the selected book accent is available through the `--hsrtech-accent` custom property.

Body and heading text use `font-family: inherit` throughout, so the reader automatically picks up your active theme's fonts — there is nothing to configure. Code blocks are the one deliberate exception and always use a fixed monospace stack, since code needs to stay legible and evenly spaced regardless of theme.

Headings (`h1`–`h3`) don't set their own `font-size` either — they inherit whatever size your theme applies to headings (or the browser default if your theme doesn't style headings globally), rather than a large size bundled with the plugin.

Do not edit the bundled stylesheet directly because plugin updates will overwrite those changes. Add overrides in one of these places instead:

- A child theme's `style.css`.
- Your theme's supported custom-CSS area.
- A small site-specific plugin.
- A dedicated stylesheet your theme enqueues itself, with `array( 'chapterwright' )` as its dependency so it's guaranteed to print after this plugin's own CSS. This is the most maintainable option for a theme that wants to closely match its own design tokens (color palette, card radius, button styling) — remap the `--hsrtech-*` custom properties to your theme's own CSS variables rather than hardcoding colors, so the reader keeps tracking your theme's light/dark toggle automatically.

Example child-theme CSS:

```css
.hsrtech-page {
    --hsrtech-accent: #2563eb;
}

.hsrtech-chapter__content {
    font-family: "Iowan Old Style", Georgia, serif;
}
```

When overriding the defaults, preserve visible focus indicators, adequate color contrast, responsive layouts, and reduced-motion behavior. The reader intentionally uses a text column of approximately 680–720px for comfortable long-form reading.

## Template customization

The presentation files are organized as follows:

| Template | Purpose |
| --- | --- |
| [`templates/single-hsrtech_book.php`](templates/single-hsrtech_book.php) | Book landing page and grouped table of contents |
| [`templates/single-hsrtech_chapter.php`](templates/single-hsrtech_chapter.php) | Chapter reader and previous/next navigation |
| [`templates/archive-hsrtech_book.php`](templates/archive-hsrtech_book.php) | `/books/` archive shell |
| [`templates/book-grid.php`](templates/book-grid.php) | Cards used by the archive and shortcode |
| [`templates/partials/document-start.php`](templates/partials/document-start.php) | Classic- and block-theme document opening |
| [`templates/partials/document-end.php`](templates/partials/document-end.php) | Classic- and block-theme document closing |

### Overriding routed templates

Editing bundled templates directly is not update-safe. Copy the template into a child theme and select it with a late `template_include` filter.

For example, copy templates to `your-child-theme/chapterwright/`, then add this to the child theme's `functions.php` or a site-specific plugin:

```php
function mysite_hsrtech_books_templates( $template ) {
    $directory = get_stylesheet_directory() . '/chapterwright/';

    if ( is_singular( 'hsrtech_book' ) ) {
        $custom_template = $directory . 'single-hsrtech_book.php';
    } elseif ( is_singular( 'hsrtech_chapter' ) ) {
        $custom_template = $directory . 'single-hsrtech_chapter.php';
    } elseif ( is_post_type_archive( 'hsrtech_book' ) ) {
        $custom_template = $directory . 'archive-hsrtech_book.php';
    } else {
        return $template;
    }

    return file_exists( $custom_template ) ? $custom_template : $template;
}
add_filter( 'template_include', 'mysite_hsrtech_books_templates', 99 );
```

The plugin does not automatically discover template copies in a theme, so this filter is required. The bundled single and archive templates include the document-start and document-end partials — a copied template can keep using those or switch to the child theme's normal header and footer, as long as the final page ends up with only one of each.

`book-grid.php` is included directly by the archive and shortcode templates and has no separate lookup filter — copy and adapt `archive-hsrtech_book.php` (and the grid it includes) to change the archive layout, or query published `hsrtech_book` posts directly for a custom library elsewhere.

## Content and URL reference

| Item | Identifier |
| --- | --- |
| Book post type | `hsrtech_book` |
| Chapter post type | `hsrtech_chapter` |
| Book archive and single base | `/books/` |
| Chapter base | `/book-chapter/` |
| Chapter parent-book metadata | `_hsrtech_book_id` |
| Chapter section metadata | `_hsrtech_section_id` (points to a row in the `hsrtech_sections` table) |
| Chapter order metadata | `_hsrtech_order` |
| Book subtitle metadata | `_hsrtech_subtitle` |
| Book accent metadata | `_hsrtech_accent` |
| Sections table | `{$wpdb->prefix}hsrtech_sections` (`id`, `book_id`, `name`, `description`, `menu_order`) |
| Library shortcode | `[hsrtech_books]` |

The `/books/` and `/book-chapter/` bases are part of the stable public URL contract and aren't configurable through plugin settings.

Books and Chapters support the block editor, revisions, and the WordPress REST API — including reading and writing `_hsrtech_subtitle`, `_hsrtech_accent`, `_hsrtech_book_id`, `_hsrtech_order`, and `_hsrtech_section_id` through the standard `/wp/v2/hsrtech_book` and `/wp/v2/hsrtech_chapter` endpoints. Books also support author selection. Sections have their own small REST namespace, `chapterwright/v1` (see `admin/rest/`), since they live in a custom table rather than post meta. Use standard WordPress APIs when reading or writing metadata, and validate that `_hsrtech_book_id` points to a Book and `_hsrtech_section_id` points to a section belonging to that same book.

## Capabilities and roles

As of 2.2.0, Books and Chapters have their own WordPress capabilities instead of reusing the generic `edit_posts`/`edit_others_posts`/etc. that every post type gets by default: `edit_hsrtech_book`, `edit_hsrtech_books`, `edit_others_hsrtech_books`, `publish_hsrtech_books`, `delete_hsrtech_book`, and so on (Chapters follow the same pattern with `hsrtech_chapter`/`hsrtech_chapters`). On activation, and automatically the first time an existing site loads the plugin after updating, Administrator, Editor, Author, and Contributor all get exactly the capabilities they'd have had anyway under the old generic behavior — this is a no-op for every site unless you deliberately do something with it.

What it enables: a role that can manage Books and Chapters without also being able to edit every other post type on the site. For example, in `wp-admin` via a role-management plugin, or with WP-CLI:

```bash
wp role create book_editor "Book Editor"
wp cap add book_editor edit_hsrtech_books edit_others_hsrtech_books publish_hsrtech_books read_private_hsrtech_books
wp cap add book_editor edit_hsrtech_chapters edit_others_hsrtech_chapters publish_hsrtech_chapters read_private_hsrtech_chapters
```

Uninstalling the plugin removes every `hsrtech_book`/`hsrtech_chapter` capability from every role, including custom ones — part of the clean sweep described under "Updating and uninstalling."

## Abilities API

As of 2.0.0, the plugin registers a `chapterwright` category and a handful of abilities with WordPress's [Abilities API](https://developer.wordpress.org/apis/abilities-api/) (introduced in WordPress 6.9): `chapterwright/list-books`, `chapterwright/get-book-overview`, `chapterwright/create-section`, `chapterwright/create-chapter`, and `chapterwright/delete-section`. Each one is a thin, permission-checked, schema-validated wrapper around the same functions the admin app and REST controllers already call — see `includes/abilities.php`. This lets AI agents, MCP servers, and other automation discover and use the plugin's core operations without any bespoke integration. Registration is skipped automatically (not an error) on WordPress versions before 6.9, where the Abilities API doesn't exist.

## Updating and uninstalling

Back up the site before updating WordPress, a theme, or any plugin. Custom changes should live in a child theme or site-specific plugin so Chapterwright updates cannot overwrite them.

> [!WARNING]
> Deleting Chapterwright from the **Plugins** screen performs a full clean sweep: every Book and Chapter (and their metadata), the `hsrtech_sections` table, the Book/Chapter capabilities granted to any role (see "Capabilities and roles"), and the plugin's saved settings are all permanently deleted. Nothing is kept. This is deliberate — back up the site first if you want to keep any of it. Deactivating the plugin (without deleting it) does **not** touch your data; only actually removing it from the Plugins screen does.

## Development

The plugin is organized by responsibility:

```text
admin/          Admin-only PHP: the app page, list-table columns, Settings, Help tabs
admin/app/src/  React admin-app and block-editor-sidebar source (built with @wordpress/scripts)
admin/rest/     Custom REST controllers for sections and bulk chapter reordering
assets/         Scoped visitor-facing CSS and JavaScript
blocks/         The Code Snippet Gutenberg block (chapterwright/code-snippet)
includes/       Content types, meta registration, the sections table, upgrades, Abilities API
public/         Template routing, conditional assets, structured data, and shortcodes
templates/      Visitor-facing presentation files
```

Every PHP file is function-based: hooks are registered at the top level when the file loads, and every public entry point is a global `hsrtech_*()` function rather than a class method. `admin/app/src/` is the one part of the plugin written against a build step (JSX, `@wordpress/components`) rather than plain `window.wp.*` globals — a real admin single-page app is impractical to hand-write the way the small Code Snippet block editor script is, so this is a deliberate, scoped exception, not a change to the rest of the plugin's conventions.

Start the local WordPress environment with:

```bash
npm install
npm run env:start
```

Build (or watch) the admin app after changing anything under `admin/app/src/`:

```bash
npm run build   # one-off production build, output to admin/app/build/
npm run start   # rebuilds on every save, for active development
```

`admin/app/build/` is gitignored, like every other generated artifact in this repository, but is expected to exist (run `npm run build` first) in any copy of the plugin actually installed in WordPress — the admin app and block-editor sidebar panel silently don't load without it.

### Translations

Every string in the plugin (PHP and the admin app's JavaScript) is wrapped for translation under the `chapterwright` text domain, and `languages/chapterwright.pot` is the template a translator starts from. See `languages/README.md` for the full translator/maintainer workflow, including how to compile a `.po` into the `.mo` and per-script `.json` files WordPress actually loads.

### Coding standards and tests

```bash
composer install
composer lint        # WordPress Coding Standards, via phpcs.xml.dist

npm run env:start    # if not already running
npm run test:php     # PHPUnit, via a dedicated wp-env test database
```

Both run automatically on every push and pull request via GitHub Actions (`.github/workflows/ci.yml`), alongside `npm run build`. None of `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`, `tests/`, or `.github/` ship in the release zip — they're development-only.

## Changelog

The three most recent releases are below. See [CHANGELOG.md](CHANGELOG.md) for the full history back to 1.0.0.

### 2.8.0

- Fixed the Code Snippet block not showing colored syntax or a working copy button outside of book and chapter pages — the highlighting script only ever loaded there; the block's own frame (language label, copy button chrome) always looked right since it loads independently, but the coloring and copy behavior never ran anywhere else.
- The Code Snippet block can now highlight JSON, alongside PHP, JavaScript, CSS, HTML, and Shell.
- Added three new Code Snippet block options: wrap long lines instead of showing a horizontal scrollbar, show line numbers, and hide the language label.

### 2.7.0

- Added a "Trashed books" screen to **Books & Chapters**: books moved to the trash can now be restored or permanently deleted from there, instead of being sent out to the classic wp-admin Books list to do it.
- Chapters in **Books & Chapters** now show their number (1., 2., 3., …) in front of the title, matching their reading order.
- The table of contents now shows a chapter's excerpt even for a draft chapter (previously excerpt display was published-only, if "Show excerpt in table of contents" is on and the chapter has one), and a draft chapter keeps its real number instead of having "Draft" printed in its place — "Draft" is now a separate label next to the title, still announced to screen readers.
- Chapters in **Books & Chapters** now show their excerpt below the title too, for drafts and published chapters alike, matching the same "Show excerpt" setting.
- Fixed a chapter, section, or book occasionally staying missing from its list right after being added successfully — root-caused to a caching layer in front of WordPress on some hosts serving a stale response for a short time afterward, not anything wrong with the save itself. Every GET request the admin app makes now includes a unique value, so it can never be served a cached hit.

### 2.6.1

- Fixed a book, section, or chapter that had already been deleted or changed server-side (e.g. by a request that actually succeeded despite looking like it failed) staying stuck in the admin app's list — every retry just repeated the same error with nothing visibly changing. Delete, save, and reorder actions in **Books & Chapters** now re-sync their list from the server after a failure, not just after success.
- Fixed a newly added chapter or section sometimes not appearing right after being added, with no error shown — an immediate re-fetch after creating one could occasionally lag behind on some hosts. **Books & Chapters** now shows what was just created directly, instead of relying on that re-fetch.

## License

Chapterwright is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

Copyright © Alok Jain.

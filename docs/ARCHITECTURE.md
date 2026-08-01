# Architecture

Make a Book follows a small component architecture so WordPress hooks remain close to the code that serves them.

## Request lifecycle

1. `make-a-book.php` defines version and path constants, loads the coordinator, and registers activation/deactivation callbacks.
2. `Make_A_Book` loads and instantiates the content, administrator, and public components.
3. `Make_A_Book_Content_Types` registers Books and Chapters on `init` and exposes the canonical ordered-chapter query.
4. `Make_A_Book_Admin` registers editor panels, verifies save requests, sanitizes metadata, and extends the Chapters list table.
5. `Make_A_Book_Public` conditionally loads CSS, chooses front-end templates, and renders the library shortcode.

## Content model

A Book is a `mab_book` post. A Chapter is a `mab_chapter` post related to its parent through `_mab_book_id`. Sections are deliberately stored as chapter text metadata rather than a taxonomy so each book may use its own section labels without creating global terms.

Chapter reading order is numeric metadata. The publication date provides deterministic ordering when two chapters use the same number.

## Presentation

Plugin templates own the main content area. Document partials use the active theme's header/footer files for classic themes and render block template parts inside a complete WordPress document for block themes. CSS is scoped with `mab-` classes and loaded only on plugin routes or pages containing the shortcode.

The chapter reader uses a deliberately narrow editorial measure, serif body typography, semantic heading rhythm, an estimated reading time, and a visual-only scroll progress bar. The progress bar is hidden from assistive technology because continuously announcing scroll changes would reduce accessibility.

## Compatibility

`Make_A_Book::get_chapters()` remains as a compatibility proxy even though the implementation belongs to `Make_A_Book_Content_Types`. Existing templates or extensions can continue calling the original public method.

## Development fixtures

`.wp-env.json` mounts this repository into WordPress 6.8 on PHP 7.4. Local fixture data may be used during development, while `tests/smoke.php` verifies post type registration, book count, chapter relationships, ordering and required metadata.

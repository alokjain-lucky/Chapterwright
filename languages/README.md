# Translations

`make-a-book.pot` is the translation template: one entry per translatable
string in the plugin (PHP, via `__()`/`_e()`/etc., and the admin app's
JavaScript, via `@wordpress/i18n`). It has no translations in it — it's the
starting point for a translator.

## For a translator

1. Copy `make-a-book.pot` to `make-a-book-{locale}.po` (for example
   `make-a-book-fr_FR.po`).
2. Fill in each `msgstr ""` with the translated string, using a PO editor
   (e.g. [Poedit](https://poedit.net/)) or by hand.
3. Compile it to `make-a-book-{locale}.mo` (Poedit does this automatically
   on save; via WP-CLI: `wp i18n make-mo make-a-book-{locale}.po`).
4. Place both the `.po` and `.mo` file in this `languages/` directory.
   `load_plugin_textdomain()` (see `mab_load_textdomain()` in
   `make-a-book.php`) picks up the `.mo` file automatically based on the
   site's locale.

## Admin app / editor sidebar strings (JavaScript)

The admin app and block-editor sidebar panel are JavaScript, translated via
`@wordpress/i18n` and `wp_set_script_translations()` (see `admin/app.php`).
These strings are included in `make-a-book.pot` too, but WordPress loads
their translations from a separate JSON file per script, not the `.mo`
file above. Generate those with WP-CLI after producing the `.po`:

```
wp i18n make-json languages/make-a-book-{locale}.po languages/ --no-purge
```

This produces files like `make-a-book-{locale}-{md5}.json` — one per script
handle (`make-a-book-app`, `make-a-book-editor-sidebar`) — which
`wp_set_script_translations()` finds automatically.

## Regenerating the .pot

This repository has no WP-CLI available in its dev environment, so
`make-a-book.pot` was generated with a small regex-based scanner
(kept outside this repo, not part of the shipped plugin) rather than
`wp i18n make-pot`. If you have WP-CLI available, prefer regenerating it
properly instead:

```
wp i18n make-pot . languages/make-a-book.pot --domain=make-a-book
```

Re-run this (or the equivalent) after adding or changing any translatable
string, and note it in the version's changelog entry like any other change.

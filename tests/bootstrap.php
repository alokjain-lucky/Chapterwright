<?php
/**
 * PHPUnit bootstrap for the Make a Book test suite.
 *
 * Standard WordPress plugin test bootstrap: load the WP core test library
 * (provisioned by `wp-env` — see .wp-env.json's `testsPort`, which is what
 * makes `wp-env run tests-cli` available), load this plugin directly rather
 * than through the normal activation flow, then hand off to WP's own
 * bootstrap. Run with `npm run test:php` (see package.json), which is a
 * thin wrapper around `wp-env run tests-cli ... phpunit`.
 *
 * @package Make_A_Book
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Test bootstrap, mirrors the standard WP plugin test-suite boilerplate exactly; not part of the plugin's own namespace.

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// The WP core test suite requires the PHPUnit Polyfills library (shims
// PHPUnit API differences across versions) to be locatable before its own
// bootstrap runs — see yoast/phpunit-polyfills in composer.json, installed
// via `composer install` (see .github/workflows/ci.yml's "PHP unit tests"
// job and README.md's local setup instructions). Defining the constant
// directly (rather than putenv(), which WPCS flags as a discouraged
// runtime-configuration function) works because WP core's own bootstrap
// only ever tries to set it from getenv(), which we're not using — so
// there's nothing for it to conflict with.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin under test.
 *
 * Hooked to `muplugins_loaded` (before WP's normal `plugins_loaded`/
 * `init` sequence runs in the test environment), which is how the WP test
 * suite expects a plugin to be loaded — there is no "activate this plugin"
 * step here, so anything the plugin depends on happening at activation
 * (the sections table, capability grants) is done explicitly below instead.
 */
function _mab_manually_load_plugin() {
	require dirname( __DIR__ ) . '/make-a-book.php';
}
tests_add_filter( 'muplugins_loaded', '_mab_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

// mab_activate() (register_activation_hook) never runs under this bootstrap
// — the plugin is loaded directly above, not "activated" — so do the two
// things every test in this suite needs by hand: the mab_sections table,
// and the Book/Chapter role capabilities (see includes/content-types.php
// and includes/upgrade.php for what these do and why).
mab_create_sections_table();
mab_add_capabilities_to_roles();

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals

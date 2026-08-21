<?php
/**
 * Lightweight regression test for the non-destructive theme-settings migration.
 *
 * Run with: php tests/theme-settings-migration-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );

class WP_Post {
	public $post_content;

	public function __construct( $post_content = '' ) {
		$this->post_content = $post_content;
	}
}

$cni_test_options = array(
	'theme_mods_lightning-child' => array(
		'shared_setting'     => 'legacy',
		'legacy_only_setting' => 'copied',
		'custom_css_post_id' => 10,
	),
	'theme_mods_cni-lightning-child' => array(
		'shared_setting' => 'new-theme-value',
	),
);
$cni_test_transients = array();
$cni_test_custom_css = array(
	'lightning-child' => new WP_Post( '.legacy { color: red; }' ),
);

function add_action( $hook, $callback, $priority = 10 ) {
}

function get_stylesheet() {
	return 'cni-lightning-child';
}

function get_option( $name, $default = false ) {
	global $cni_test_options;
	return array_key_exists( $name, $cni_test_options ) ? $cni_test_options[ $name ] : $default;
}

function update_option( $name, $value ) {
	global $cni_test_options;
	$cni_test_options[ $name ] = $value;
	return true;
}

function set_transient( $name, $value, $expiration ) {
	global $cni_test_transients;
	$cni_test_transients[ $name ] = $value;
	return true;
}

function get_transient( $name ) {
	global $cni_test_transients;
	return isset( $cni_test_transients[ $name ] ) ? $cni_test_transients[ $name ] : false;
}

function delete_transient( $name ) {
	global $cni_test_transients;
	unset( $cni_test_transients[ $name ] );
	return true;
}

function current_user_can( $capability ) {
	return true;
}

function __( $text, $domain ) {
	return $text;
}

function esc_html( $text ) {
	return $text;
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_get_custom_css_post( $stylesheet ) {
	global $cni_test_custom_css;
	return isset( $cni_test_custom_css[ $stylesheet ] ) ? $cni_test_custom_css[ $stylesheet ] : null;
}

function wp_get_custom_css( $stylesheet ) {
	$post = wp_get_custom_css_post( $stylesheet );
	return $post instanceof WP_Post ? $post->post_content : '';
}

function wp_update_custom_css_post( $css, $args ) {
	global $cni_test_custom_css;
	$cni_test_custom_css[ $args['stylesheet'] ] = new WP_Post( $css );
	return $cni_test_custom_css[ $args['stylesheet'] ];
}

function is_wp_error( $thing ) {
	return false;
}

require dirname( __DIR__ ) . '/inc/theme-settings-migration.php';

function cni_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

cni_lightning_child_migrate_legacy_theme_settings();

cni_test_assert(
	'legacy' === $cni_test_options['theme_mods_lightning-child']['shared_setting'],
	'Legacy theme mods must remain unchanged.'
);
cni_test_assert(
	'new-theme-value' === $cni_test_options['theme_mods_cni-lightning-child']['shared_setting'],
	'Existing new-theme values must not be overwritten.'
);
cni_test_assert(
	'copied' === $cni_test_options['theme_mods_cni-lightning-child']['legacy_only_setting'],
	'Missing new-theme values must be copied.'
);
cni_test_assert(
	! isset( $cni_test_options['theme_mods_cni-lightning-child']['custom_css_post_id'] ),
	'Legacy Custom CSS pointer must not be copied.'
);
cni_test_assert(
	'.legacy { color: red; }' === $cni_test_custom_css['lightning-child']->post_content,
	'Legacy Custom CSS must remain unchanged.'
);
cni_test_assert(
	'.legacy { color: red; }' === $cni_test_custom_css['cni-lightning-child']->post_content,
	'Custom CSS must be copied to the new stylesheet.'
);
cni_test_assert(
	'completed' === $cni_test_options['cni_lightning_child_settings_migration_v1']['status'],
	'A versioned, product-specific completion option must be stored.'
);

$cni_test_options['theme_mods_lightning-child']['later_legacy_setting'] = 'must-not-copy';
cni_lightning_child_migrate_legacy_theme_settings();
cni_test_assert(
	! isset( $cni_test_options['theme_mods_cni-lightning-child']['later_legacy_setting'] ),
	'Completed migration must not run a second time.'
);

echo "Theme settings migration test passed.\n";

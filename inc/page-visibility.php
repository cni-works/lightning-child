<?php
/**
 * Per-page visibility settings for Lightning elements.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the supported visibility meta fields and their labels.
 *
 * @return array<string, string>
 */
function lightning_child_get_page_visibility_fields() {
	return array(
		'_lightning_child_hide_site_header' => __( 'ヘッダーを表示しない', 'cni-lightning-child' ),
		'_lightning_child_hide_page_header' => __( 'ページヘッダーを表示しない', 'cni-lightning-child' ),
		'_lightning_child_hide_breadcrumb'  => __( 'パンくずリストを表示しない', 'cni-lightning-child' ),
		'_lightning_child_hide_site_footer' => __( 'フッターを表示しない', 'cni-lightning-child' ),
	);
}

/**
 * Sanitize a checkbox value as a boolean.
 *
 * @param mixed $value Submitted value.
 * @return bool
 */
function lightning_child_sanitize_boolean( $value ) {
	return rest_sanitize_boolean( $value );
}

/**
 * Check whether the current user may edit page visibility metadata.
 *
 * @param bool   $allowed  Existing authorization result.
 * @param string $meta_key Current meta key.
 * @param int    $post_id  Current post ID.
 * @return bool
 */
function lightning_child_auth_page_visibility_meta( $allowed, $meta_key, $post_id ) {
	unset( $allowed, $meta_key );
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Register page visibility metadata.
 *
 * @return void
 */
function lightning_child_register_page_visibility_meta() {
	foreach ( lightning_child_get_page_visibility_fields() as $meta_key => $label ) {
		register_post_meta(
			'page',
			$meta_key,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => 'lightning_child_sanitize_boolean',
				'auth_callback'     => 'lightning_child_auth_page_visibility_meta',
			)
		);
	}
}
add_action( 'init', 'lightning_child_register_page_visibility_meta' );

/**
 * Load the page visibility panel in the block editor.
 *
 * @return void
 */
function lightning_child_enqueue_page_visibility_panel() {
	$screen = get_current_screen();

	if ( ! $screen || 'page' !== $screen->post_type || ! $screen->is_block_editor ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/page-visibility-panel.js';

	wp_enqueue_script(
		'lightning-child-page-visibility-panel',
		get_stylesheet_directory_uri() . '/assets/js/page-visibility-panel.js',
		array( 'wp-components', 'wp-core-data', 'wp-data', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);

	wp_localize_script(
		'lightning-child-page-visibility-panel',
		'lightningChildPageVisibility',
		array(
			'panelTitle' => __( 'Lightning 表示設定', 'cni-lightning-child' ),
			'fields'     => lightning_child_get_page_visibility_fields(),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'lightning_child_enqueue_page_visibility_panel' );

/**
 * Register a fallback meta box for the Classic Editor.
 *
 * @return void
 */
function lightning_child_add_page_visibility_meta_box() {
	add_meta_box(
		'lightning_child_page_visibility',
		__( 'Lightning 表示設定', 'cni-lightning-child' ),
		'lightning_child_render_page_visibility_meta_box',
		'page',
		'side',
		'default',
		array( '__back_compat_meta_box' => true )
	);
}
add_action( 'add_meta_boxes_page', 'lightning_child_add_page_visibility_meta_box' );

/**
 * Render the Classic Editor meta box.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function lightning_child_render_page_visibility_meta_box( $post ) {
	wp_nonce_field( 'lightning_child_save_page_visibility', 'lightning_child_page_visibility_nonce' );

	echo '<fieldset>';
	foreach ( lightning_child_get_page_visibility_fields() as $meta_key => $label ) {
		printf(
			'<p><label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label></p>',
			esc_attr( $meta_key ),
			checked( (bool) get_post_meta( $post->ID, $meta_key, true ), true, false ),
			esc_html( $label )
		);
	}
	echo '</fieldset>';
}

/**
 * Save page visibility settings from the Classic Editor.
 *
 * @param int $post_id Current post ID.
 * @return void
 */
function lightning_child_save_page_visibility( $post_id ) {
	$nonce = isset( $_POST['lightning_child_page_visibility_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lightning_child_page_visibility_nonce'] ) )
		: '';

	if ( ! wp_verify_nonce( $nonce, 'lightning_child_save_page_visibility' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( lightning_child_get_page_visibility_fields() as $meta_key => $label ) {
		$value = isset( $_POST[ $meta_key ] );
		update_post_meta( $post_id, $meta_key, $value );
	}
}
add_action( 'save_post_page', 'lightning_child_save_page_visibility' );

/**
 * Determine whether an element is hidden on the current fixed page.
 *
 * @param string $meta_key Visibility meta key.
 * @return bool
 */
function lightning_child_is_page_element_hidden( $meta_key ) {
	if ( ! is_page() ) {
		return false;
	}

	$page_id = get_queried_object_id();
	return $page_id > 0 && (bool) get_post_meta( $page_id, $meta_key, true );
}

/**
 * Filter a Lightning visibility flag using page metadata.
 *
 * @param bool   $is_visible Current visibility.
 * @param string $meta_key   Visibility meta key.
 * @return bool
 */
function lightning_child_filter_page_element_visibility( $is_visible, $meta_key ) {
	if ( lightning_child_is_page_element_hidden( $meta_key ) ) {
		return false;
	}

	return $is_visible;
}

/**
 * Filter the Lightning site header visibility.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_site_header_visibility( $is_visible ) {
	return lightning_child_filter_page_element_visibility( $is_visible, '_lightning_child_hide_site_header' );
}
add_filter( 'lightning_is_site_header', 'lightning_child_filter_site_header_visibility' );

/**
 * Filter the Lightning page header visibility.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_page_header_visibility( $is_visible ) {
	return lightning_child_filter_page_element_visibility( $is_visible, '_lightning_child_hide_page_header' );
}
add_filter( 'lightning_is_page_header', 'lightning_child_filter_page_header_visibility' );

/**
 * Filter the Lightning breadcrumb visibility.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_breadcrumb_visibility( $is_visible ) {
	return lightning_child_filter_page_element_visibility( $is_visible, '_lightning_child_hide_breadcrumb' );
}
add_filter( 'lightning_is_breadcrumb', 'lightning_child_filter_breadcrumb_visibility' );

/**
 * Filter the Lightning site footer visibility.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_site_footer_visibility( $is_visible ) {
	return lightning_child_filter_page_element_visibility( $is_visible, '_lightning_child_hide_site_footer' );
}
add_filter( 'lightning_is_site_footer', 'lightning_child_filter_site_footer_visibility' );

/**
 * Add a body class when the whole footer area is hidden.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function lightning_child_add_page_visibility_body_class( $classes ) {
	if ( lightning_child_is_page_element_hidden( '_lightning_child_hide_site_footer' ) ) {
		$classes[] = 'lightning-child-hide-site-footer';
	}

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_page_visibility_body_class' );

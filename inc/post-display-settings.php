<?php
/**
 * Singular post display settings by post type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return post types supported by the display settings.
 *
 * @return WP_Post_Type[]
 */
function lightning_child_get_display_setting_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'objects'
	);

	unset( $post_types['attachment'], $post_types['page'] );

	return apply_filters( 'lightning_child_display_setting_post_types', $post_types );
}

/**
 * Build a theme modification name for a post type and display item.
 *
 * @param string $post_type Post type name.
 * @param string $item      Display item name.
 * @return string
 */
function lightning_child_get_post_display_setting_name( $post_type, $item ) {
	return 'lightning_child_display_' . sanitize_key( $post_type ) . '_' . sanitize_key( $item );
}

/**
 * Determine whether a display item is enabled for a post type.
 *
 * @param string $post_type Post type name.
 * @param string $item      Display item name.
 * @return bool
 */
function lightning_child_is_post_display_item_enabled( $post_type, $item ) {
	$setting_name = lightning_child_get_post_display_setting_name( $post_type, $item );
	return rest_sanitize_boolean( get_theme_mod( $setting_name, true ) );
}

/**
 * Register singular post display settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_post_display_settings( $wp_customize ) {
	$post_types = lightning_child_get_display_setting_post_types();

	if ( empty( $post_types ) ) {
		return;
	}

	$wp_customize->add_panel(
		'lightning_child_post_display',
		array(
			'title'       => __( 'Lightning 子テーマ 投稿表示', 'lightning-child' ),
			'description' => __( 'Lightning標準の詳細ページに表示する情報を投稿タイプごとに設定します。', 'lightning-child' ),
			'priority'    => 166,
		)
	);

	$items = array(
		'published' => __( '公開日を表示する', 'lightning-child' ),
		'updated'   => __( '更新日を表示する', 'lightning-child' ),
		'author'    => __( '投稿者名と投稿者画像を表示する', 'lightning-child' ),
		'next_prev' => __( '前の記事・次の記事を表示する', 'lightning-child' ),
	);

	foreach ( $post_types as $post_type ) {
		$section_id = 'lightning_child_post_display_' . sanitize_key( $post_type->name );

		$wp_customize->add_section(
			$section_id,
			array(
				'title'       => $post_type->labels->name,
				'description' => sprintf(
					/* translators: %s: post type label. */
					__( '%sの詳細ページに適用します。独自テンプレートを使用する投稿タイプでは反映されない場合があります。', 'lightning-child' ),
					$post_type->labels->singular_name
				),
				'panel'       => 'lightning_child_post_display',
			)
		);

		foreach ( $items as $item => $label ) {
			$setting_name = lightning_child_get_post_display_setting_name( $post_type->name, $item );

			$wp_customize->add_setting(
				$setting_name,
				array(
					'default'           => true,
					'sanitize_callback' => 'lightning_child_sanitize_boolean',
				)
			);

			$wp_customize->add_control(
				$setting_name,
				array(
					'label'   => $label,
					'section' => $section_id,
					'type'    => 'checkbox',
				)
			);
		}
	}
}
add_action( 'customize_register', 'lightning_child_customize_post_display_settings' );

/**
 * Apply the configured publication, update, and author visibility.
 *
 * @param array<string, mixed> $options Lightning entry meta options.
 * @return array<string, mixed>
 */
function lightning_child_filter_entry_meta_options( $options ) {
	if ( ! is_singular() || is_page() ) {
		return $options;
	}

	$post_type = get_post_type();
	if ( ! $post_type ) {
		return $options;
	}

	if ( ! lightning_child_is_post_display_item_enabled( $post_type, 'published' ) ) {
		$options['published'] = false;
	}

	if ( ! lightning_child_is_post_display_item_enabled( $post_type, 'updated' ) ) {
		$options['updated'] = false;
	}

	if ( ! lightning_child_is_post_display_item_enabled( $post_type, 'author' ) ) {
		$options['author_name']  = false;
		$options['author_image'] = false;
	}

	return $options;
}
add_filter( 'lightning_get_entry_meta_options', 'lightning_child_filter_entry_meta_options' );

/**
 * Apply the configured previous and next post visibility.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_next_prev_visibility( $is_visible ) {
	if ( ! $is_visible || ! is_singular() || is_page() ) {
		return $is_visible;
	}

	$post_type = get_post_type();
	if ( $post_type && ! lightning_child_is_post_display_item_enabled( $post_type, 'next_prev' ) ) {
		return false;
	}

	return $is_visible;
}
add_filter( 'lightning_is_next_prev', 'lightning_child_filter_next_prev_visibility' );

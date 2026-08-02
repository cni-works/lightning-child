<?php
/**
 * Archive query and loop display settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return post types that have archive screens.
 *
 * @return WP_Post_Type[]
 */
function lightning_child_get_archive_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'objects'
	);

	foreach ( $post_types as $name => $post_type ) {
		if ( 'post' !== $name && empty( $post_type->has_archive ) ) {
			unset( $post_types[ $name ] );
		}
	}

	return apply_filters( 'lightning_child_archive_post_types', $post_types );
}

/**
 * Build an archive setting name.
 *
 * @param string $post_type Post type name.
 * @param string $field     Field name.
 * @return string
 */
function lightning_child_get_archive_setting_name( $post_type, $field ) {
	return 'lightning_child_archive_' . sanitize_key( $post_type ) . '_' . sanitize_key( $field );
}

/**
 * Sanitize the archive display type.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_archive_display_type( $value ) {
	$choices = array( 'standard', 'card', 'text' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'standard';
}

/**
 * Sanitize archive posts per page.
 *
 * Zero keeps the WordPress Reading setting.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_archive_posts_per_page( $value ) {
	if ( ! is_numeric( $value ) ) {
		return 0;
	}

	return min( 50, max( 0, absint( $value ) ) );
}

/**
 * Register archive settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_archive_settings( $wp_customize ) {
	$panel_id = 'lightning_child_archive_settings';
	$wp_customize->add_panel(
		$panel_id,
		array(
			'title'       => __( 'Lightning アーカイブ設定', 'lightning-child' ),
			'description' => __( '投稿とカスタム投稿の一覧件数、表示形式、表示要素を設定します。', 'lightning-child' ),
			'priority'    => 171,
		)
	);

	foreach ( lightning_child_get_archive_post_types() as $post_type ) {
		$post_type_name = sanitize_key( $post_type->name );
		$section_id     = 'lightning_child_archive_' . $post_type_name;
		$wp_customize->add_section(
			$section_id,
			array(
				'title' => $post_type->labels->name,
				'panel' => $panel_id,
			)
		);

		$posts_per_page_setting = lightning_child_get_archive_setting_name( $post_type_name, 'posts_per_page' );
		$wp_customize->add_setting(
			$posts_per_page_setting,
			array(
				'default'           => 0,
				'sanitize_callback' => 'lightning_child_sanitize_archive_posts_per_page',
			)
		);
		$wp_customize->add_control(
			$posts_per_page_setting,
			array(
				'label'       => __( '表示件数', 'lightning-child' ),
				'description' => __( '1〜50件で指定します。0の場合は「設定 → 表示設定」の件数を使用します。', 'lightning-child' ),
				'section'     => $section_id,
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 50,
					'step' => 1,
				),
			)
		);

		$display_type_setting = lightning_child_get_archive_setting_name( $post_type_name, 'display_type' );
		$wp_customize->add_setting(
			$display_type_setting,
			array(
				'default'           => 'standard',
				'sanitize_callback' => 'lightning_child_sanitize_archive_display_type',
			)
		);
		$wp_customize->add_control(
			$display_type_setting,
			array(
				'label'   => __( '表示タイプ', 'lightning-child' ),
				'section' => $section_id,
				'type'    => 'radio',
				'choices' => array(
					'standard' => __( 'Lightning標準', 'lightning-child' ),
					'card'     => __( 'カード（PC3列）', 'lightning-child' ),
					'text'     => __( 'テキスト1カラム', 'lightning-child' ),
				),
			)
		);

		$element_settings = array(
			'taxonomies' => array(
				'label'   => __( '分類を表示する', 'lightning-child' ),
				'default' => false,
			),
			'author'     => array(
				'label'   => __( '投稿者を表示する', 'lightning-child' ),
				'default' => false,
			),
			'date'       => array(
				'label'   => __( '日付を表示する', 'lightning-child' ),
				'default' => true,
			),
			'modified'   => array(
				'label'   => __( '更新日を表示する', 'lightning-child' ),
				'default' => false,
			),
			'new'        => array(
				'label'   => __( '新着表示を使用する', 'lightning-child' ),
				'default' => true,
			),
		);

		foreach ( $element_settings as $field => $setting ) {
			$setting_name = lightning_child_get_archive_setting_name( $post_type_name, $field );
			$wp_customize->add_setting(
				$setting_name,
				array(
					'default'           => $setting['default'],
					'sanitize_callback' => 'lightning_child_sanitize_boolean',
				)
			);
			$wp_customize->add_control(
				$setting_name,
				array(
					'label'   => $setting['label'],
					'section' => $section_id,
					'type'    => 'checkbox',
				)
			);
		}
	}
}
add_action( 'customize_register', 'lightning_child_customize_archive_settings' );

/**
 * Return the post type represented by the current archive request.
 *
 * @return string
 */
function lightning_child_get_current_archive_post_type() {
	if ( is_home() || is_category() || is_tag() || is_date() || is_author() ) {
		return 'post';
	}

	if ( is_post_type_archive() ) {
		$queried_object = get_queried_object();
		return $queried_object instanceof WP_Post_Type ? sanitize_key( $queried_object->name ) : '';
	}

	if ( is_tax() ) {
		$queried_object = get_queried_object();
		$taxonomy       = $queried_object instanceof WP_Term ? get_taxonomy( $queried_object->taxonomy ) : false;
		return lightning_child_get_taxonomy_archive_post_type( $taxonomy );
	}

	return '';
}

/**
 * Apply the configured posts-per-page value to frontend main archives.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function lightning_child_apply_archive_posts_per_page( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_home() || $query->is_archive() ) ) {
		return;
	}

	$post_type      = lightning_child_get_current_archive_post_type();
	if ( ! $post_type ) {
		return;
	}

	$posts_per_page = lightning_child_sanitize_archive_posts_per_page(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'posts_per_page' ), 0 )
	);
	if ( 0 < $posts_per_page ) {
		$query->set( 'posts_per_page', $posts_per_page );
	}
}
add_action( 'pre_get_posts', 'lightning_child_apply_archive_posts_per_page' );

/**
 * Apply archive layout and metadata settings to Lightning's post component.
 *
 * @param array<string, mixed> $options Post component options.
 * @param WP_Post              $post    Current post.
 * @return array<string, mixed>
 */
function lightning_child_filter_archive_post_options( $options, $post ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! ( is_home() || is_archive() ) ) {
		return $options;
	}

	$post_type = lightning_child_get_current_archive_post_type();
	if ( ! $post_type || ! $post instanceof WP_Post ) {
		return $options;
	}

	$display_type = lightning_child_sanitize_archive_display_type(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'display_type' ), 'standard' )
	);
	if ( 'card' === $display_type ) {
		$options['layout']          = 'card';
		$options['display_image']   = true;
		$options['display_excerpt'] = true;
		$options['display_btn']    = false;
		$options['class_outer']    = 'vk_post-col-xs-12 vk_post-col-sm-6 vk_post-col-lg-4';
	} elseif ( 'text' === $display_type ) {
		$options['layout']          = 'postListText';
		$options['display_image']   = false;
		$options['display_excerpt'] = false;
		$options['display_btn']     = false;
		$options['class_outer']     = 'vk_post-col-xs-12 vk_post-col-sm-12 vk_post-col-lg-12';
	}

	$options['display_image_overlay_term'] = false;
	$options['display_taxonomies'] = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'taxonomies' ), false )
	);
	$options['display_author'] = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'author' ), false )
	);
	$options['display_date'] = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'date' ), true )
	);
	$options['display_modified'] = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'modified' ), false )
	);
	$options['display_new'] = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'new' ), true )
	);

	return $options;
}
add_filter( 'vk_post_options', 'lightning_child_filter_archive_post_options', 20, 2 );

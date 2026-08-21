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
	$choices = array( 'standard', 'card', 'media', 'text' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'standard';
}

/**
 * Sanitize an archive card design.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_archive_card_design( $value ) {
	$choices = array( 'standard', 'overlay', 'date_corner', 'text_card' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'standard';
}

/**
 * Sanitize an archive card column count.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_archive_columns( $value ) {
	$value = absint( $value );
	return in_array( $value, array( 1, 2, 3, 4 ), true ) ? $value : 1;
}

/**
 * Sanitize an archive image ratio.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_archive_image_ratio( $value ) {
	$choices = array( '16_9', '3_2', '2_1' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : '16_9';
}

/**
 * Sanitize an archive hover effect.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_archive_hover_effect( $value ) {
	$choices = array( 'none', 'darken', 'zoom', 'lift' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'none';
}

/**
 * Sanitize an archive card radius.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_archive_card_radius( $value ) {
	return min( 32, max( 0, absint( $value ) ) );
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
			'title'       => __( 'Lightning アーカイブ設定', 'cni-lightning-child' ),
			'description' => __( '投稿とカスタム投稿の一覧件数、表示形式、表示要素を設定します。', 'cni-lightning-child' ),
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
				'label'       => __( '表示件数', 'cni-lightning-child' ),
				'description' => __( '1〜50件で指定します。0の場合は「設定 → 表示設定」の件数を使用します。', 'cni-lightning-child' ),
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
				'label'   => __( '表示タイプ', 'cni-lightning-child' ),
				'section' => $section_id,
				'type'    => 'radio',
				'choices' => array(
					'standard'   => __( 'Lightning標準', 'cni-lightning-child' ),
					'card'       => __( '縦カード', 'cni-lightning-child' ),
					'media'      => __( 'メディア', 'cni-lightning-child' ),
					'text'       => __( 'テキストリスト', 'cni-lightning-child' ),
				),
			)
		);

		$select_settings = array(
			'card_design' => array(
				'label'       => __( 'カードデザイン', 'cni-lightning-child' ),
				'description' => __( '縦カードの見た目を選択します。', 'cni-lightning-child' ),
				'default'     => 'standard',
				'sanitize'    => 'lightning_child_sanitize_archive_card_design',
				'choices'     => array(
					'standard'    => __( '標準カード', 'cni-lightning-child' ),
					'overlay'     => __( '画像全面＋下部オーバーレイ', 'cni-lightning-child' ),
					'date_corner' => __( '日付コーナー＋画像・本文', 'cni-lightning-child' ),
					'text_card'   => __( 'テキストカード（画像なし）', 'cni-lightning-child' ),
				),
			),
			'columns_pc'  => array(
				'label'       => __( 'PCのカラム数', 'cni-lightning-child' ),
				'description' => __( '縦カード、メディア、テキストへ適用します。', 'cni-lightning-child' ),
				'default'     => 3,
				'sanitize'    => 'lightning_child_sanitize_archive_columns',
				'choices'     => array(
					1 => __( '1列', 'cni-lightning-child' ),
					2 => __( '2列', 'cni-lightning-child' ),
					3 => __( '3列', 'cni-lightning-child' ),
					4 => __( '4列', 'cni-lightning-child' ),
				),
			),
			'columns_tablet' => array(
				'label'       => __( 'タブレットのカラム数', 'cni-lightning-child' ),
				'description' => __( '576px以上、992px未満の幅へ適用します。', 'cni-lightning-child' ),
				'default'     => 2,
				'sanitize'    => 'lightning_child_sanitize_archive_columns',
				'choices'     => array(
					1 => __( '1列', 'cni-lightning-child' ),
					2 => __( '2列', 'cni-lightning-child' ),
					3 => __( '3列', 'cni-lightning-child' ),
					4 => __( '4列', 'cni-lightning-child' ),
				),
			),
			'columns_mobile' => array(
				'label'       => __( 'スマートフォンのカラム数', 'cni-lightning-child' ),
				'description' => __( '576px未満の幅へ適用します。', 'cni-lightning-child' ),
				'default'     => 1,
				'sanitize'    => 'lightning_child_sanitize_archive_columns',
				'choices'     => array(
					1 => __( '1列', 'cni-lightning-child' ),
					2 => __( '2列', 'cni-lightning-child' ),
					3 => __( '3列', 'cni-lightning-child' ),
					4 => __( '4列', 'cni-lightning-child' ),
				),
			),
			'image_ratio' => array(
				'label'       => __( '縦カードの画像比率', 'cni-lightning-child' ),
				'description' => __( '縦に高くなりすぎない横長比率から選択します。', 'cni-lightning-child' ),
				'default'     => '16_9',
				'sanitize'    => 'lightning_child_sanitize_archive_image_ratio',
				'choices'     => array(
					'16_9' => '16:9',
					'3_2'  => '3:2',
					'2_1'  => '2:1',
				),
			),
			'hover_effect' => array(
				'label'       => __( 'マウスオーバー時の動き', 'cni-lightning-child' ),
				'description' => __( 'キーボード操作時も同等の状態を表示し、動きを減らす設定を尊重します。', 'cni-lightning-child' ),
				'default'     => 'none',
				'sanitize'    => 'lightning_child_sanitize_archive_hover_effect',
				'choices'     => array(
					'none'   => __( 'なし', 'cni-lightning-child' ),
					'darken' => __( '画像を暗くする', 'cni-lightning-child' ),
					'zoom'   => __( '画像を拡大する', 'cni-lightning-child' ),
					'lift'   => __( 'カードを少し持ち上げる', 'cni-lightning-child' ),
				),
			),
		);

		foreach ( $select_settings as $field => $setting ) {
			$setting_name = lightning_child_get_archive_setting_name( $post_type_name, $field );
			$wp_customize->add_setting(
				$setting_name,
				array(
					'default'           => $setting['default'],
					'sanitize_callback' => $setting['sanitize'],
				)
			);
			$wp_customize->add_control(
				$setting_name,
				array(
					'label'       => $setting['label'],
					'description' => $setting['description'],
					'section'     => $section_id,
					'type'        => 'select',
					'choices'     => $setting['choices'],
				)
			);
		}

		$radius_setting = lightning_child_get_archive_setting_name( $post_type_name, 'card_radius' );
		$wp_customize->add_setting(
			$radius_setting,
			array(
				'default'           => 8,
				'sanitize_callback' => 'lightning_child_sanitize_archive_card_radius',
			)
		);
		$wp_customize->add_control(
			$radius_setting,
			array(
				'label'       => __( 'カードの角丸（px）', 'cni-lightning-child' ),
				'section'     => $section_id,
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 32,
					'step' => 1,
				),
			)
		);

		$element_settings = array(
			'image'      => array(
				'label'   => __( 'アイキャッチ画像を表示する（オーバーレイ型では常に使用）', 'cni-lightning-child' ),
				'default' => true,
			),
			'excerpt'    => array(
				'label'   => __( '抜粋を表示する', 'cni-lightning-child' ),
				'default' => true,
			),
			'taxonomies' => array(
				'label'   => __( '分類を表示する', 'cni-lightning-child' ),
				'default' => false,
			),
			'author'     => array(
				'label'   => __( '投稿者を表示する', 'cni-lightning-child' ),
				'default' => false,
			),
			'date'       => array(
				'label'   => __( '日付を表示する', 'cni-lightning-child' ),
				'default' => true,
			),
			'modified'   => array(
				'label'   => __( '更新日を表示する', 'cni-lightning-child' ),
				'default' => false,
			),
			'new'        => array(
				'label'   => __( '新着表示を使用する', 'cni-lightning-child' ),
				'default' => true,
			),
			'card_border' => array(
				'label'   => __( 'カードの枠線を表示する', 'cni-lightning-child' ),
				'default' => true,
			),
			'card_shadow' => array(
				'label'   => __( 'カードの影を表示する', 'cni-lightning-child' ),
				'default' => false,
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
 * Load conditional visibility handling for archive Customizer controls.
 *
 * @return void
 */
function lightning_child_enqueue_archive_customizer_controls() {
	$script_path = get_stylesheet_directory() . '/assets/js/archive-customizer-controls.js';
	if ( ! file_exists( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'lightning-child-archive-customizer-controls',
		get_stylesheet_directory_uri() . '/assets/js/archive-customizer-controls.js',
		array( 'customize-controls' ),
		filemtime( $script_path ),
		true
	);

	$config = array();
	foreach ( lightning_child_get_archive_post_types() as $post_type ) {
		$post_type_name = sanitize_key( $post_type->name );
		$config[]       = array(
			'displaySetting' => lightning_child_get_archive_setting_name( $post_type_name, 'display_type' ),
			'cardControls'   => array(
				lightning_child_get_archive_setting_name( $post_type_name, 'card_design' ),
				lightning_child_get_archive_setting_name( $post_type_name, 'image_ratio' ),
				lightning_child_get_archive_setting_name( $post_type_name, 'card_radius' ),
				lightning_child_get_archive_setting_name( $post_type_name, 'card_border' ),
				lightning_child_get_archive_setting_name( $post_type_name, 'card_shadow' ),
			),
			'customControls' => array(
				lightning_child_get_archive_setting_name( $post_type_name, 'columns_pc' ),
				lightning_child_get_archive_setting_name( $post_type_name, 'columns_tablet' ),
				lightning_child_get_archive_setting_name( $post_type_name, 'columns_mobile' ),
			),
		);
	}

	wp_localize_script(
		'lightning-child-archive-customizer-controls',
		'lightningChildArchiveControls',
		$config
	);
}
add_action( 'customize_controls_enqueue_scripts', 'lightning_child_enqueue_archive_customizer_controls' );

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
 * Enqueue archive card styles only where the archive loop can use them.
 *
 * @return void
 */
function lightning_child_enqueue_archive_styles() {
	if ( ! ( is_home() || is_archive() ) ) {
		return;
	}

	$style_path = get_stylesheet_directory() . '/assets/css/archive-posts.css';
	if ( ! file_exists( $style_path ) ) {
		return;
	}

	wp_enqueue_style(
		'lightning-child-archive-posts',
		get_stylesheet_directory_uri() . '/assets/css/archive-posts.css',
		array( 'lightning-theme-style' ),
		filemtime( $style_path )
	);

	$post_type = lightning_child_get_current_archive_post_type();
	if ( ! $post_type ) {
		return;
	}

	$radius = lightning_child_sanitize_archive_card_radius(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'card_radius' ), 8 )
	);
	$border = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'card_border' ), true )
	);
	$shadow = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'card_shadow' ), false )
	);

	$custom_css = '.lightning-child-archive-item{'
		. '--lightning-child-archive-radius:' . $radius . 'px;'
		. '--lightning-child-archive-border-width:' . ( $border ? '1px' : '0px' ) . ';'
		. '--lightning-child-archive-shadow:' . ( $shadow ? '0 10px 28px rgba(0,0,0,.12)' : 'none' ) . ';'
		. '}';
	wp_add_inline_style( 'lightning-child-archive-posts', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_archive_styles', 20 );

/**
 * Return responsive VK Post column classes.
 *
 * @param int $mobile Mobile columns.
 * @param int $tablet Tablet columns.
 * @param int $pc      PC columns.
 * @return string
 */
function lightning_child_get_archive_column_classes( $mobile, $tablet, $pc ) {
	$widths = array(
		1 => 12,
		2 => 6,
		3 => 4,
		4 => 3,
	);

	$mobile = lightning_child_sanitize_archive_columns( $mobile );
	$tablet = lightning_child_sanitize_archive_columns( $tablet );
	$pc     = lightning_child_sanitize_archive_columns( $pc );

	return 'vk_post-col-xs-' . $widths[ $mobile ]
		. ' vk_post-col-sm-' . $widths[ $tablet ]
		. ' vk_post-col-lg-' . $widths[ $pc ];
}

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
	$show_image = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'image' ), true )
	);
	$show_excerpt = rest_sanitize_boolean(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'excerpt' ), true )
	);
	$card_design = lightning_child_sanitize_archive_card_design(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'card_design' ), 'standard' )
	);
	$legacy_columns = lightning_child_sanitize_archive_columns(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'columns' ), 3 )
	);
	$columns_pc = lightning_child_sanitize_archive_columns(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'columns_pc' ), $legacy_columns )
	);
	$columns_tablet = lightning_child_sanitize_archive_columns(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'columns_tablet' ), 2 )
	);
	$columns_mobile = lightning_child_sanitize_archive_columns(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'columns_mobile' ), 1 )
	);
	$image_ratio = lightning_child_sanitize_archive_image_ratio(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'image_ratio' ), '16_9' )
	);
	$hover_effect = lightning_child_sanitize_archive_hover_effect(
		get_theme_mod( lightning_child_get_archive_setting_name( $post_type, 'hover_effect' ), 'none' )
	);

	$column_classes = lightning_child_get_archive_column_classes( $columns_mobile, $columns_tablet, $columns_pc );
	$item_classes = array(
		'lightning-child-archive-item',
		'lightning-child-archive-ratio-' . str_replace( '_', '-', $image_ratio ),
		'lightning-child-archive-hover-' . $hover_effect,
	);

	if ( 'card' === $display_type ) {
		$item_classes[] = 'lightning-child-archive-design-' . str_replace( '_', '-', $card_design );
		$options['layout'] = 'overlay' === $card_design ? 'card-intext' : 'card';
		$options['display_image']   = 'overlay' === $card_design || ( 'text_card' !== $card_design && $show_image );
		$options['display_excerpt'] = $show_excerpt;
		$options['display_btn']    = false;
		$options['class_outer']    = $column_classes . ' ' . implode( ' ', $item_classes );
	} elseif ( 'media' === $display_type ) {
		$options['layout']          = 'media';
		$options['display_image']   = $show_image;
		$options['display_excerpt'] = $show_excerpt;
		$options['display_btn']     = false;
		$options['class_outer']     = $column_classes . ' ' . implode( ' ', $item_classes );
	} elseif ( 'text' === $display_type ) {
		$options['layout']          = 'postListText';
		$options['display_image']   = false;
		$options['display_excerpt'] = false;
		$options['display_btn']     = false;
		$options['class_outer']     = $column_classes . ' ' . implode( ' ', $item_classes );
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

	if ( 'card' === $display_type && 'date_corner' === $card_design && $options['display_image'] && $options['display_date'] ) {
		$options['overlay'] = '<span class="lightning-child-archive-date-corner">'
			. '<span class="lightning-child-archive-date-corner__month">' . esc_html( get_the_date( 'M', $post ) ) . '</span>'
			. '<span class="lightning-child-archive-date-corner__day">' . esc_html( get_the_date( 'd', $post ) ) . '</span>'
			. '<span class="lightning-child-archive-date-corner__year">' . esc_html( get_the_date( 'Y', $post ) ) . '</span>'
			. '</span>';
		$options['display_date'] = false;
	}

	if ( 'card' === $display_type && 'overlay' === $card_design ) {
		$options['body_append'] = '<span class="lightning-child-archive-overlay-arrow" aria-hidden="true">&rarr;</span>';
	}

	return $options;
}
add_filter( 'vk_post_options', 'lightning_child_filter_archive_post_options', 20, 2 );

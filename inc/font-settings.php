<?php
/**
 * Font settings for the site and editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the available font sets.
 *
 * The stack and Google Fonts request are both trusted values defined here. This
 * keeps arbitrary CSS and remote URLs out of Customizer data.
 *
 * @return array<string, array<string, string>>
 */
function lightning_child_get_font_sets() {
	return array(
		'system-sans'     => array(
			'label' => __( 'OS標準サンセリフ', 'cni-lightning-child' ),
			'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
		),
		'japanese-gothic' => array(
			'label' => __( '日本語ゴシック（端末標準）', 'cni-lightning-child' ),
			'stack' => '"Hiragino Kaku Gothic ProN", "Yu Gothic", YuGothic, Meiryo, sans-serif',
		),
		'japanese-mincho' => array(
			'label' => __( '日本語明朝（端末標準）', 'cni-lightning-child' ),
			'stack' => '"Hiragino Mincho ProN", "Yu Mincho", YuMincho, serif',
		),
		'hiragino-kaku'   => array(
			'label' => __( 'ヒラギノ角ゴ', 'cni-lightning-child' ),
			'stack' => '"Hiragino Kaku Gothic ProN", "Hiragino Sans", sans-serif',
		),
		'yu-gothic'       => array(
			'label' => __( '游ゴシック', 'cni-lightning-child' ),
			'stack' => '"Yu Gothic", YuGothic, "Hiragino Kaku Gothic ProN", sans-serif',
		),
		'meiryo'          => array(
			'label' => __( 'メイリオ', 'cni-lightning-child' ),
			'stack' => 'Meiryo, "Hiragino Kaku Gothic ProN", sans-serif',
		),
		'noto-sans-jp'    => array(
			'label'         => __( 'Noto Sans JP（Google Fonts）', 'cni-lightning-child' ),
			'stack'         => '"Noto Sans JP", "Hiragino Kaku Gothic ProN", "Yu Gothic", YuGothic, Meiryo, sans-serif',
			'google_family' => 'Noto Sans JP:wght@400;700',
		),
		'noto-serif-jp'   => array(
			'label'         => __( 'Noto Serif JP（Google Fonts）', 'cni-lightning-child' ),
			'stack'         => '"Noto Serif JP", "Hiragino Mincho ProN", "Yu Mincho", YuMincho, serif',
			'google_family' => 'Noto Serif JP:wght@400;700',
		),
	);
}

/**
 * Return the font setting locations.
 *
 * The existing lightning_child_font_set setting remains the body setting so
 * sites upgraded from the first version keep their selected font.
 *
 * @return array<string, array<string, string>>
 */
function lightning_child_get_font_locations() {
	return array(
		'body'        => array(
			'setting' => 'lightning_child_font_set',
			'label'   => __( '本文テキスト', 'cni-lightning-child' ),
			'default' => 'system-sans',
		),
		'title'       => array(
			'setting' => 'lightning_child_font_title',
			'label'   => __( 'タイトル・見出し', 'cni-lightning-child' ),
			'default' => 'inherit',
		),
		'global_nav'  => array(
			'setting' => 'lightning_child_font_global_nav',
			'label'   => __( 'グローバルメニュー', 'cni-lightning-child' ),
			'default' => 'inherit',
		),
		'header_logo' => array(
			'setting' => 'lightning_child_font_header_logo',
			'label'   => __( 'ヘッダーロゴ（文字表示時）', 'cni-lightning-child' ),
			'default' => 'inherit',
		),
	);
}

/**
 * Sanitize the body font setting.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_font_set( $value ) {
	$font_sets = lightning_child_get_font_sets();
	return is_string( $value ) && isset( $font_sets[ $value ] ) ? $value : 'system-sans';
}

/**
 * Sanitize a location font setting that may inherit the body font.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_font_location( $value ) {
	if ( 'inherit' === $value ) {
		return 'inherit';
	}

	$font_sets = lightning_child_get_font_sets();
	return is_string( $value ) && isset( $font_sets[ $value ] ) ? $value : 'inherit';
}

/**
 * Sanitize an optional font weight.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_font_weight( $value ) {
	$value = is_scalar( $value ) ? (string) $value : '';
	return in_array( $value, array( 'inherit', '400', '700' ), true ) ? $value : 'inherit';
}

/**
 * Return a sanitized font choice for a location.
 *
 * @param string $location Location key.
 * @return string
 */
function lightning_child_get_font_choice( $location ) {
	$locations = lightning_child_get_font_locations();
	if ( ! isset( $locations[ $location ] ) ) {
		return 'system-sans';
	}

	$config = $locations[ $location ];
	$value  = get_theme_mod( $config['setting'], $config['default'] );

	return 'body' === $location
		? lightning_child_sanitize_font_set( $value )
		: lightning_child_sanitize_font_location( $value );
}

/**
 * Return the selected trusted font stack for a location.
 *
 * @param string $location Location key.
 * @return string
 */
function lightning_child_get_selected_font_stack( $location = 'body' ) {
	$font_sets = lightning_child_get_font_sets();
	$selected  = lightning_child_get_font_choice( $location );

	if ( 'inherit' === $selected ) {
		$selected = lightning_child_get_font_choice( 'body' );
	}

	return $font_sets[ $selected ]['stack'];
}

/**
 * Return the Google Fonts families currently in use.
 *
 * @return string[]
 */
function lightning_child_get_selected_google_font_families() {
	$font_sets = lightning_child_get_font_sets();
	$families  = array();

	foreach ( array_keys( lightning_child_get_font_locations() ) as $location ) {
		$selected = lightning_child_get_font_choice( $location );
		if ( isset( $font_sets[ $selected ]['google_family'] ) ) {
			$families[] = $font_sets[ $selected ]['google_family'];
		}
	}

	return array_values( array_unique( $families ) );
}

/**
 * Register the location-based font selections in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_font_settings( $wp_customize ) {
	$wp_customize->add_section(
		'lightning_child_typography',
		array(
			'title'       => __( 'Lightning フォント設定', 'cni-lightning-child' ),
			'description' => __( '場所ごとに書体を選択できます。Google Fontsを選択した場合だけ外部フォント（通常・太字）を読み込みます。', 'cni-lightning-child' ),
			'priority'    => 167,
		)
	);

	$font_choices = array();
	foreach ( lightning_child_get_font_sets() as $font_set => $font_data ) {
		$font_choices[ $font_set ] = $font_data['label'];
	}

	foreach ( lightning_child_get_font_locations() as $location => $config ) {
		$is_body = 'body' === $location;
		$choices = $font_choices;
		if ( ! $is_body ) {
			$choices = array( 'inherit' => __( '本文テキストと同じ', 'cni-lightning-child' ) ) + $choices;
		}

		$wp_customize->add_setting(
			$config['setting'],
			array(
				'default'           => $config['default'],
				'sanitize_callback' => $is_body ? 'lightning_child_sanitize_font_set' : 'lightning_child_sanitize_font_location',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$config['setting'],
			array(
				'label'   => $config['label'],
				'section' => 'lightning_child_typography',
				'type'    => 'select',
				'choices' => $choices,
			)
		);
	}

	$weight_settings = array(
		'lightning_child_font_title_weight'      => __( 'タイトル・見出しの太さ', 'cni-lightning-child' ),
		'lightning_child_font_global_nav_weight' => __( 'グローバルメニューの太さ', 'cni-lightning-child' ),
	);
	foreach ( $weight_settings as $setting_name => $label ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => 'inherit',
				'sanitize_callback' => 'lightning_child_sanitize_font_weight',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$setting_name,
			array(
				'label'   => $label,
				'section' => 'lightning_child_typography',
				'type'    => 'select',
				'choices' => array(
					'inherit' => __( 'Lightning標準', 'cni-lightning-child' ),
					'400'     => '400',
					'700'     => '700',
				),
			)
		);
	}
}
add_action( 'customize_register', 'lightning_child_customize_font_settings' );

/**
 * Enqueue only the selected Google Fonts families.
 *
 * @return void
 */
function lightning_child_enqueue_google_fonts() {
	$families = lightning_child_get_selected_google_font_families();
	if ( empty( $families ) ) {
		return;
	}

	$query_parts = array();
	foreach ( $families as $family ) {
		$query_parts[] = 'family=' . str_replace( '%20', '+', rawurlencode( $family ) );
	}

	$url = 'https://fonts.googleapis.com/css2?' . implode( '&', $query_parts ) . '&display=swap';
	wp_enqueue_style( 'lightning-child-google-fonts', $url, array(), null );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_google_fonts', 15 );
add_action( 'enqueue_block_editor_assets', 'lightning_child_enqueue_google_fonts', 15 );

/**
 * Add the selected font stacks to the child theme stylesheet.
 *
 * @return void
 */
function lightning_child_add_font_css() {
	$title_weight = lightning_child_sanitize_font_weight( get_theme_mod( 'lightning_child_font_title_weight', 'inherit' ) );
	$nav_weight   = lightning_child_sanitize_font_weight( get_theme_mod( 'lightning_child_font_global_nav_weight', 'inherit' ) );
	$css = ':root{'
		. '--lightning-child-font-family:' . lightning_child_get_selected_font_stack( 'body' ) . ';'
		. '--lightning-child-font-title:' . lightning_child_get_selected_font_stack( 'title' ) . ';'
		. '--lightning-child-font-global-nav:' . lightning_child_get_selected_font_stack( 'global_nav' ) . ';'
		. '--lightning-child-font-header-logo:' . lightning_child_get_selected_font_stack( 'header_logo' ) . ';'
		. '}';
	if ( 'inherit' !== $title_weight ) {
		$css .= 'h1,h2,h3,h4,h5,h6,.entry-title,.page-header-title,.sub-section-title,.site-footer-title,.vk_post_title{font-weight:' . $title_weight . ';}';
	}
	if ( 'inherit' !== $nav_weight ) {
		$css .= '.global-nav,.global-nav .global-nav-name,'
			. '.vk-mobile-nav,.vk-mobile-nav .global-nav-name,'
			. '.lightning-child-mobile-fixed-nav{font-weight:' . $nav_weight . ';}';
	}

	wp_add_inline_style( 'lightning-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_font_css', 20 );

/**
 * Apply the selected font stacks to the block editor.
 *
 * @return void
 */
function lightning_child_add_editor_font_css() {
	$body_font  = lightning_child_get_selected_font_stack( 'body' );
	$title_font = lightning_child_get_selected_font_stack( 'title' );
	$title_weight = lightning_child_sanitize_font_weight( get_theme_mod( 'lightning_child_font_title_weight', 'inherit' ) );
	$css        = ':root{--lightning-child-font-family:' . $body_font . ';--lightning-child-font-title:' . $title_font . ';}'
		. '.editor-styles-wrapper{font-family:var(--lightning-child-font-family);}'
		. '.editor-styles-wrapper h1,.editor-styles-wrapper h2,.editor-styles-wrapper h3,'
		. '.editor-styles-wrapper h4,.editor-styles-wrapper h5,.editor-styles-wrapper h6,'
		. '.editor-styles-wrapper .wp-block-post-title{font-family:var(--lightning-child-font-title);}';
	if ( 'inherit' !== $title_weight ) {
		$css .= '.editor-styles-wrapper h1,.editor-styles-wrapper h2,.editor-styles-wrapper h3,'
			. '.editor-styles-wrapper h4,.editor-styles-wrapper h5,.editor-styles-wrapper h6,'
			. '.editor-styles-wrapper .wp-block-post-title{font-weight:' . $title_weight . ';}';
	}

	wp_add_inline_style( 'lightning-common-editor-gutenberg', $css );
}
add_action( 'enqueue_block_assets', 'lightning_child_add_editor_font_css', 20 );

/**
 * Stop the Origin III skin from loading its fixed Google Fonts combination.
 *
 * The child theme loads only the families selected above, preventing duplicate
 * Noto Sans JP and unused Lato requests.
 *
 * @return void
 */
function lightning_child_disable_origin3_google_fonts() {
	if ( function_exists( 'lightning_origin3_load_fonts' ) ) {
		remove_action( 'wp_footer', 'lightning_origin3_load_fonts' );
		remove_action( 'admin_footer', 'lightning_origin3_load_fonts' );
	}
}
add_action( 'after_setup_theme', 'lightning_child_disable_origin3_google_fonts', 20 );

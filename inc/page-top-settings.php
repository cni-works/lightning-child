<?php
/**
 * Scroll-to-top button design settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the page-top display mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_page_top_mode( $value ) {
	return 'image' === $value ? 'image' : 'design';
}

/**
 * Sanitize the page-top shape.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_page_top_shape( $value ) {
	$choices = array( 'circle', 'rounded', 'square' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'circle';
}

/**
 * Sanitize the page-top button size.
 *
 * @param mixed $value Submitted value.
 * @return int
 */
function lightning_child_sanitize_page_top_size( $value ) {
	return min( 80, max( 40, absint( $value ) ) );
}

/**
 * Sanitize opacity between zero and one.
 *
 * @param mixed $value Submitted value.
 * @return float
 */
function lightning_child_sanitize_page_top_opacity( $value ) {
	if ( ! is_numeric( $value ) ) {
		return 0.8;
	}

	return min( 1, max( 0, (float) $value ) );
}

/**
 * Register scroll-to-top button design settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_page_top_settings( $wp_customize ) {
	$section_id = 'lightning_child_page_top';

	$wp_customize->add_section(
		$section_id,
		array(
			'title'       => __( 'Lightning スクロールアップ', 'cni-lightning-child' ),
			'description' => __( 'ページ右下のスクロールアップボタンのデザインを変更します。位置と動作は変更しません。', 'cni-lightning-child' ),
			'priority'    => 169,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_top_enabled',
		array(
			'label'   => __( '子テーマのデザインを使用する', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_mode',
		array(
			'default'           => 'design',
			'sanitize_callback' => 'lightning_child_sanitize_page_top_mode',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_top_mode',
		array(
			'label'   => __( '表示方法', 'cni-lightning-child' ),
			'section' => $section_id,
			'type'    => 'radio',
			'choices' => array(
				'design' => __( '色と形を変更する', 'cni-lightning-child' ),
				'image'  => __( '指定画像をボタン全体に使用する', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_size',
		array(
			'default'           => 48,
			'sanitize_callback' => 'lightning_child_sanitize_page_top_size',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_top_size',
		array(
			'label'       => __( 'ボタンサイズ', 'cni-lightning-child' ),
			'description' => __( '縦横共通の表示サイズです。40〜80pxで指定します。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 40,
				'max'  => 80,
				'step' => 1,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_shape',
		array(
			'default'           => 'circle',
			'sanitize_callback' => 'lightning_child_sanitize_page_top_shape',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_top_shape',
		array(
			'label'       => __( 'ボタンの形', 'cni-lightning-child' ),
			'description' => __( '「色と形を変更する」場合に使用します。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'radio',
			'choices'     => array(
				'circle'  => __( '正円', 'cni-lightning-child' ),
				'rounded' => __( '角丸', 'cni-lightning-child' ),
				'square'  => __( '四角', 'cni-lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_background_color',
		array(
			'default'           => '#000000',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_page_top_background_color',
			array(
				'label'   => __( '背景色', 'cni-lightning-child' ),
				'section' => $section_id,
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_background_opacity',
		array(
			'default'           => 0.8,
			'sanitize_callback' => 'lightning_child_sanitize_page_top_opacity',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_top_background_opacity',
		array(
			'label'       => __( '背景の透明度', 'cni-lightning-child' ),
			'description' => __( '0（透明）から1（不透明）の数字で入力します。画像モードでは使用しません。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 1,
				'step' => 0.1,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_outline_enabled',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_top_outline_enabled',
		array(
			'label'       => __( '外周線を表示する', 'cni-lightning-child' ),
			'description' => __( '画像モードでは使用しません。', 'cni-lightning-child' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_outline_color',
		array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_page_top_outline_color',
			array(
				'label'   => __( '外周線の色', 'cni-lightning-child' ),
				'section' => $section_id,
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_top_image',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'lightning_child_page_top_image',
			array(
				'label'       => __( 'ボタン画像', 'cni-lightning-child' ),
				'description' => __( '透明背景の正方形PNGまたはWebPを推奨します。画像がない場合は通常デザインへ戻ります。', 'cni-lightning-child' ),
				'section'     => $section_id,
				'mime_type'   => 'image',
			)
		)
	);
}
add_action( 'customize_register', 'lightning_child_customize_page_top_settings' );

/**
 * Convert a six-digit hex color to rgba.
 *
 * @param string $hex     Hex color.
 * @param float  $opacity Opacity between zero and one.
 * @return string
 */
function lightning_child_page_top_rgba( $hex, $opacity ) {
	$hex = ltrim( $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		$hex = '000000';
	}

	return sprintf(
		'rgba(%1$d,%2$d,%3$d,%4$s)',
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) ),
		rtrim( rtrim( number_format( $opacity, 2, '.', '' ), '0' ), '.' )
	);
}

/**
 * Return a CSS-safe image URL for the configured attachment.
 *
 * @return string
 */
function lightning_child_get_page_top_image_url() {
	$image_url = wp_get_attachment_image_url(
		absint( get_theme_mod( 'lightning_child_page_top_image', 0 ) ),
		'full'
	);

	if ( ! $image_url ) {
		return '';
	}

	$image_url = esc_url_raw( $image_url );
	return str_replace( array( '"', "'", '(', ')', "\n", "\r" ), '', $image_url );
}

/**
 * Add the configured scroll-to-top button design.
 *
 * @return void
 */
function lightning_child_add_page_top_css() {
	if ( ! rest_sanitize_boolean( get_theme_mod( 'lightning_child_page_top_enabled', false ) ) ) {
		return;
	}

	$mode  = lightning_child_sanitize_page_top_mode( get_theme_mod( 'lightning_child_page_top_mode', 'design' ) );
	$size  = lightning_child_sanitize_page_top_size( get_theme_mod( 'lightning_child_page_top_size', 48 ) );
	$image = 'image' === $mode ? lightning_child_get_page_top_image_url() : '';

	$css = '.page_top_btn{width:' . $size . 'px;height:' . $size . 'px;}';

	if ( $image ) {
		$css .= '.page_top_btn{border:none;border-radius:0;background-color:transparent;background-image:url("' . $image . '");background-position:center;background-repeat:no-repeat;background-size:contain;box-shadow:none;}';
	} else {
		$shape        = lightning_child_sanitize_page_top_shape( get_theme_mod( 'lightning_child_page_top_shape', 'circle' ) );
		$border_radius = array(
			'circle'  => '50%',
			'rounded' => '10px',
			'square'  => '0',
		);
		$background   = sanitize_hex_color( get_theme_mod( 'lightning_child_page_top_background_color', '#000000' ) );
		$background   = $background ? $background : '#000000';
		$opacity      = lightning_child_sanitize_page_top_opacity(
			get_theme_mod( 'lightning_child_page_top_background_opacity', 0.8 )
		);
		$outline      = rest_sanitize_boolean( get_theme_mod( 'lightning_child_page_top_outline_enabled', false ) );
		$outline_color = sanitize_hex_color( get_theme_mod( 'lightning_child_page_top_outline_color', '#ffffff' ) );
		$outline_color = $outline_color ? $outline_color : '#ffffff';
		$box_shadow    = $outline ? '0 0 0 1px ' . $outline_color : 'none';

		$css .= '.page_top_btn{border:none;border-radius:' . $border_radius[ $shape ] . ';background-color:' . lightning_child_page_top_rgba( $background, $opacity ) . ';box-shadow:' . $box_shadow . ';}';
	}

	wp_add_inline_style( 'lightning-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_page_top_css', 20 );

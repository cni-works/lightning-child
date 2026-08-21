<?php
/**
 * Footer colors and copyright settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the default copyright text.
 *
 * @return string
 */
function lightning_child_get_default_copyright_text() {
	return 'Copyright © {year} {site_name} All Rights Reserved.';
}

/**
 * Register child-theme footer settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_footer_settings( $wp_customize ) {
	$wp_customize->add_section(
		'lightning_child_footer',
		array(
			'title'       => __( 'Lightning 子テーマ フッター', 'cni-lightning-child' ),
			'description' => __( 'Lightning標準フッターの配色とコピーライトを設定します。', 'cni-lightning-child' ),
			'priority'    => 165,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_footer_background_color',
		array(
			'default'           => '#ffffff',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_footer_background_color',
			array(
				'label'   => __( '背景色', 'cni-lightning-child' ),
				'section' => 'lightning_child_footer',
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_footer_text_color',
		array(
			'default'           => '#333333',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'lightning_child_footer_text_color',
			array(
				'label'   => __( '文字色', 'cni-lightning-child' ),
				'section' => 'lightning_child_footer',
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_copyright_text',
		array(
			'default'           => lightning_child_get_default_copyright_text(),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'lightning_child_copyright_text',
		array(
			'label'       => __( 'コピーライト', 'cni-lightning-child' ),
			'description' => __( '{year} は現在年、{site_name} はサイト名へ置き換わります。空欄にすると非表示になります。', 'cni-lightning-child' ),
			'section'     => 'lightning_child_footer',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'lightning_child_customize_footer_settings' );

/**
 * Return a validated footer color setting.
 *
 * @param string $setting_name Theme modification name.
 * @param string $default      Default color.
 * @return string
 */
function lightning_child_get_footer_color( $setting_name, $default ) {
	$color = sanitize_hex_color( get_theme_mod( $setting_name, $default ) );
	return $color ? $color : $default;
}

/**
 * Add footer color variables after the child theme stylesheet.
 *
 * @return void
 */
function lightning_child_add_footer_color_css() {
	$background_color = lightning_child_get_footer_color( 'lightning_child_footer_background_color', '#ffffff' );
	$text_color       = lightning_child_get_footer_color( 'lightning_child_footer_text_color', '#333333' );
	$css              = sprintf(
		':root{--lightning-child-footer-background:%1$s;--lightning-child-footer-text:%2$s;}',
		$background_color,
		$text_color
	);

	wp_add_inline_style( 'lightning-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_footer_color_css', 20 );

/**
 * Build the configured copyright HTML.
 *
 * @return string
 */
function lightning_child_get_copyright_html() {
	$text = get_theme_mod( 'lightning_child_copyright_text', lightning_child_get_default_copyright_text() );
	$text = is_string( $text ) ? trim( $text ) : '';

	if ( '' === $text ) {
		return '';
	}

	$text = strtr(
		$text,
		array(
			'{year}'      => wp_date( 'Y' ),
			'{site_name}' => get_bloginfo( 'name', 'display' ),
		)
	);

	return '<p>' . esc_html( $text ) . '</p>';
}

/**
 * Replace Lightning's standard copyright.
 *
 * @param string $copyright_html Lightning's default copyright HTML.
 * @return string
 */
function lightning_child_filter_footer_copyright( $copyright_html ) {
	unset( $copyright_html );
	return lightning_child_get_copyright_html();
}
add_filter( 'lightning_footerCopyRightCustom', 'lightning_child_filter_footer_copyright' );

/**
 * Remove Lightning's standard theme credit.
 *
 * @param string $powered_html Lightning's default credit HTML.
 * @return string
 */
function lightning_child_filter_footer_powered( $powered_html ) {
	unset( $powered_html );
	return '';
}
add_filter( 'lightning_footerPoweredCustom', 'lightning_child_filter_footer_powered' );

/**
 * Add a body class when the configured copyright is empty.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function lightning_child_add_copyright_body_class( $classes ) {
	if ( '' === lightning_child_get_copyright_html() ) {
		$classes[] = 'lightning-child-copyright-hidden';
	}

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_copyright_body_class' );

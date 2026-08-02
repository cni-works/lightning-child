<?php
/**
 * Header layout and color settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the supported header layout.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_header_layout( $value ) {
	$choices = array( 'standard', 'center', 'contact', 'widget' );
	return in_array( $value, $choices, true ) ? $value : 'standard';
}

/**
 * Sanitize the header layout used after scrolling.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_header_scroll_layout( $value ) {
	$choices = array( 'nav-only', 'nav-center', 'nav-container', 'logo-nav', 'none' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'nav-only';
}

/**
 * Sanitize the desktop header logo display mode.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_header_logo_display( $value ) {
	$choices = array( 'always', 'first-view-hidden', 'hidden' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'always';
}

/**
 * Sanitize the header contact data source.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_header_contact_source( $value ) {
	return 'custom' === $value ? 'custom' : 'exunit';
}

/**
 * Sanitize header background opacity.
 *
 * @param mixed $value Submitted value.
 * @return float
 */
function lightning_child_sanitize_header_opacity( $value ) {
	if ( ! is_numeric( $value ) ) {
		return 0;
	}

	return min( 1, max( 0, (float) $value ) );
}

/**
 * Sanitize the supported mobile logo position.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_mobile_logo_position( $value ) {
	$choices = array( 'left', 'center', 'right' );
	return in_array( $value, $choices, true ) ? $value : 'center';
}

/**
 * Determine whether controls for Lightning's standard header should be active.
 *
 * @return bool
 */
function lightning_child_is_standard_header_mode() {
	return ! function_exists( 'lightning_child_get_template_part_mode' )
		|| 'standard' === lightning_child_get_template_part_mode( 'header' );
}

/**
 * Determine whether the contact header layout is selected.
 *
 * @return bool
 */
function lightning_child_is_contact_header_layout() {
	return lightning_child_is_standard_header_mode()
		&& 'contact' === lightning_child_sanitize_header_layout( get_theme_mod( 'lightning_child_header_layout', 'standard' ) );
}

/**
 * Determine whether custom contact fields should be displayed.
 *
 * @return bool
 */
function lightning_child_is_custom_header_contact_source() {
	return lightning_child_is_contact_header_layout()
		&& 'custom' === lightning_child_sanitize_header_contact_source(
			get_theme_mod( 'lightning_child_header_contact_source', 'exunit' )
		);
}

/**
 * Register child-theme header settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_header_settings( $wp_customize ) {
	$wp_customize->add_section(
		'lightning_child_header',
		array(
			'title'       => __( 'Lightning ヘッダー設定', 'lightning-child' ),
			'description' => __( 'Lightning標準ヘッダーのレイアウト、固定方法、配色、トップページ透過を設定します。', 'lightning-child' ),
			'priority'    => 164,
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_layout',
		array(
			'default'           => 'standard',
			'sanitize_callback' => 'lightning_child_sanitize_header_layout',
		)
	);

	$wp_customize->add_control(
		'lightning_child_header_layout',
		array(
			'label'           => __( 'ヘッダーレイアウト', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'select',
			'active_callback' => 'lightning_child_is_standard_header_mode',
			'choices'         => array(
				'standard' => __( 'ナビゲーション回り込み', 'lightning-child' ),
				'center'   => __( 'ロゴ・ナビゲーション中央寄せ', 'lightning-child' ),
				'contact'  => __( 'ヘッダーコンタクトあり（ExUnit連携）', 'lightning-child' ),
				'widget'   => __( 'ヘッダーウィジェットあり', 'lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_logo_display',
		array(
			'default'           => 'always',
			'sanitize_callback' => 'lightning_child_sanitize_header_logo_display',
		)
	);
	$wp_customize->add_control(
		'lightning_child_header_logo_display',
		array(
			'label'           => __( 'PCヘッダーロゴの表示', 'lightning-child' ),
			'description'     => __( '「ファーストビューだけ非表示」では、スクロール後の表示は下の固定ヘッダー設定に従います。', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'radio',
			'active_callback' => 'lightning_child_is_standard_header_mode',
			'choices'         => array(
				'always'            => __( '常に表示する', 'lightning-child' ),
				'first-view-hidden' => __( 'ファーストビューだけ非表示', 'lightning-child' ),
				'hidden'            => __( 'PCでは常に非表示', 'lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_logo_hidden',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_mobile_logo_hidden',
		array(
			'label'           => __( 'モバイルのヘッダーロゴを非表示にする', 'lightning-child' ),
			'description'     => __( '非表示でもLightningのハンバーガーボタンを配置できる高さは残します。', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'checkbox',
			'active_callback' => 'lightning_child_is_standard_header_mode',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_contact_source',
		array(
			'default'           => 'exunit',
			'sanitize_callback' => 'lightning_child_sanitize_header_contact_source',
		)
	);
	$wp_customize->add_control(
		'lightning_child_header_contact_source',
		array(
			'label'           => __( 'ヘッダーコンタクトの取得元', 'lightning-child' ),
			'description'     => __( 'ExUnitを使用しない案件では「この画面で入力」を選択してください。', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'radio',
			'active_callback' => 'lightning_child_is_contact_header_layout',
			'choices'         => array(
				'exunit' => __( 'ExUnitの連絡先情報', 'lightning-child' ),
				'custom' => __( 'この画面で入力', 'lightning-child' ),
			),
		)
	);

	$contact_text_settings = array(
		'lightning_child_header_contact_catch' => array(
			'label'    => __( '案内文', 'lightning-child' ),
			'sanitize' => 'sanitize_text_field',
			'type'     => 'text',
		),
		'lightning_child_header_contact_phone' => array(
			'label'    => __( '電話番号', 'lightning-child' ),
			'sanitize' => 'sanitize_text_field',
			'type'     => 'text',
		),
		'lightning_child_header_contact_time' => array(
			'label'    => __( '受付時間', 'lightning-child' ),
			'sanitize' => 'sanitize_textarea_field',
			'type'     => 'textarea',
		),
		'lightning_child_header_contact_url' => array(
			'label'    => __( 'お問い合わせURL', 'lightning-child' ),
			'sanitize' => 'esc_url_raw',
			'type'     => 'text',
		),
		'lightning_child_header_contact_button_text' => array(
			'label'    => __( 'お問い合わせボタンの文字', 'lightning-child' ),
			'sanitize' => 'sanitize_text_field',
			'type'     => 'text',
		),
	);

	foreach ( $contact_text_settings as $setting_name => $setting ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => '',
				'sanitize_callback' => $setting['sanitize'],
			)
		);
		$wp_customize->add_control(
			$setting_name,
			array(
				'label'           => $setting['label'],
				'section'         => 'lightning_child_header',
				'type'            => $setting['type'],
				'active_callback' => 'lightning_child_is_custom_header_contact_source',
			)
		);
	}

	$wp_customize->add_setting(
		'lightning_child_header_contact_new_window',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_header_contact_new_window',
		array(
			'label'           => __( 'お問い合わせを新しいウィンドウで開く', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'checkbox',
			'active_callback' => 'lightning_child_is_custom_header_contact_source',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_scroll_layout',
		array(
			'default'           => 'nav-only',
			'sanitize_callback' => 'lightning_child_sanitize_header_scroll_layout',
		)
	);
	$wp_customize->add_control(
		'lightning_child_header_scroll_layout',
		array(
			'label'           => __( 'グローバルナビ スクロール時のレイアウト', 'lightning-child' ),
			'description'     => __( '変更後に表示が切り替わらない場合は、一度保存してプレビューを再読み込みしてください。', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'select',
			'active_callback' => 'lightning_child_is_standard_header_mode',
			'choices'         => array(
				'nav-only'      => __( 'ナビゲーションのみ固定（Lightning標準）', 'lightning-child' ),
				'nav-center'    => __( '固定ナビ中央寄せ', 'lightning-child' ),
				'nav-container' => __( '固定ナビコンテナ幅', 'lightning-child' ),
				'logo-nav'      => __( '固定 ロゴ＆ナビ回り込み', 'lightning-child' ),
				'none'          => __( '固定しない', 'lightning-child' ),
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_scrolled_logo',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'lightning_child_header_scrolled_logo',
			array(
				'label'       => __( 'スクロール時のロゴ', 'lightning-child' ),
				'description' => __( '「固定 ロゴ＆ナビ回り込み」選択時に使用します。未設定の場合は通常のヘッダーロゴを使用します。同じ縦横比の画像を推奨します。', 'lightning-child' ),
				'section'     => 'lightning_child_header',
				'mime_type'   => 'image',
			)
		)
	);

	$color_settings = array(
		'lightning_child_header_background_color'          => array(
			'label'   => __( '背景色', 'lightning-child' ),
			'default' => '#ffffff',
		),
		'lightning_child_header_text_color'                => array(
			'label'   => __( '文字色', 'lightning-child' ),
			'default' => '#333333',
		),
		'lightning_child_header_transparent_text_color'    => array(
			'label'   => __( '透過時の文字色', 'lightning-child' ),
			'default' => '#ffffff',
		),
		'lightning_child_header_transparent_background_color' => array(
			'label'   => __( '透過時の背景色', 'lightning-child' ),
			'default' => '#ffffff',
		),
		'lightning_child_header_scrolled_background_color' => array(
			'label'   => __( 'スクロール後の背景色', 'lightning-child' ),
			'default' => '#ffffff',
		),
	);

	foreach ( $color_settings as $setting_name => $setting ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => $setting['default'],
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				$setting_name,
				array(
					'label'   => $setting['label'],
					'section' => 'lightning_child_header',
				)
			)
		);
	}

	$wp_customize->add_setting(
		'lightning_child_header_transparent_front_page',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);

	$wp_customize->add_control(
		'lightning_child_header_transparent_front_page',
		array(
			'label'       => __( 'トップページでヘッダーを透過する', 'lightning-child' ),
			'description' => __( '先頭の画像や背景の上へヘッダーを重ねます。PCとモバイルの両方で確認してください。', 'lightning-child' ),
			'section'     => 'lightning_child_header',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_transparent_opacity',
		array(
			'default'           => 0,
			'sanitize_callback' => 'lightning_child_sanitize_header_opacity',
		)
	);
	$wp_customize->add_control(
		'lightning_child_header_transparent_opacity',
		array(
			'label'       => __( '透過時のヘッダー背景濃度', 'lightning-child' ),
			'description' => __( '0では背景色が完全透明になります。色を見せる場合は0.1〜1を指定してください。', 'lightning-child' ),
			'section'     => 'lightning_child_header',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 1,
				'step' => 0.1,
			),
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_transparent_gradient',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_header_transparent_gradient',
		array(
			'label'       => __( '透過背景をグラデーションにする', 'lightning-child' ),
			'description' => __( '画面幅992px以上でのみ適用します。', 'lightning-child' ),
			'section'     => 'lightning_child_header',
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_header_transparent_logo',
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			'lightning_child_header_transparent_logo',
			array(
				'label'       => __( '透過時のヘッダーロゴ画像', 'lightning-child' ),
				'description' => __( '未設定の場合は通常のヘッダーロゴを使用します。同じ縦横比の画像を推奨します。', 'lightning-child' ),
				'section'     => 'lightning_child_header',
				'mime_type'   => 'image',
			)
		)
	);

	$wp_customize->add_setting(
		'lightning_child_mobile_logo_position',
		array(
			'default'           => 'center',
			'sanitize_callback' => 'lightning_child_sanitize_mobile_logo_position',
		)
	);

	$wp_customize->add_control(
		'lightning_child_mobile_logo_position',
		array(
			'label'           => __( 'モバイルのロゴ位置', 'lightning-child' ),
			'section'         => 'lightning_child_header',
			'type'            => 'select',
			'active_callback' => 'lightning_child_is_standard_header_mode',
			'choices'         => array(
				'left'   => __( '左', 'lightning-child' ),
				'center' => __( '中央', 'lightning-child' ),
				'right'  => __( '右', 'lightning-child' ),
			),
		)
	);
}
add_action( 'customize_register', 'lightning_child_customize_header_settings' );

/**
 * Return a validated header color setting.
 *
 * @param string $setting_name Theme modification name.
 * @param string $default      Default color.
 * @return string
 */
function lightning_child_get_header_color( $setting_name, $default ) {
	$color = sanitize_hex_color( get_theme_mod( $setting_name, $default ) );
	return $color ? $color : $default;
}

/**
 * Add header color variables after the child theme stylesheet.
 *
 * @return void
 */
function lightning_child_add_header_color_css() {
	$background_color          = lightning_child_get_header_color( 'lightning_child_header_background_color', '#ffffff' );
	$text_color                = lightning_child_get_header_color( 'lightning_child_header_text_color', '#333333' );
	$transparent_text_color    = lightning_child_get_header_color( 'lightning_child_header_transparent_text_color', '#ffffff' );
	$transparent_background    = lightning_child_get_header_color( 'lightning_child_header_transparent_background_color', '#ffffff' );
	$transparent_opacity       = lightning_child_sanitize_header_opacity( get_theme_mod( 'lightning_child_header_transparent_opacity', 0 ) );
	$scrolled_background_color = lightning_child_get_header_color( 'lightning_child_header_scrolled_background_color', '#ffffff' );
	$mobile_nav_asset_url       = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/';
	$transparent_icon_tone      = lightning_child_is_light_header_color( $transparent_text_color ) ? 'white' : 'black';
	$mobile_menu_button_url     = esc_url_raw( $mobile_nav_asset_url . 'vk-menu-btn-' . $transparent_icon_tone . '.svg' );
	$mobile_menu_close_url      = esc_url_raw( $mobile_nav_asset_url . 'vk-menu-close-' . $transparent_icon_tone . '.svg' );
	$transparent_rgba           = lightning_child_header_rgba( $transparent_background, $transparent_opacity );
	$transparent_clear          = lightning_child_header_rgba( $transparent_background, 0 );
	$css                       = sprintf(
		':root{--lightning-child-header-background:%1$s;--lightning-child-header-text:%2$s;--lightning-child-header-transparent-text:%3$s;--lightning-child-header-scrolled-background:%4$s;--lightning-child-mobile-menu-button-light:url("%5$s");--lightning-child-mobile-menu-close-light:url("%6$s");--lightning-child-header-transparent-background:%7$s;--lightning-child-header-transparent-clear:%8$s;}',
		$background_color,
		$text_color,
		$transparent_text_color,
		$scrolled_background_color,
		$mobile_menu_button_url,
		$mobile_menu_close_url,
		$transparent_rgba,
		$transparent_clear
	);

	wp_add_inline_style( 'lightning-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_header_color_css', 20 );

/**
 * Convert a six-digit hex color to rgba.
 *
 * @param string $hex     Hex color.
 * @param float  $opacity Opacity between zero and one.
 * @return string
 */
function lightning_child_header_rgba( $hex, $opacity ) {
	$hex = ltrim( $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		$hex = 'ffffff';
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
 * Determine whether a color is light enough to use white menu artwork.
 *
 * @param string $hex Six-digit hex color.
 * @return bool
 */
function lightning_child_is_light_header_color( $hex ) {
	$hex = ltrim( $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		return true;
	}

	$luminance = ( 0.299 * hexdec( substr( $hex, 0, 2 ) ) )
		+ ( 0.587 * hexdec( substr( $hex, 2, 2 ) ) )
		+ ( 0.114 * hexdec( substr( $hex, 4, 2 ) ) );

	return $luminance >= 150;
}

/**
 * Switch between Lightning's existing standard and centered layout classes.
 *
 * @param array<string, string[]> $class_names Lightning class names by position.
 * @return array<string, string[]>
 */
function lightning_child_filter_header_class_names( $class_names ) {
	$layout = lightning_child_sanitize_header_layout( get_theme_mod( 'lightning_child_header_layout', 'standard' ) );

	if ( 'center' === $layout && isset( $class_names['site-header'] ) ) {
		$class_names['site-header']   = array_diff( $class_names['site-header'], array( 'site-header--layout--nav-float' ) );
		$class_names['site-header'][] = 'site-header--layout--center';
		$class_names['site-header']   = array_values( array_unique( $class_names['site-header'] ) );
	}

	if ( 'center' === $layout && isset( $class_names['global-nav'] ) ) {
		$class_names['global-nav']   = array_diff( $class_names['global-nav'], array( 'global-nav--layout--float-right' ) );
		$class_names['global-nav'][] = 'global-nav--layout--center';
		$class_names['global-nav']   = array_values( array_unique( $class_names['global-nav'] ) );
	}

	if ( in_array( $layout, array( 'contact', 'widget' ), true ) && isset( $class_names['site-header'] ) ) {
		$class_names['site-header'][] = 'site-header--layout--nav-float';
		$class_names['site-header']   = array_values( array_unique( $class_names['site-header'] ) );
	}

	return $class_names;
}
add_filter( 'lightning_get_class_names', 'lightning_child_filter_header_class_names' );

/**
 * Add state classes used by the header CSS.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function lightning_child_add_header_body_classes( $classes ) {
	if ( is_front_page() && get_theme_mod( 'lightning_child_header_transparent_front_page', false ) ) {
		$classes[] = 'lightning-child-transparent-header';
		if ( get_theme_mod( 'lightning_child_header_transparent_gradient', false ) ) {
			$classes[] = 'lightning-child-transparent-header-gradient';
		}
	}

	$scroll_layout = lightning_child_sanitize_header_scroll_layout(
		get_theme_mod( 'lightning_child_header_scroll_layout', 'nav-only' )
	);
	$classes[]     = 'lightning-child-header-scroll-' . $scroll_layout;

	$mobile_logo_position = lightning_child_sanitize_mobile_logo_position(
		get_theme_mod( 'lightning_child_mobile_logo_position', 'center' )
	);
	$classes[]            = 'lightning-child-mobile-logo-' . $mobile_logo_position;
	if ( rest_sanitize_boolean( get_theme_mod( 'lightning_child_mobile_logo_hidden', false ) ) ) {
		$classes[] = 'lightning-child-mobile-logo-hidden';
	}

	$logo_display = lightning_child_sanitize_header_logo_display(
		get_theme_mod( 'lightning_child_header_logo_display', 'always' )
	);
	$classes[]    = 'lightning-child-header-logo-' . $logo_display;

	$layout    = lightning_child_sanitize_header_layout( get_theme_mod( 'lightning_child_header_layout', 'standard' ) );
	$classes[] = 'lightning-child-header-' . $layout;

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_header_body_classes' );

/**
 * Register the reusable header widget area.
 *
 * @return void
 */
function lightning_child_register_header_widget_area() {
	register_sidebar(
		array(
			'name'          => __( 'ヘッダーウィジェット', 'lightning-child' ),
			'id'            => 'lightning-child-header-widget',
			'description'   => __( '「ヘッダーウィジェットあり」レイアウトで、PCヘッダー右側に表示します。', 'lightning-child' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'lightning_child_register_header_widget_area' );

/**
 * Return configured ExUnit contact information without using placeholder data.
 *
 * Read the saved option directly so the header can use the contact data even
 * when ExUnit's Contact Section module (and its PHP class) is disabled.
 *
 * @return array<string, mixed>
 */
function lightning_child_get_exunit_header_contact() {
	$stored = get_option( 'vkExUnit_contact', array() );
	if ( ! is_array( $stored ) || empty( $stored ) ) {
		return array();
	}

	$options = wp_parse_args(
		$stored,
		array(
			'contact_txt'         => '',
			'tel_number'          => '',
			'contact_time'        => '',
			'contact_link'        => '',
			'contact_target_blank' => false,
			'button_text'         => '',
			'short_text'          => '',
		)
	);
	$phone_digits = preg_replace( '/\D+/', '', (string) $options['tel_number'] );
	if ( '' !== $phone_digits && preg_match( '/^0+$/', $phone_digits ) ) {
		$options['tel_number'] = '';
		if ( untrailingslashit( (string) $options['contact_link'] ) === untrailingslashit( home_url( '/' ) ) ) {
			$options['contact_link'] = '';
		}
	}

	return apply_filters( 'lightning_child_exunit_header_contact_options', $options, $stored );
}

/**
 * Return contact information selected for the header.
 *
 * @return array<string, mixed>
 */
function lightning_child_get_header_contact() {
	$source = lightning_child_sanitize_header_contact_source(
		get_theme_mod( 'lightning_child_header_contact_source', 'exunit' )
	);
	if ( 'custom' !== $source ) {
		return lightning_child_get_exunit_header_contact();
	}

	return array(
		'contact_txt'          => get_theme_mod( 'lightning_child_header_contact_catch', '' ),
		'tel_number'           => get_theme_mod( 'lightning_child_header_contact_phone', '' ),
		'contact_time'         => get_theme_mod( 'lightning_child_header_contact_time', '' ),
		'contact_link'         => get_theme_mod( 'lightning_child_header_contact_url', '' ),
		'contact_target_blank' => get_theme_mod( 'lightning_child_header_contact_new_window', false ),
		'button_text'          => get_theme_mod( 'lightning_child_header_contact_button_text', '' ),
		'short_text'           => get_theme_mod( 'lightning_child_header_contact_button_text', '' ),
	);
}

/**
 * Output the selected contact or widget content after the header logo.
 *
 * @return void
 */
function lightning_child_render_header_extra() {
	if ( ! lightning_child_is_standard_header_mode() ) {
		return;
	}

	$layout = lightning_child_sanitize_header_layout( get_theme_mod( 'lightning_child_header_layout', 'standard' ) );
	if ( 'widget' === $layout ) {
		if ( is_active_sidebar( 'lightning-child-header-widget' ) ) {
			echo '<div class="lightning-child-header-widget-area">';
			dynamic_sidebar( 'lightning-child-header-widget' );
			echo '</div>';
		}
		return;
	}

	if ( 'contact' !== $layout ) {
		return;
	}

	$options = lightning_child_get_header_contact();
	if ( empty( $options ) ) {
		return;
	}

	$catch       = isset( $options['contact_txt'] ) ? sanitize_text_field( $options['contact_txt'] ) : '';
	$telephone   = isset( $options['tel_number'] ) ? sanitize_text_field( $options['tel_number'] ) : '';
	$office_time = isset( $options['contact_time'] ) ? sanitize_text_field( $options['contact_time'] ) : '';
	$link        = isset( $options['contact_link'] ) ? esc_url( $options['contact_link'] ) : '';
	$button_text = isset( $options['short_text'] ) ? sanitize_text_field( $options['short_text'] ) : '';
	if ( '' === $button_text && isset( $options['button_text'] ) ) {
		$button_text = sanitize_text_field( $options['button_text'] );
	}
	$button_text = $button_text ? $button_text : __( 'お問い合わせ', 'lightning-child' );
	$tel_href    = preg_replace( '/[^0-9+]/', '', $telephone );
	$new_window  = ! empty( $options['contact_target_blank'] );

	if ( '' === $telephone && '' === $link ) {
		return;
	}
	?>
	<div class="site-header-sub lightning-child-header-contact-content">
		<?php if ( '' !== $telephone ) : ?>
			<p class="contact-txt">
				<?php if ( '' !== $catch ) : ?><span class="contact-txt-catch"><?php echo esc_html( $catch ); ?></span><?php endif; ?>
				<a href="tel:<?php echo esc_attr( $tel_href ); ?>"><span class="contact-txt-tel"><i class="contact-txt-tel_icon fa-solid fa-phone" aria-hidden="true"></i><?php echo esc_html( $telephone ); ?></span></a>
				<?php if ( '' !== $office_time ) : ?><span class="contact-txt-time"><?php echo esc_html( $office_time ); ?></span><?php endif; ?>
			</p>
		<?php endif; ?>
		<?php if ( '' !== $link ) : ?>
			<div class="contact-btn">
				<a class="btn btn-primary" href="<?php echo esc_url( $link ); ?>"<?php echo $new_window ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><i class="fa-regular fa-envelope" aria-hidden="true"></i><?php echo esc_html( $button_text ); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'lightning_site_header_logo_after', 'lightning_child_render_header_extra', 10 );

/**
 * Enable or disable Lightning's existing scroll state handler.
 *
 * @param array<string, mixed> $options Lightning JavaScript options.
 * @return array<string, mixed>
 */
function lightning_child_filter_header_scroll_options( $options ) {
	$scroll_layout            = lightning_child_sanitize_header_scroll_layout(
		get_theme_mod( 'lightning_child_header_scroll_layout', 'nav-only' )
	);
	$options['header_scroll'] = 'none' !== $scroll_layout;

	return $options;
}
add_filter( 'lightning_localize_options', 'lightning_child_filter_header_scroll_options', 20 );

/**
 * Return a configured logo attachment URL.
 *
 * @param string $setting_name Theme modification name.
 * @return string
 */
function lightning_child_get_header_logo_url( $setting_name ) {
	$url = wp_get_attachment_image_url( absint( get_theme_mod( $setting_name, 0 ) ), 'full' );
	return $url ? esc_url_raw( $url ) : '';
}

/**
 * Load state-based logo switching only when an alternate logo is configured.
 *
 * @return void
 */
function lightning_child_enqueue_header_logo_state_script() {
	if ( ! lightning_child_is_standard_header_mode() ) {
		return;
	}

	$transparent_logo = lightning_child_get_header_logo_url( 'lightning_child_header_transparent_logo' );
	$scrolled_logo    = lightning_child_get_header_logo_url( 'lightning_child_header_scrolled_logo' );
	if ( ! $transparent_logo && ! $scrolled_logo ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/header-logo-state.js';
	wp_enqueue_script(
		'lightning-child-header-logo-state',
		get_stylesheet_directory_uri() . '/assets/js/header-logo-state.js',
		array( 'lightning-js' ),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);
	wp_localize_script(
		'lightning-child-header-logo-state',
		'lightningChildHeaderLogos',
		array(
			'transparent' => $transparent_logo,
			'scrolled'    => $scrolled_logo,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lightning_child_enqueue_header_logo_state_script', 30 );

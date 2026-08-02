<?php
/**
 * WordPress block template parts integration for Lightning.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable block template parts in this classic child theme.
 *
 * @return void
 */
function lightning_child_add_block_template_parts_support() {
	add_theme_support( 'block-template-parts' );
	add_theme_support(
		'custom-logo',
		array(
			'width'       => 320,
			'height'      => 100,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'lightning_child_add_block_template_parts_support' );

/**
 * Return the default template part slug for an area.
 *
 * @param string $area Header or footer.
 * @return string
 */
function lightning_child_get_default_template_part_slug( $area ) {
	return in_array( $area, array( 'header', 'footer' ), true ) ? $area : '';
}

/**
 * Determine whether a template part exists in the requested area.
 *
 * @param string $slug Template part slug.
 * @param string $area Header or footer.
 * @return bool
 */
function lightning_child_is_template_part_in_area( $slug, $area ) {
	$template_part = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );
	return $template_part
		&& isset( $template_part->area )
		&& $area === $template_part->area;
}

/**
 * Determine whether a template part contains renderable block markup.
 *
 * @param string $slug Template part slug.
 * @param string $area Header or footer.
 * @return bool
 */
function lightning_child_template_part_has_content( $slug, $area ) {
	$template_part = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );
	return $template_part
		&& isset( $template_part->area, $template_part->content )
		&& $area === $template_part->area
		&& is_string( $template_part->content )
		&& '' !== trim( $template_part->content );
}

/**
 * Sanitize a standard or template part selection.
 *
 * The legacy value "block" continues to select the bundled header or footer.
 *
 * @param mixed  $value Submitted value.
 * @param string $area  Header or footer.
 * @return string
 */
function lightning_child_sanitize_template_part_selection( $value, $area ) {
	$default_slug = lightning_child_get_default_template_part_slug( $area );

	if ( ! is_string( $value ) || '' === $default_slug ) {
		return 'standard';
	}

	if ( in_array( $value, array( 'standard', 'block' ), true ) ) {
		return $value;
	}

	if ( 0 !== strpos( $value, 'part:' ) ) {
		return 'standard';
	}

	$slug = sanitize_key( substr( $value, 5 ) );
	if ( '' === $slug ) {
		return 'block';
	}

	if ( ! lightning_child_is_template_part_in_area( $slug, $area ) ) {
		return 'block';
	}

	return 'part:' . $slug;
}

/**
 * Sanitize the header template part selection.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_header_template_part_selection( $value ) {
	return lightning_child_sanitize_template_part_selection( $value, 'header' );
}

/**
 * Sanitize the footer template part selection.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_footer_template_part_selection( $value ) {
	return lightning_child_sanitize_template_part_selection( $value, 'footer' );
}

/**
 * Build Customizer choices from template parts in a specific area.
 *
 * @param string $area Header or footer.
 * @return array<string, string>
 */
function lightning_child_get_template_part_choices( $area ) {
	$default_slug = lightning_child_get_default_template_part_slug( $area );
	$default_label = 'header' === $area
		? __( 'ヘッダー', 'lightning-child' )
		: __( 'フッター', 'lightning-child' );
	$choices = array(
		'standard' => __( 'Lightning標準', 'lightning-child' ),
		'block'    => $default_label,
	);
	$additional_choices = array();
	$template_parts     = get_block_templates( array( 'area' => $area ), 'wp_template_part' );

	foreach ( $template_parts as $template_part ) {
		$slug = isset( $template_part->slug ) ? sanitize_key( $template_part->slug ) : '';
		if ( '' === $slug ) {
			continue;
		}

		$title = isset( $template_part->title ) && is_string( $template_part->title )
			? wp_strip_all_tags( $template_part->title )
			: $slug;
		$title = '' !== $title ? $title : $slug;

		if ( $default_slug === $slug ) {
			$choices['block'] = $title;
		} else {
			$additional_choices[ 'part:' . $slug ] = sprintf(
				/* translators: 1: template part title, 2: template part slug. */
				__( '%1$s（%2$s）', 'lightning-child' ),
				$title,
				$slug
			);
		}
	}

	natcasesort( $additional_choices );
	return array_merge( $choices, $additional_choices );
}

/**
 * Return the selected block template part slug.
 *
 * @param string $area Header or footer.
 * @return string Empty when Lightning's standard output is selected.
 */
function lightning_child_get_selected_template_part_slug( $area ) {
	$default_slug = lightning_child_get_default_template_part_slug( $area );
	if ( '' === $default_slug ) {
		return '';
	}

	$setting_name = 'lightning_child_' . $area . '_mode';
	$value        = get_theme_mod( $setting_name, 'standard' );

	if ( 'block' === $value ) {
		return $default_slug;
	}

	if ( is_string( $value ) && 0 === strpos( $value, 'part:' ) ) {
		$slug = sanitize_key( substr( $value, 5 ) );
		return '' !== $slug ? $slug : $default_slug;
	}

	return '';
}

/**
 * Return the template part slug that can be rendered for an area.
 *
 * A deleted selection falls back to the bundled part. An intentionally empty
 * selected part remains empty so callers can omit related controls.
 *
 * @param string $area Header or footer.
 * @return string
 */
function lightning_child_get_renderable_template_part_slug( $area ) {
	$selected_slug = lightning_child_get_selected_template_part_slug( $area );
	if ( '' === $selected_slug ) {
		return '';
	}

	if ( lightning_child_is_template_part_in_area( $selected_slug, $area ) ) {
		return lightning_child_template_part_has_content( $selected_slug, $area ) ? $selected_slug : '';
	}

	$default_slug = lightning_child_get_default_template_part_slug( $area );
	return lightning_child_template_part_has_content( $default_slug, $area ) ? $default_slug : '';
}

/**
 * Return the configured template part mode.
 *
 * @param string $area Header or footer.
 * @return string
 */
function lightning_child_get_template_part_mode( $area ) {
	return '' === lightning_child_get_selected_template_part_slug( $area ) ? 'standard' : 'block';
}

/**
 * Register standard or block selectors in the existing Customizer sections.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_block_template_parts( $wp_customize ) {
	$areas = array(
		'header' => array(
			'label'             => __( '使用するヘッダー', 'lightning-child' ),
			'section'           => 'lightning_child_header',
			'sanitize_callback' => 'lightning_child_sanitize_header_template_part_selection',
		),
		'footer' => array(
			'label'             => __( '使用するフッター', 'lightning-child' ),
			'section'           => 'lightning_child_footer',
			'sanitize_callback' => 'lightning_child_sanitize_footer_template_part_selection',
		),
	);

	foreach ( $areas as $area => $control ) {
		$setting_name = 'lightning_child_' . $area . '_mode';

		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => 'standard',
				'sanitize_callback' => $control['sanitize_callback'],
			)
		);

		$wp_customize->add_control(
			$setting_name,
			array(
				'label'       => $control['label'],
				'description' => __( '「外観 → デザイン → パターン」のテンプレートパーツから選択できます。作成後はカスタマイザーを開き直してください。', 'lightning-child' ),
				'section'     => $control['section'],
				'type'        => 'select',
				'choices'     => lightning_child_get_template_part_choices( $area ),
				'priority'    => 5,
			)
		);
	}
}
add_action( 'customize_register', 'lightning_child_customize_block_template_parts', 20 );

/**
 * Stop Lightning's standard header when the block header is selected.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_standard_header_for_block_part( $is_visible ) {
	if ( 'block' === lightning_child_get_template_part_mode( 'header' ) ) {
		return false;
	}

	return $is_visible;
}
add_filter( 'lightning_is_site_header', 'lightning_child_filter_standard_header_for_block_part', 20 );

/**
 * Stop Lightning's standard footer when the block footer is selected.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_standard_footer_for_block_part( $is_visible ) {
	if ( 'block' === lightning_child_get_template_part_mode( 'footer' ) ) {
		return false;
	}

	return $is_visible;
}
add_filter( 'lightning_is_site_footer', 'lightning_child_filter_standard_footer_for_block_part', 20 );

/**
 * Render the editable block header before Lightning's standard header position.
 *
 * @return void
 */
function lightning_child_render_block_header() {
	$template_part_slug = lightning_child_get_renderable_template_part_slug( 'header' );
	if ( '' === $template_part_slug ) {
		return;
	}

	if ( lightning_child_is_page_element_hidden( '_lightning_child_hide_site_header' ) ) {
		return;
	}

	echo '<header id="site-header" class="site-header block-site-header lightning-child-block-site-header">';
	block_template_part( $template_part_slug );
	echo '</header>';
}
add_action( 'lightning_site_header_before', 'lightning_child_render_block_header' );

/**
 * Render the editable block footer before Lightning's standard footer position.
 *
 * @return void
 */
function lightning_child_render_block_footer() {
	$template_part_slug = lightning_child_get_renderable_template_part_slug( 'footer' );
	if ( '' === $template_part_slug ) {
		return;
	}

	if ( lightning_child_is_page_element_hidden( '_lightning_child_hide_site_footer' ) ) {
		return;
	}

	echo '<footer id="lightning-child-block-site-footer" class="block-site-footer lightning-child-block-site-footer">';
	block_template_part( $template_part_slug );

	$copyright_html = lightning_child_get_copyright_html();
	if ( '' !== $copyright_html ) {
		echo '<div class="lightning-child-block-site-footer__copyright">';
		echo '<div class="container site-footer-copyright">';
		echo wp_kses_post( $copyright_html );
		echo '</div>';
		echo '</div>';
	}

	echo '</footer>';
}
add_action( 'lightning_site_footer_before', 'lightning_child_render_block_footer' );

/**
 * Determine whether the current screen is provided by The Events Calendar.
 *
 * @return bool
 */
function lightning_child_is_the_events_calendar_view() {
	if ( class_exists( '\\VektorInc\\VK_Helpers\\VkHelpers' ) ) {
		$post_type_info = \VektorInc\VK_Helpers\VkHelpers::get_post_type_info();
		return isset( $post_type_info['slug'] ) && 'tribe_events' === $post_type_info['slug'];
	}

	return 'tribe_events' === get_post_type();
}

/**
 * Render the appropriate header on The Events Calendar templates.
 *
 * @return void
 */
function lightning_child_tec_load_header() {
	if ( 'block' !== lightning_child_get_template_part_mode( 'header' ) ) {
		if ( function_exists( 'lightning_g3_tec_load_header' ) ) {
			lightning_g3_tec_load_header();
		}
		return;
	}

	if ( lightning_child_is_the_events_calendar_view() ) {
		lightning_child_render_block_header();
	}
}

/**
 * Render the appropriate footer on The Events Calendar templates.
 *
 * @return void
 */
function lightning_child_tec_load_footer() {
	if ( 'block' !== lightning_child_get_template_part_mode( 'footer' ) ) {
		if ( function_exists( 'lightning_g3_tec_load_footer' ) ) {
			lightning_g3_tec_load_footer();
		}
		return;
	}

	if ( lightning_child_is_the_events_calendar_view() ) {
		lightning_child_render_block_footer();
	}
}

/**
 * Replace Lightning's direct event header and footer callbacks with dispatchers.
 *
 * @return void
 */
function lightning_child_replace_tec_template_callbacks() {
	if ( function_exists( 'lightning_g3_tec_load_header' )
		&& remove_action( 'wp_body_open', 'lightning_g3_tec_load_header' ) ) {
		add_action( 'wp_body_open', 'lightning_child_tec_load_header' );
	}

	if ( function_exists( 'lightning_g3_tec_load_footer' )
		&& remove_action( 'wp_footer', 'lightning_g3_tec_load_footer', 1 ) ) {
		add_action( 'wp_footer', 'lightning_child_tec_load_footer', 1 );
	}
}
add_action( 'after_setup_theme', 'lightning_child_replace_tec_template_callbacks', 20 );

/**
 * Prevent duplicate mobile menus when the block header uses core Navigation.
 *
 * @param string $hook_name Lightning mobile navigation output hook.
 * @return string
 */
function lightning_child_filter_mobile_nav_hook_for_block_header( $hook_name ) {
	if ( 'block' === lightning_child_get_template_part_mode( 'header' ) ) {
		return 'lightning_child_disabled_mobile_nav';
	}

	return $hook_name;
}
add_filter( 'vk_mobile_nav_html_hook_point', 'lightning_child_filter_mobile_nav_hook_for_block_header', 20 );

/**
 * Add body classes for active block template parts.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function lightning_child_add_block_template_part_body_classes( $classes ) {
	if ( 'block' === lightning_child_get_template_part_mode( 'header' ) ) {
		$classes[] = 'lightning-child-block-header-active';
	}

	if ( 'block' === lightning_child_get_template_part_mode( 'footer' ) ) {
		$classes[] = 'lightning-child-block-footer-active';
	}

	return $classes;
}
add_filter( 'body_class', 'lightning_child_add_block_template_part_body_classes' );

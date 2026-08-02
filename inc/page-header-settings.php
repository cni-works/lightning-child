<?php
/**
 * Page header settings for Lightning G3.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return post types supported by page header settings.
 *
 * @return WP_Post_Type[]
 */
function lightning_child_get_page_header_post_types() {
	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'objects'
	);

	return apply_filters( 'lightning_child_page_header_post_types', $post_types );
}

/**
 * Build a theme modification name for a post type and field.
 *
 * @param string $post_type Post type name.
 * @param string $field     Field name.
 * @return string
 */
function lightning_child_get_page_header_setting_name( $post_type, $field ) {
	return 'lightning_child_page_header_' . sanitize_key( $post_type ) . '_' . sanitize_key( $field );
}

/**
 * Sanitize the page header title source.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_page_header_title_source( $value ) {
	$choices = array( 'default', 'post_type', 'post_title' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'default';
}

/**
 * Sanitize text alignment.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_page_header_alignment( $value ) {
	$choices = array( 'left', 'center', 'right' );
	return is_string( $value ) && in_array( $value, $choices, true ) ? $value : 'center';
}

/**
 * Sanitize background attachment.
 *
 * @param string $value Submitted value.
 * @return string
 */
function lightning_child_sanitize_page_header_attachment( $value ) {
	return 'fixed' === $value ? 'fixed' : 'scroll';
}

/**
 * Sanitize page header height in rem.
 *
 * @param mixed $value Submitted value.
 * @return float
 */
function lightning_child_sanitize_page_header_height( $value ) {
	if ( ! is_numeric( $value ) ) {
		return 8;
	}

	return min( 30, max( 4, (float) $value ) );
}

/**
 * Sanitize a value between zero and one.
 *
 * @param mixed $value Submitted value.
 * @return float
 */
function lightning_child_sanitize_page_header_opacity( $value ) {
	if ( ! is_numeric( $value ) ) {
		return 0;
	}

	return min( 1, max( 0, (float) $value ) );
}

/**
 * Return individual page-header metadata definitions.
 *
 * @return array<string, array<string, mixed>>
 */
function lightning_child_get_page_header_meta_fields() {
	return array(
		'_lightning_child_page_header_custom_enabled' => array(
			'type'     => 'boolean',
			'default'  => false,
			'sanitize' => 'lightning_child_sanitize_boolean',
		),
		'_lightning_child_page_header_catchphrase'    => array(
			'type'     => 'string',
			'default'  => '',
			'sanitize' => 'sanitize_text_field',
		),
		'_lightning_child_page_header_subtext'        => array(
			'type'     => 'string',
			'default'  => '',
			'sanitize' => 'sanitize_text_field',
		),
		'_lightning_child_page_header_image_id'       => array(
			'type'     => 'integer',
			'default'  => 0,
			'sanitize' => 'absint',
		),
		'_lightning_child_page_header_mobile_image_id' => array(
			'type'     => 'integer',
			'default'  => 0,
			'sanitize' => 'absint',
		),
		'_lightning_child_page_header_overlay_custom_enabled' => array(
			'type'     => 'boolean',
			'default'  => false,
			'sanitize' => 'lightning_child_sanitize_boolean',
		),
		'_lightning_child_page_header_overlay_color' => array(
			'type'     => 'string',
			'default'  => '#000000',
			'sanitize' => 'sanitize_hex_color',
		),
		'_lightning_child_page_header_overlay_opacity' => array(
			'type'     => 'number',
			'default'  => 0.4,
			'sanitize' => 'lightning_child_sanitize_page_header_opacity',
		),
	);
}

/**
 * Authorize updates to individual page-header metadata.
 *
 * @param bool   $allowed  Existing authorization result.
 * @param string $meta_key Current meta key.
 * @param int    $post_id  Current post ID.
 * @return bool
 */
function lightning_child_auth_page_header_meta( $allowed, $meta_key, $post_id ) {
	unset( $allowed, $meta_key );
	return current_user_can( 'edit_post', $post_id );
}

/**
 * Register individual header metadata for REST-backed editors.
 *
 * @return void
 */
function lightning_child_register_page_header_meta() {
	foreach ( lightning_child_get_page_header_post_types() as $post_type ) {
		if ( 'attachment' === $post_type->name ) {
			continue;
		}

		if ( ! empty( $post_type->show_in_rest ) && ! post_type_supports( $post_type->name, 'custom-fields' ) ) {
			add_post_type_support( $post_type->name, 'custom-fields' );
		}

		foreach ( lightning_child_get_page_header_meta_fields() as $meta_key => $config ) {
			register_post_meta(
				$post_type->name,
				$meta_key,
				array(
					'type'              => $config['type'],
					'single'            => true,
					'default'           => $config['default'],
					'show_in_rest'      => true,
					'sanitize_callback' => $config['sanitize'],
					'auth_callback'     => 'lightning_child_auth_page_header_meta',
				)
			);
		}
	}
}
add_action( 'init', 'lightning_child_register_page_header_meta', 100 );

/**
 * Determine whether the current singular content uses individual header settings.
 *
 * @return bool
 */
function lightning_child_is_individual_page_header_enabled() {
	if ( ! is_singular() || 'attachment' === get_post_type( get_queried_object_id() ) ) {
		return false;
	}

	$page_id = get_queried_object_id();
	return 0 < $page_id && (bool) get_post_meta( $page_id, '_lightning_child_page_header_custom_enabled', true );
}

/**
 * Load the individual page-header panel in the block editor.
 *
 * @return void
 */
function lightning_child_enqueue_page_header_panel() {
	$screen = get_current_screen();
	$supported_post_types = wp_list_pluck( lightning_child_get_page_header_post_types(), 'name' );
	if ( ! $screen || ! in_array( $screen->post_type, $supported_post_types, true ) || 'attachment' === $screen->post_type || ! $screen->is_block_editor ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/page-header-panel.js';
	wp_enqueue_script(
		'lightning-child-page-header-panel',
		get_stylesheet_directory_uri() . '/assets/js/page-header-panel.js',
		array( 'wp-block-editor', 'wp-components', 'wp-core-data', 'wp-data', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);

	wp_localize_script(
		'lightning-child-page-header-panel',
		'lightningChildPageHeader',
		array(
			'postType'         => $screen->post_type,
			'panelTitle'       => __( 'ページヘッダー個別設定', 'lightning-child' ),
			'enabledLabel'     => __( 'このコンテンツで個別設定を使用する', 'lightning-child' ),
			'enabledHelp'      => __( '有効にすると、空欄は非表示、画像未選択は背景画像なしとして扱います。モバイル画像だけ未選択の場合はPC画像を使用します。', 'lightning-child' ),
			'catchphraseLabel' => __( 'キャッチフレーズ', 'lightning-child' ),
			'subtextLabel'     => __( 'サブテキスト', 'lightning-child' ),
			'desktopImage'     => __( '背景画像（PC）', 'lightning-child' ),
			'mobileImage'      => __( '背景画像（モバイル）', 'lightning-child' ),
			'overlayEnabled'   => __( 'オーバーレイを個別設定する', 'lightning-child' ),
			'overlayHelp'      => __( '無効の場合は、この投稿タイプのオーバーレイ設定を引き継ぎます。', 'lightning-child' ),
			'overlayColor'     => __( 'オーバーレイの色', 'lightning-child' ),
			'overlayOpacity'   => __( 'オーバーレイの濃さ（不透明度）', 'lightning-child' ),
			'selectImage'      => __( '画像を選択', 'lightning-child' ),
			'changeImage'      => __( '画像を変更', 'lightning-child' ),
			'removeImage'      => __( '画像を解除', 'lightning-child' ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'lightning_child_enqueue_page_header_panel' );

/**
 * Register a Classic Editor fallback meta box.
 *
 * @return void
 */
function lightning_child_add_page_header_meta_box() {
	foreach ( lightning_child_get_page_header_post_types() as $post_type ) {
		if ( 'attachment' === $post_type->name ) {
			continue;
		}

		add_meta_box(
			'lightning_child_page_header_individual',
			__( 'ページヘッダー個別設定', 'lightning-child' ),
			'lightning_child_render_page_header_meta_box',
			$post_type->name,
			'side',
			'default',
			array( '__back_compat_meta_box' => true )
		);
	}
}
add_action( 'add_meta_boxes', 'lightning_child_add_page_header_meta_box' );

/**
 * Render one image field in the Classic Editor fallback.
 *
 * @param WP_Post $post     Current post.
 * @param string  $meta_key Image meta key.
 * @param string  $label    Field label.
 * @return void
 */
function lightning_child_render_page_header_image_field( $post, $meta_key, $label ) {
	$attachment_id = absint( get_post_meta( $post->ID, $meta_key, true ) );
	?>
	<p><strong><?php echo esc_html( $label ); ?></strong></p>
	<div class="lightning-child-page-header-image-field" data-lightning-child-image-field>
		<div data-lightning-child-image-preview><?php echo $attachment_id ? wp_kses_post( wp_get_attachment_image( $attachment_id, 'thumbnail' ) ) : ''; ?></div>
		<input type="hidden" name="<?php echo esc_attr( $meta_key ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" data-lightning-child-image-input>
		<p>
			<button type="button" class="button" data-lightning-child-image-select><?php esc_html_e( '画像を選択・変更', 'lightning-child' ); ?></button>
			<button type="button" class="button-link-delete" data-lightning-child-image-remove<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( '解除', 'lightning-child' ); ?></button>
		</p>
	</div>
	<?php
}

/**
 * Render the Classic Editor page-header fields.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function lightning_child_render_page_header_meta_box( $post ) {
	wp_nonce_field( 'lightning_child_save_page_header', 'lightning_child_page_header_nonce' );
	$enabled         = (bool) get_post_meta( $post->ID, '_lightning_child_page_header_custom_enabled', true );
	$catchphrase     = (string) get_post_meta( $post->ID, '_lightning_child_page_header_catchphrase', true );
	$subtext         = (string) get_post_meta( $post->ID, '_lightning_child_page_header_subtext', true );
	$overlay_custom  = (bool) get_post_meta( $post->ID, '_lightning_child_page_header_overlay_custom_enabled', true );
	$overlay_color   = sanitize_hex_color( get_post_meta( $post->ID, '_lightning_child_page_header_overlay_color', true ) );
	$overlay_color   = $overlay_color ? $overlay_color : '#000000';
	$overlay_opacity = metadata_exists( 'post', $post->ID, '_lightning_child_page_header_overlay_opacity' )
		? lightning_child_sanitize_page_header_opacity( get_post_meta( $post->ID, '_lightning_child_page_header_overlay_opacity', true ) )
		: 0.4;
	?>
	<p><label><input type="checkbox" name="_lightning_child_page_header_custom_enabled" value="1" <?php checked( $enabled ); ?>> <?php esc_html_e( 'このコンテンツで個別設定を使用する', 'lightning-child' ); ?></label></p>
	<p><label><strong><?php esc_html_e( 'キャッチフレーズ', 'lightning-child' ); ?></strong><br><input class="widefat" type="text" name="_lightning_child_page_header_catchphrase" value="<?php echo esc_attr( $catchphrase ); ?>"></label></p>
	<p><label><strong><?php esc_html_e( 'サブテキスト', 'lightning-child' ); ?></strong><br><input class="widefat" type="text" name="_lightning_child_page_header_subtext" value="<?php echo esc_attr( $subtext ); ?>"></label></p>
	<?php
	lightning_child_render_page_header_image_field( $post, '_lightning_child_page_header_image_id', __( '背景画像（PC）', 'lightning-child' ) );
	lightning_child_render_page_header_image_field( $post, '_lightning_child_page_header_mobile_image_id', __( '背景画像（モバイル）', 'lightning-child' ) );
	?>
	<hr>
	<p><label><input type="checkbox" name="_lightning_child_page_header_overlay_custom_enabled" value="1" <?php checked( $overlay_custom ); ?>> <?php esc_html_e( 'オーバーレイを個別設定する', 'lightning-child' ); ?></label></p>
	<p><label><strong><?php esc_html_e( 'オーバーレイの色', 'lightning-child' ); ?></strong><br><input type="color" name="_lightning_child_page_header_overlay_color" value="<?php echo esc_attr( $overlay_color ); ?>"></label></p>
	<p><label><strong><?php esc_html_e( 'オーバーレイの濃さ（不透明度）', 'lightning-child' ); ?></strong><br><input class="widefat" type="number" min="0" max="1" step="0.1" name="_lightning_child_page_header_overlay_opacity" value="<?php echo esc_attr( $overlay_opacity ); ?>"></label></p>
	<p class="description"><?php esc_html_e( '個別設定を無効にすると、この投稿タイプの色と透明率を引き継ぎます。', 'lightning-child' ); ?></p>
	<?php
}

/**
 * Load media controls for the Classic Editor fallback.
 *
 * @return void
 */
function lightning_child_enqueue_page_header_classic_assets() {
	$screen = get_current_screen();
	$supported_post_types = wp_list_pluck( lightning_child_get_page_header_post_types(), 'name' );
	if ( ! $screen || ! in_array( $screen->post_type, $supported_post_types, true ) || 'attachment' === $screen->post_type || $screen->is_block_editor ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/js/page-header-classic.js';
	wp_enqueue_media();
	wp_enqueue_script(
		'lightning-child-page-header-classic',
		get_stylesheet_directory_uri() . '/assets/js/page-header-classic.js',
		array( 'jquery' ),
		file_exists( $script_path ) ? filemtime( $script_path ) : null,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'lightning_child_enqueue_page_header_classic_assets' );

/**
 * Save individual page-header fields from the Classic Editor.
 *
 * @param int $post_id Current post ID.
 * @return void
 */
function lightning_child_save_page_header_meta( $post_id ) {
	$supported_post_types = wp_list_pluck( lightning_child_get_page_header_post_types(), 'name' );
	if ( ! in_array( get_post_type( $post_id ), $supported_post_types, true ) || 'attachment' === get_post_type( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['lightning_child_page_header_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lightning_child_page_header_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'lightning_child_save_page_header' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, '_lightning_child_page_header_custom_enabled', isset( $_POST['_lightning_child_page_header_custom_enabled'] ) );

	$text_fields = array( '_lightning_child_page_header_catchphrase', '_lightning_child_page_header_subtext' );
	foreach ( $text_fields as $meta_key ) {
		$value = isset( $_POST[ $meta_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) : '';
		update_post_meta( $post_id, $meta_key, $value );
	}

	$image_fields = array( '_lightning_child_page_header_image_id', '_lightning_child_page_header_mobile_image_id' );
	foreach ( $image_fields as $meta_key ) {
		$value = isset( $_POST[ $meta_key ] ) ? absint( wp_unslash( $_POST[ $meta_key ] ) ) : 0;
		update_post_meta( $post_id, $meta_key, $value );
	}

	update_post_meta( $post_id, '_lightning_child_page_header_overlay_custom_enabled', isset( $_POST['_lightning_child_page_header_overlay_custom_enabled'] ) );
	$overlay_color = isset( $_POST['_lightning_child_page_header_overlay_color'] )
		? sanitize_hex_color( wp_unslash( $_POST['_lightning_child_page_header_overlay_color'] ) )
		: '#000000';
	update_post_meta( $post_id, '_lightning_child_page_header_overlay_color', $overlay_color ? $overlay_color : '#000000' );
	$overlay_opacity = isset( $_POST['_lightning_child_page_header_overlay_opacity'] )
		? lightning_child_sanitize_page_header_opacity( wp_unslash( $_POST['_lightning_child_page_header_overlay_opacity'] ) )
		: 0.4;
	update_post_meta( $post_id, '_lightning_child_page_header_overlay_opacity', $overlay_opacity );
}
add_action( 'save_post', 'lightning_child_save_page_header_meta' );

/**
 * Register page header settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function lightning_child_customize_page_header_settings( $wp_customize ) {
	$wp_customize->add_panel(
		'lightning_child_page_header',
		array(
			'title'       => __( 'Lightning ページヘッダー設定', 'lightning-child' ),
			'description' => __( 'ページ上部のタイトル領域を設定します。親テーマのページヘッダーをそのまま利用します。', 'lightning-child' ),
			'priority'    => 166,
		)
	);

	$wp_customize->add_section(
		'lightning_child_page_header_common',
		array(
			'title' => __( '共通', 'lightning-child' ),
			'panel' => 'lightning_child_page_header',
		)
	);

	$wp_customize->add_setting(
		'lightning_child_page_header_hide_all',
		array(
			'default'           => false,
			'sanitize_callback' => 'lightning_child_sanitize_boolean',
		)
	);
	$wp_customize->add_control(
		'lightning_child_page_header_hide_all',
		array(
			'label'       => __( 'ページヘッダーをサイト全体で非表示にする', 'lightning-child' ),
			'description' => __( '投稿・固定ページ・アーカイブなど、Lightningのページヘッダーをすべて非表示にします。', 'lightning-child' ),
			'section'     => 'lightning_child_page_header_common',
			'type'        => 'checkbox',
		)
	);

	$image_settings = array(
		'lightning_child_page_header_image'        => __( '背景画像（PC）', 'lightning-child' ),
		'lightning_child_page_header_mobile_image' => __( '背景画像（モバイル）', 'lightning-child' ),
	);

	foreach ( $image_settings as $setting_name => $label ) {
		$wp_customize->add_setting(
			$setting_name,
			array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Media_Control(
				$wp_customize,
				$setting_name,
				array(
					'label'     => $label,
					'section'   => 'lightning_child_page_header_common',
					'mime_type' => 'image',
				)
			)
		);
	}

	$wp_customize->add_setting(
		'lightning_child_page_header_attachment',
		array(
			'default'           => 'scroll',
			'sanitize_callback' => 'lightning_child_sanitize_page_header_attachment',
		)
	);

	$wp_customize->add_control(
		'lightning_child_page_header_attachment',
		array(
			'label'       => __( '背景画像の位置', 'lightning-child' ),
			'description' => __( '「固定」は一部のモバイルブラウザーでは反映されません。', 'lightning-child' ),
			'section'     => 'lightning_child_page_header_common',
			'type'        => 'radio',
			'choices'     => array(
				'scroll' => __( '標準', 'lightning-child' ),
				'fixed'  => __( '固定', 'lightning-child' ),
			),
		)
	);

	foreach ( lightning_child_get_page_header_post_types() as $post_type ) {
		$post_type_name = sanitize_key( $post_type->name );
		$section_id     = 'lightning_child_page_header_' . $post_type_name;

		$wp_customize->add_section(
			$section_id,
			array(
				'title'       => $post_type->labels->name,
				'description' => sprintf(
					/* translators: %s: post type label. */
					__( '%sの個別ページと一覧に適用します。', 'lightning-child' ),
					$post_type->labels->name
				),
				'panel'       => 'lightning_child_page_header',
			)
		);

		$title_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'title_source' );
		$wp_customize->add_setting(
			$title_setting,
			array(
				'default'           => 'default',
				'sanitize_callback' => 'lightning_child_sanitize_page_header_title_source',
			)
		);
		$wp_customize->add_control(
			$title_setting,
			array(
				'label'   => __( '表示するタイトル', 'lightning-child' ),
				'section' => $section_id,
				'type'    => 'radio',
				'choices' => array(
					'default'    => __( 'Lightning標準', 'lightning-child' ),
					'post_type'  => __( '投稿タイプ名', 'lightning-child' ),
					'post_title' => __( '個別ページのタイトル（一覧はLightning標準）', 'lightning-child' ),
				),
			)
		);

		$text_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'subtitle' );
		$wp_customize->add_setting(
			$text_setting,
			array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$text_setting,
			array(
				'label'   => __( 'サブタイトル', 'lightning-child' ),
				'section' => $section_id,
				'type'    => 'text',
			)
		);

		$post_type_images = array(
			'image'        => array(
				'label'       => __( '背景画像（PC）', 'lightning-child' ),
				'description' => __( '未選択の場合は共通のPC画像を使用します。', 'lightning-child' ),
			),
			'mobile_image' => array(
				'label'       => __( '背景画像（モバイル）', 'lightning-child' ),
				'description' => __( '未選択の場合は、この投稿タイプのPC画像を使用します。', 'lightning-child' ),
			),
		);
		foreach ( $post_type_images as $field => $image_control ) {
			$setting_name = lightning_child_get_page_header_setting_name( $post_type_name, $field );
			$wp_customize->add_setting(
				$setting_name,
				array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Media_Control(
					$wp_customize,
					$setting_name,
					array(
						'label'       => $image_control['label'],
						'description' => $image_control['description'],
						'section'     => $section_id,
						'mime_type'   => 'image',
					)
				)
			);
		}

		if ( ! in_array( $post_type_name, array( 'page', 'attachment' ), true ) ) {
			$hide_singular_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'hide_singular' );
			$wp_customize->add_setting(
				$hide_singular_setting,
				array(
					'default'           => false,
					'sanitize_callback' => 'lightning_child_sanitize_boolean',
				)
			);
			$wp_customize->add_control(
				$hide_singular_setting,
				array(
					'label'       => __( '個別ページのページヘッダーを非表示にする', 'lightning-child' ),
					'description' => __( 'この投稿タイプの詳細ページに適用します。', 'lightning-child' ),
					'section'     => $section_id,
					'type'        => 'checkbox',
				)
			);
		}

		if ( 'post' === $post_type_name || ! empty( $post_type->has_archive ) ) {
			$hide_archive_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'hide_archive' );
			$wp_customize->add_setting(
				$hide_archive_setting,
				array(
					'default'           => false,
					'sanitize_callback' => 'lightning_child_sanitize_boolean',
				)
			);
			$wp_customize->add_control(
				$hide_archive_setting,
				array(
					'label'       => __( '一覧・アーカイブのページヘッダーを非表示にする', 'lightning-child' ),
					'description' => 'post' === $post_type_name
						? __( '投稿一覧、カテゴリー、タグ、日付、投稿者アーカイブに適用します。', 'lightning-child' )
						: __( 'この投稿タイプのアーカイブに適用します。共有カスタム分類は対象外です。', 'lightning-child' ),
					'section'     => $section_id,
					'type'        => 'checkbox',
				)
			);
		}

		$color_settings = array(
			'text_color'    => __( '文字色', 'lightning-child' ),
			'shadow_color'  => __( '文字の影の色', 'lightning-child' ),
			'overlay_color' => __( 'オーバーレイの色', 'lightning-child' ),
		);

		foreach ( $color_settings as $field => $label ) {
			$setting_name = lightning_child_get_page_header_setting_name( $post_type_name, $field );
			$wp_customize->add_setting(
				$setting_name,
				array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_hex_color',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$setting_name,
					array(
						'label'   => $label,
						'section' => $section_id,
					)
				)
			);
		}

		$alignment_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'alignment' );
		$wp_customize->add_setting(
			$alignment_setting,
			array(
				'default'           => 'center',
				'sanitize_callback' => 'lightning_child_sanitize_page_header_alignment',
			)
		);
		$wp_customize->add_control(
			$alignment_setting,
			array(
				'label'   => __( '文字の位置', 'lightning-child' ),
				'section' => $section_id,
				'type'    => 'radio',
				'choices' => array(
					'left'   => __( '左', 'lightning-child' ),
					'center' => __( '中央', 'lightning-child' ),
					'right'  => __( '右', 'lightning-child' ),
				),
			)
		);

		$height_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'min_height' );
		$wp_customize->add_setting(
			$height_setting,
			array(
				'default'           => 8,
				'sanitize_callback' => 'lightning_child_sanitize_page_header_height',
			)
		);
		$wp_customize->add_control(
			$height_setting,
			array(
				'label'       => __( '最小高さ', 'lightning-child' ),
				'description' => __( '4〜30remの範囲で指定します。', 'lightning-child' ),
				'section'     => $section_id,
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 4,
					'max'  => 30,
					'step' => 0.5,
				),
			)
		);

		$opacity_setting = lightning_child_get_page_header_setting_name( $post_type_name, 'overlay_opacity' );
		$wp_customize->add_setting(
			$opacity_setting,
			array(
				'default'           => 0,
				'sanitize_callback' => 'lightning_child_sanitize_page_header_opacity',
			)
		);
		$wp_customize->add_control(
			$opacity_setting,
			array(
				'label'       => __( 'オーバーレイの濃さ（不透明度）', 'lightning-child' ),
				'description' => __( '0（透明）から1（不透明）の数字で入力してください。', 'lightning-child' ),
				'section'     => $section_id,
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 1,
					'step' => 0.1,
				),
			)
		);
	}
}
add_action( 'customize_register', 'lightning_child_customize_page_header_settings' );

/**
 * Apply the global page header visibility setting.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_global_page_header_visibility( $is_visible ) {
	if ( rest_sanitize_boolean( get_theme_mod( 'lightning_child_page_header_hide_all', false ) ) ) {
		return false;
	}

	return $is_visible;
}
add_filter( 'lightning_is_page_header', 'lightning_child_filter_global_page_header_visibility', 30 );

/**
 * Return the post type associated with the current page header.
 *
 * @return string
 */
function lightning_child_get_current_page_header_post_type() {
	if ( is_singular() ) {
		return sanitize_key( (string) get_post_type( get_queried_object_id() ) );
	}

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
 * Apply post-type visibility settings to singular and archive headers.
 *
 * @param bool $is_visible Current visibility.
 * @return bool
 */
function lightning_child_filter_post_type_page_header_visibility( $is_visible ) {
	if ( ! $is_visible ) {
		return false;
	}

	$post_type = lightning_child_get_current_page_header_post_type();
	if ( ! $post_type ) {
		return $is_visible;
	}

	if ( ! in_array( $post_type, array( 'page', 'attachment' ), true )
		&& is_singular( $post_type )
		&& rest_sanitize_boolean(
			get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'hide_singular' ), false )
		) ) {
		return false;
	}

	$is_post_archive = 'post' === $post_type
		&& ( is_home() || is_category() || is_tag() || is_date() || is_author() );
	$is_cpt_archive  = 'post' !== $post_type && is_post_type_archive( $post_type );
	if ( ( $is_post_archive || $is_cpt_archive )
		&& rest_sanitize_boolean(
			get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'hide_archive' ), false )
		) ) {
		return false;
	}

	return $is_visible;
}
add_filter( 'lightning_is_page_header', 'lightning_child_filter_post_type_page_header_visibility', 25 );

/**
 * Return a sanitized color theme modification, or an empty string.
 *
 * @param string $setting_name Theme modification name.
 * @return string
 */
function lightning_child_get_page_header_color( $setting_name ) {
	$color = sanitize_hex_color( get_theme_mod( $setting_name, '' ) );
	return $color ? $color : '';
}

/**
 * Replace the title when requested and append the configured subtitle.
 *
 * @param string $title_html Lightning page header title HTML.
 * @return string
 */
function lightning_child_filter_page_header_title_html( $title_html ) {
	$post_type = lightning_child_get_current_page_header_post_type();
	if ( ! $post_type ) {
		return $title_html;
	}

	if ( ! preg_match( '/\A(<(?:h1|div)\b[^>]*>)(.*)(<\/(?:h1|div)>)\z/s', trim( $title_html ), $matches ) ) {
		return $title_html;
	}

	$title_source = lightning_child_sanitize_page_header_title_source(
		get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'title_source' ), 'default' )
	);
	$title        = $matches[2];

	if ( 'post_type' === $title_source ) {
		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type_object ) {
			$title = esc_html( $post_type_object->labels->name );
		}
	} elseif ( 'post_title' === $title_source && is_singular( $post_type ) ) {
		$title = esc_html( get_the_title( get_queried_object_id() ) );
	}

	$catchphrase = '';
	if ( lightning_child_is_individual_page_header_enabled() ) {
		$page_id     = get_queried_object_id();
		$catchphrase = (string) get_post_meta( $page_id, '_lightning_child_page_header_catchphrase', true );
		$subtitle    = (string) get_post_meta( $page_id, '_lightning_child_page_header_subtext', true );
	} else {
		$subtitle = get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'subtitle' ), '' );
	}

	$catchphrase = trim( $catchphrase );
	$subtitle    = is_string( $subtitle ) ? trim( $subtitle ) : '';
	if ( 'default' === $title_source && '' === $catchphrase && '' === $subtitle ) {
		return $title_html;
	}

	if ( '' !== $catchphrase ) {
		$title = '<span class="page-header-catchphrase">' . esc_html( $catchphrase ) . '</span>' . $title;
	}

	if ( '' !== $subtitle ) {
		$title .= '<span class="page-header-subtext">' . esc_html( $subtitle ) . '</span>';
	}

	return $matches[1] . $title . $matches[3];
}
add_filter( 'lightning_page_header_title_html', 'lightning_child_filter_page_header_title_html', 20 );

/**
 * Convert a hex color to an rgba color.
 *
 * @param string $hex     Hex color.
 * @param float  $opacity Opacity between zero and one.
 * @return string
 */
function lightning_child_page_header_rgba( $hex, $opacity ) {
	$hex = ltrim( $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		return 'rgba(0,0,0,0)';
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
 * Return a safe CSS URL value for an attachment.
 *
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function lightning_child_get_page_header_image_css( $attachment_id ) {
	$url = wp_get_attachment_image_url( absint( $attachment_id ), 'full' );
	if ( ! $url ) {
		return 'none';
	}

	$url = esc_url_raw( $url );
	$url = str_replace( array( '"', "'", '(', ')', "\n", "\r" ), '', $url );
	return 'url("' . $url . '")';
}

/**
 * Add page header CSS variables for the current request.
 *
 * @return void
 */
function lightning_child_add_page_header_css() {
	$post_type = lightning_child_get_current_page_header_post_type();
	if ( ! $post_type ) {
		return;
	}

	$alignment = lightning_child_sanitize_page_header_alignment(
		get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'alignment' ), 'center' )
	);
	$height    = lightning_child_sanitize_page_header_height(
		get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'min_height' ), 8 )
	);
	$opacity   = lightning_child_sanitize_page_header_opacity(
		get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'overlay_opacity' ), 0 )
	);

	$text_color    = lightning_child_get_page_header_color( lightning_child_get_page_header_setting_name( $post_type, 'text_color' ) );
	$shadow_color  = lightning_child_get_page_header_color( lightning_child_get_page_header_setting_name( $post_type, 'shadow_color' ) );
	$overlay_color = lightning_child_get_page_header_color( lightning_child_get_page_header_setting_name( $post_type, 'overlay_color' ) );
	$overlay_color = $overlay_color ? $overlay_color : '#000000';

	if ( lightning_child_is_individual_page_header_enabled() ) {
		$page_id          = get_queried_object_id();
		$desktop_image_id = absint( get_post_meta( $page_id, '_lightning_child_page_header_image_id', true ) );
		$mobile_image_id  = absint( get_post_meta( $page_id, '_lightning_child_page_header_mobile_image_id', true ) );
		if ( (bool) get_post_meta( $page_id, '_lightning_child_page_header_overlay_custom_enabled', true ) ) {
			$individual_overlay_color = sanitize_hex_color( get_post_meta( $page_id, '_lightning_child_page_header_overlay_color', true ) );
			$overlay_color = $individual_overlay_color ? $individual_overlay_color : '#000000';
			$opacity = lightning_child_sanitize_page_header_opacity(
				get_post_meta( $page_id, '_lightning_child_page_header_overlay_opacity', true )
			);
		}
	} else {
		$post_type_desktop_image_id = absint(
			get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'image' ), 0 )
		);
		$mobile_image_id = absint(
			get_theme_mod( lightning_child_get_page_header_setting_name( $post_type, 'mobile_image' ), 0 )
		);
		$desktop_image_id = $post_type_desktop_image_id
			? $post_type_desktop_image_id
			: absint( get_theme_mod( 'lightning_child_page_header_image', 0 ) );
		if ( ! $mobile_image_id ) {
			$mobile_image_id = $post_type_desktop_image_id
				? $post_type_desktop_image_id
				: absint( get_theme_mod( 'lightning_child_page_header_mobile_image', 0 ) );
		}
	}

	$desktop_image = lightning_child_get_page_header_image_css( $desktop_image_id );
	$mobile_image  = lightning_child_get_page_header_image_css( $mobile_image_id );
	if ( 'none' === $mobile_image ) {
		$mobile_image = $desktop_image;
	}

	$overlay = lightning_child_page_header_rgba( $overlay_color, $opacity );
	$desktop_background = 'linear-gradient(' . $overlay . ',' . $overlay . ')';
	$mobile_background  = $desktop_background;
	if ( 'none' !== $desktop_image ) {
		$desktop_background .= ',' . $desktop_image;
	}
	if ( 'none' !== $mobile_image ) {
		$mobile_background .= ',' . $mobile_image;
	}

	$attachment = lightning_child_sanitize_page_header_attachment(
		get_theme_mod( 'lightning_child_page_header_attachment', 'scroll' )
	);
	$css = '';
	if ( 'center' !== $alignment || 8.0 !== (float) $height ) {
		$css .= '.page-header{text-align:' . $alignment . ';min-height:' . (float) $height . 'rem;}';
	}
	if ( 'none' !== $desktop_image || 'none' !== $mobile_image || $opacity > 0 ) {
		$css .= '.page-header{background-image:' . $desktop_background . ';background-repeat:no-repeat;background-position:center;background-size:cover;background-attachment:' . $attachment . ';}'
			. '@media(max-width:767.98px){.page-header{background-image:' . $mobile_background . ';}}';
	}
	if ( $text_color ) {
		$css .= '.page-header{color:' . $text_color . ';}';
	}
	if ( $shadow_color ) {
		$css .= '.page-header-title{text-shadow:0 1px 3px ' . $shadow_color . ';}';
	}

	if ( '' !== $css ) {
		wp_add_inline_style( 'lightning-theme-style', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'lightning_child_add_page_header_css', 20 );

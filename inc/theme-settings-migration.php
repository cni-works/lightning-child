<?php
/**
 * One-time, non-destructive migration from the legacy Lightning Child theme.
 *
 * @package CniLightningChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Legacy stylesheet directory name. */
const CNI_LIGHTNING_CHILD_LEGACY_STYLESHEET = 'lightning-child';

/** Current stylesheet directory name. */
const CNI_LIGHTNING_CHILD_STYLESHEET = 'cni-lightning-child';

/**
 * Stores completion for migration schema version 1.
 *
 * This is intentionally independent of theme mods, so changing a Customizer
 * setting never re-runs a completed migration.
 */
const CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_OPTION = 'cni_lightning_child_settings_migration_v1';

/** Displays the result of the migration once after the theme switch. */
const CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_NOTICE = 'cni_lightning_child_settings_migration_v1_notice';

/**
 * Copies legacy theme mods only when CNI Lightning Child is first activated.
 *
 * Existing values belonging to the new theme are always retained. The legacy
 * option and its Custom CSS post are never edited or deleted.
 *
 * @return void
 */
function cni_lightning_child_migrate_legacy_theme_settings() {
	if ( CNI_LIGHTNING_CHILD_STYLESHEET !== get_stylesheet() ) {
		return;
	}

	if ( false !== get_option( CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_OPTION, false ) ) {
		return;
	}

	$legacy_option_name = 'theme_mods_' . CNI_LIGHTNING_CHILD_LEGACY_STYLESHEET;
	$target_option_name = 'theme_mods_' . CNI_LIGHTNING_CHILD_STYLESHEET;
	$legacy_mods        = get_option( $legacy_option_name, false );
	$target_mods        = get_option( $target_option_name, false );

	if ( ! is_array( $legacy_mods ) ) {
		cni_lightning_child_record_theme_settings_migration(
			'not_applicable',
			array(
				'copied_theme_mods' => 0,
				'custom_css'        => 'not_found',
			)
		);
		return;
	}

	$target_mods = is_array( $target_mods ) ? $target_mods : array();
	$copied      = 0;

	/*
	 * Custom CSS has its own post per stylesheet. Copying this pointer would
	 * make edits in the new theme mutate the old theme's CSS post.
	 */
	unset( $legacy_mods['custom_css_post_id'] );

	foreach ( $legacy_mods as $key => $value ) {
		if ( array_key_exists( $key, $target_mods ) ) {
			continue;
		}

		$target_mods[ $key ] = $value;
		++$copied;
	}

	if ( 0 < $copied || false === get_option( $target_option_name, false ) ) {
		update_option( $target_option_name, $target_mods );
	}

	$custom_css_result = cni_lightning_child_copy_legacy_custom_css();
	if ( 'failed' === $custom_css_result ) {
		set_transient(
			CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_NOTICE,
			array(
				'status' => 'failed',
			),
			DAY_IN_SECONDS
		);
		return;
	}

	cni_lightning_child_record_theme_settings_migration(
		'completed',
		array(
			'copied_theme_mods' => $copied,
			'custom_css'        => $custom_css_result,
		)
	);
}
add_action( 'after_switch_theme', 'cni_lightning_child_migrate_legacy_theme_settings', 20, 0 );

/**
 * Copies Customizer Additional CSS into a new, stylesheet-specific post.
 *
 * @return string copied, existing, or not_found. A failed save returns failed.
 */
function cni_lightning_child_copy_legacy_custom_css() {
	$new_css_post = wp_get_custom_css_post( CNI_LIGHTNING_CHILD_STYLESHEET );
	if ( $new_css_post instanceof WP_Post ) {
		return 'existing';
	}

	$legacy_css = wp_get_custom_css( CNI_LIGHTNING_CHILD_LEGACY_STYLESHEET );
	if ( '' === trim( $legacy_css ) ) {
		return 'not_found';
	}

	$result = wp_update_custom_css_post(
		$legacy_css,
		array(
			'stylesheet' => CNI_LIGHTNING_CHILD_STYLESHEET,
		)
	);

	return is_wp_error( $result ) ? 'failed' : 'copied';
}

/**
 * Records a completed migration and makes its one-time administrator notice.
 *
 * @param string               $status  Completion state.
 * @param array<string, mixed> $details Migration result details.
 * @return void
 */
function cni_lightning_child_record_theme_settings_migration( $status, $details ) {
	$record = array(
		'version'    => 1,
		'status'     => $status,
		'completed'  => time(),
		'legacy'     => CNI_LIGHTNING_CHILD_LEGACY_STYLESHEET,
		'stylesheet' => CNI_LIGHTNING_CHILD_STYLESHEET,
		'details'    => $details,
	);

	update_option( CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_OPTION, $record );
	set_transient( CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_NOTICE, $record, DAY_IN_SECONDS );
}

/**
 * Shows the migration result to an administrator once.
 *
 * @return void
 */
function cni_lightning_child_theme_settings_migration_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$record = get_transient( CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_NOTICE );
	if ( ! is_array( $record ) || empty( $record['status'] ) ) {
		return;
	}

	delete_transient( CNI_LIGHTNING_CHILD_SETTINGS_MIGRATION_V1_NOTICE );

	if ( 'failed' === $record['status'] ) {
		$message = __( 'CNI Lightning Child: 旧テーマ設定はコピーしましたが、「追加CSS」の複製に失敗しました。旧テーマの設定は変更されていません。', 'cni-lightning-child' );
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		return;
	}

	if ( 'not_applicable' === $record['status'] ) {
		$message = __( 'CNI Lightning Child: 移行元の Lightning Child 設定が見つからなかったため、設定移行は行いませんでした。', 'cni-lightning-child' );
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		return;
	}

	$copied = isset( $record['details']['copied_theme_mods'] ) ? absint( $record['details']['copied_theme_mods'] ) : 0;
	$message = sprintf(
		/* translators: %d: Number of copied theme modification values. */
		__( 'CNI Lightning Child: 旧 Lightning Child の設定を安全に移行しました（コピー: %d件）。旧設定は保持されています。', 'cni-lightning-child' ),
		$copied
	);
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}
add_action( 'admin_notices', 'cni_lightning_child_theme_settings_migration_notice' );

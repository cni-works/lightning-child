( function ( wp, settings ) {
	'use strict';

	if ( ! wp || ! settings || ! wp.coreData || ! wp.blockEditor ) {
		return;
	}

	var editorApi = wp.editor || wp.editPost;
	if ( ! editorApi || ! editorApi.PluginDocumentSettingPanel ) {
		return;
	}

	var createElement = wp.element.createElement;
	var CheckboxControl = wp.components.CheckboxControl;
	var TextControl = wp.components.TextControl;
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PluginDocumentSettingPanel = editorApi.PluginDocumentSettingPanel;
	var registerPlugin = wp.plugins.registerPlugin;
	var useEntityProp = wp.coreData.useEntityProp;
	var useSelect = wp.data.useSelect;

	function ImageControl( props ) {
		var imageId = Number( props.value ) || 0;
		var media = useSelect( function ( select ) {
			return imageId ? select( 'core' ).getMedia( imageId ) : null;
		}, [ imageId ] );
		var previewUrl = '';

		if ( media ) {
			previewUrl = media.source_url || '';
			if ( media.media_details && media.media_details.sizes && media.media_details.sizes.thumbnail ) {
				previewUrl = media.media_details.sizes.thumbnail.source_url;
			}
		}

		return createElement(
			'div',
			{ className: 'lightning-child-page-header-image-control' },
			createElement( 'p', null, createElement( 'strong', null, props.label ) ),
			previewUrl ? createElement( 'img', {
				src: previewUrl,
				alt: '',
				style: { display: 'block', width: '100%', height: 'auto', marginBottom: '8px' },
			} ) : null,
			createElement(
				MediaUploadCheck,
				null,
				createElement( MediaUpload, {
					onSelect: function ( attachment ) {
						props.onChange( attachment && attachment.id ? Number( attachment.id ) : 0 );
					},
					allowedTypes: [ 'image' ],
					value: imageId,
					render: function ( uploadProps ) {
						return createElement( Button, {
							variant: 'secondary',
							onClick: uploadProps.open,
							disabled: props.disabled,
						}, imageId ? settings.changeImage : settings.selectImage );
					},
				} )
			),
			imageId ? createElement( Button, {
				variant: 'tertiary',
				isDestructive: true,
				onClick: function () { props.onChange( 0 ); },
				disabled: props.disabled,
			}, settings.removeImage ) : null
		);
	}

	function PageHeaderPanel() {
		var result = useEntityProp( 'postType', settings.postType, 'meta' );
		var meta = result[ 0 ] || {};
		var setMeta = result[ 1 ];
		var enabled = Boolean( meta._lightning_child_page_header_custom_enabled );
		var overlayEnabled = Boolean( meta._lightning_child_page_header_overlay_custom_enabled );
		var overlayOpacity = Number( meta._lightning_child_page_header_overlay_opacity );
		if ( ! Number.isFinite( overlayOpacity ) ) {
			overlayOpacity = 0.4;
		}

		function updateMeta( metaKey, value ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ metaKey ] = value;
			setMeta( nextMeta );
		}

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'lightning-child-page-header-individual',
				title: settings.panelTitle,
				className: 'lightning-child-page-header-individual',
			},
			createElement( CheckboxControl, {
				label: settings.enabledLabel,
				help: settings.enabledHelp,
				checked: enabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_custom_enabled', Boolean( value ) );
				},
			} ),
			createElement( TextControl, {
				label: settings.catchphraseLabel,
				value: meta._lightning_child_page_header_catchphrase || '',
				disabled: ! enabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_catchphrase', value );
				},
			} ),
			createElement( TextControl, {
				label: settings.subtextLabel,
				value: meta._lightning_child_page_header_subtext || '',
				disabled: ! enabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_subtext', value );
				},
			} ),
			createElement( ImageControl, {
				label: settings.desktopImage,
				value: meta._lightning_child_page_header_image_id || 0,
				disabled: ! enabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_image_id', Number( value ) || 0 );
				},
			} ),
			createElement( ImageControl, {
				label: settings.mobileImage,
				value: meta._lightning_child_page_header_mobile_image_id || 0,
				disabled: ! enabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_mobile_image_id', Number( value ) || 0 );
				},
			} ),
			createElement( CheckboxControl, {
				label: settings.overlayEnabled,
				help: settings.overlayHelp,
				checked: overlayEnabled,
				disabled: ! enabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_overlay_custom_enabled', Boolean( value ) );
				},
			} ),
			createElement( TextControl, {
				label: settings.overlayColor,
				type: 'color',
				value: meta._lightning_child_page_header_overlay_color || '#000000',
				disabled: ! enabled || ! overlayEnabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_overlay_color', value );
				},
			} ),
			createElement( RangeControl, {
				label: settings.overlayOpacity,
				value: overlayOpacity,
				min: 0,
				max: 1,
				step: 0.1,
				disabled: ! enabled || ! overlayEnabled,
				onChange: function ( value ) {
					updateMeta( '_lightning_child_page_header_overlay_opacity', Number( value ) || 0 );
				},
			} )
		);
	}

	registerPlugin( 'lightning-child-page-header-individual', {
		render: PageHeaderPanel,
		icon: 'format-image',
	} );
}( window.wp, window.lightningChildPageHeader ) );

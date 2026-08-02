( function ( wp, settings ) {
	'use strict';

	if ( ! wp || ! settings || ! wp.coreData ) {
		return;
	}

	var editorApi = wp.editor || wp.editPost;
	if ( ! editorApi || ! editorApi.PluginDocumentSettingPanel ) {
		return;
	}

	var createElement = wp.element.createElement;
	var CheckboxControl = wp.components.CheckboxControl;
	var PluginDocumentSettingPanel = editorApi.PluginDocumentSettingPanel;
	var registerPlugin = wp.plugins.registerPlugin;
	var useEntityProp = wp.coreData.useEntityProp;

	function PageVisibilityPanel() {
		var result = useEntityProp( 'postType', 'page', 'meta' );
		var meta = result[ 0 ] || {};
		var setMeta = result[ 1 ];

		function updateMeta( metaKey, value ) {
			var nextMeta = Object.assign( {}, meta );
			nextMeta[ metaKey ] = Boolean( value );
			setMeta( nextMeta );
		}

		var controls = Object.keys( settings.fields ).map( function ( metaKey ) {
			return createElement( CheckboxControl, {
				key: metaKey,
				label: settings.fields[ metaKey ],
				checked: Boolean( meta[ metaKey ] ),
				onChange: function ( value ) {
					updateMeta( metaKey, value );
				},
			} );
		} );

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'lightning-child-page-visibility',
				title: settings.panelTitle,
				className: 'lightning-child-page-visibility',
			},
			controls
		);
	}

	registerPlugin( 'lightning-child-page-visibility', {
		render: PageVisibilityPanel,
		icon: 'visibility',
	} );
}( window.wp, window.lightningChildPageVisibility ) );

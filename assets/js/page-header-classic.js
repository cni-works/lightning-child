( function ( $ ) {
	'use strict';

	$( function () {
		$( '[data-lightning-child-image-field]' ).each( function () {
			var field = $( this );
			var input = field.find( '[data-lightning-child-image-input]' );
			var preview = field.find( '[data-lightning-child-image-preview]' );
			var removeButton = field.find( '[data-lightning-child-image-remove]' );

			field.on( 'click', '[data-lightning-child-image-select]', function () {
				var frame = wp.media( {
					title: 'ページヘッダー画像を選択',
					library: { type: 'image' },
					multiple: false,
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var imageUrl = attachment.url;
					if ( attachment.sizes && attachment.sizes.thumbnail ) {
						imageUrl = attachment.sizes.thumbnail.url;
					}

					input.val( attachment.id );
					preview.empty().append( $( '<img>', { src: imageUrl, alt: '', style: 'max-width:100%;height:auto;' } ) );
					removeButton.prop( 'hidden', false );
				} );

				frame.open();
			} );

			field.on( 'click', '[data-lightning-child-image-remove]', function () {
				input.val( 0 );
				preview.empty();
				removeButton.prop( 'hidden', true );
			} );
		} );
	} );
}( jQuery ) );

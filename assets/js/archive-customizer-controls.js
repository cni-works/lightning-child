( function( customize ) {
	'use strict';

	customize.bind( 'ready', function() {
		const groups = window.lightningChildArchiveControls || [];

		groups.forEach( function( group ) {
			const displaySetting = customize( group.displaySetting );
			if ( ! displaySetting ) {
				return;
			}

			const updateVisibility = function( value ) {
				( group.cardControls || [] ).forEach( function( controlId ) {
					const control = customize.control( controlId );
					if ( control ) {
						control.active.set( value === 'card' );
					}
				} );

				( group.customControls || [] ).forEach( function( controlId ) {
					const control = customize.control( controlId );
					if ( control ) {
						control.active.set( value !== 'standard' );
					}
				} );
			};

			displaySetting.bind( updateVisibility );
			updateVisibility( displaySetting.get() );
		} );
	} );
} )( wp.customize );

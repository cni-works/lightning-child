( function () {
	'use strict';

	function initializeFloatingContacts() {
		var contacts = document.querySelectorAll( '.lightning-child-floating-contact[data-display-mode="scroll"]' );

		Array.prototype.forEach.call( contacts, function ( contact ) {
			var threshold = parseInt( contact.getAttribute( 'data-scroll-threshold' ), 10 );
			var ticking = false;
			contact.classList.add( 'is-scroll-controlled' );

			if ( Number.isNaN( threshold ) ) {
				threshold = 100;
			}

			function updateVisibility() {
				contact.classList.toggle( 'is-visible', window.scrollY >= threshold );
				ticking = false;
			}

			function requestUpdate() {
				if ( ! ticking ) {
					window.requestAnimationFrame( updateVisibility );
					ticking = true;
				}
			}

			updateVisibility();
			window.addEventListener( 'scroll', requestUpdate, { passive: true } );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initializeFloatingContacts );
	} else {
		initializeFloatingContacts();
	}
}() );

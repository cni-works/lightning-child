( function () {
	'use strict';

	window.addEventListener( 'DOMContentLoaded', function () {
		const logo = document.querySelector( '.site-header-logo img' );
		if ( ! logo || typeof lightningChildHeaderLogos === 'undefined' ) {
			return;
		}

		const defaultLogo = logo.getAttribute( 'src' );
		if ( ! defaultLogo ) {
			return;
		}
		const defaultSrcset = logo.getAttribute( 'srcset' );
		const defaultSizes = logo.getAttribute( 'sizes' );

		const restoreResponsiveAttributes = function () {
			if ( defaultSrcset ) {
				logo.setAttribute( 'srcset', defaultSrcset );
			} else {
				logo.removeAttribute( 'srcset' );
			}

			if ( defaultSizes ) {
				logo.setAttribute( 'sizes', defaultSizes );
			} else {
				logo.removeAttribute( 'sizes' );
			}
		};

		const updateLogo = function () {
			let nextLogo = defaultLogo;

			if ( document.body.classList.contains( 'header_scrolled' ) && lightningChildHeaderLogos.scrolled ) {
				nextLogo = lightningChildHeaderLogos.scrolled;
			} else if (
				document.body.classList.contains( 'lightning-child-transparent-header' )
				&& lightningChildHeaderLogos.transparent
			) {
				nextLogo = lightningChildHeaderLogos.transparent;
			}

			if ( logo.getAttribute( 'src' ) !== nextLogo ) {
				logo.setAttribute( 'src', nextLogo );
			}

			if ( defaultLogo === nextLogo ) {
				restoreResponsiveAttributes();
			} else {
				logo.removeAttribute( 'srcset' );
				logo.removeAttribute( 'sizes' );
			}
		};

		new MutationObserver( updateLogo ).observe( document.body, {
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		updateLogo();
	} );
}() );

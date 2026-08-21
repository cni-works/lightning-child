( function() {
	'use strict';

	window.addEventListener( 'DOMContentLoaded', function() {
		// Lightning inserts its accordion controls on DOMContentLoaded. Run after it.
		window.setTimeout( function() {
			const parents = document.querySelectorAll(
				'.global-nav-list > .lightning-child-mega-menu-parent'
			);
			const labels = window.lightningChildMegaMenuL10n || {
				openSubmenu: 'Open submenu',
				closeSubmenu: 'Close submenu',
			};

			parents.forEach( function( parent, parentIndex ) {
				const panel = parent.querySelector( ':scope > .sub-menu' );
				const childMenus = [];
				let closeTimer = 0;

				if ( ! panel ) {
					return;
				}

				const setChildMenuState = function( entry, isOpen ) {
					entry.item.classList.toggle( 'is-lightning-child-submenu-open', isOpen );
					entry.button.classList.toggle( 'is-open', isOpen );
					entry.button.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
					entry.button.setAttribute( 'aria-label', isOpen ? labels.closeSubmenu : labels.openSubmenu );
					entry.submenu.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
				};

				const closeChildMenus = function( exceptEntry ) {
					childMenus.forEach( function( entry ) {
						if ( entry !== exceptEntry ) {
							setChildMenuState( entry, false );
						}
					} );
				};

				panel.querySelectorAll( ':scope > .lightning-child-mega-menu-item-has-children' ).forEach(
					function( item, itemIndex ) {
						const submenu = item.querySelector( ':scope > .sub-menu' );
						if ( ! submenu ) {
							return;
						}

						const lightningButton = submenu.previousElementSibling;
						if ( lightningButton && lightningButton.classList.contains( 'acc-btn' ) ) {
							lightningButton.remove();
						}

						submenu.classList.remove( 'acc-child-open', 'acc-child-close' );
						item.classList.remove( 'acc-parent-open', 'acc-parent-close' );

						const submenuId = 'lightning-child-mega-submenu-' + parentIndex + '-' + itemIndex;
						const button = document.createElement( 'button' );
						button.type = 'button';
						button.className = 'lightning-child-mega-menu__submenu-toggle';
						button.setAttribute( 'aria-controls', submenuId );
						button.innerHTML = '<span aria-hidden="true"></span>';
						submenu.id = submenuId;
						item.insertBefore( button, submenu );

						const entry = {
							item: item,
							button: button,
							submenu: submenu,
						};
						childMenus.push( entry );
						setChildMenuState( entry, false );

						const positionButton = function() {
							const media = item.querySelector( ':scope > a .lightning-child-mega-menu__media' );
							const link = item.querySelector( ':scope > a' );
							const target = media || link;
							if ( ! target ) {
								return;
							}

							const itemRect = item.getBoundingClientRect();
							const targetRect = target.getBoundingClientRect();
							const inset = media ? 12 : 10;
							const top = media
								? targetRect.bottom - itemRect.top - button.offsetHeight - inset
								: targetRect.top - itemRect.top + ( targetRect.height - button.offsetHeight ) / 2;

							button.style.top = Math.max( inset, top ) + 'px';
							button.style.right = inset + 'px';
						};

						button.addEventListener( 'click', function( event ) {
							event.preventDefault();
							event.stopPropagation();
							const willOpen = button.getAttribute( 'aria-expanded' ) !== 'true';
							closeChildMenus( entry );
							setChildMenuState( entry, willOpen );
						} );

						positionButton();
						window.addEventListener( 'resize', positionButton );
						if ( 'ResizeObserver' in window ) {
							const observer = new ResizeObserver( positionButton );
							observer.observe( item );
						}
					}
				);

				const cancelClose = function() {
					window.clearTimeout( closeTimer );
					closeTimer = 0;
				};

				const openMenu = function() {
					cancelClose();
					parents.forEach( function( otherParent ) {
						if ( otherParent !== parent ) {
							otherParent.classList.remove( 'is-mega-menu-open' );
						}
					} );
					parent.classList.add( 'is-mega-menu-open' );
				};

				const scheduleClose = function() {
					cancelClose();
					closeTimer = window.setTimeout( function() {
						if ( ! parent.matches( ':hover' ) && ! parent.contains( document.activeElement ) ) {
							parent.classList.remove( 'is-mega-menu-open' );
							closeChildMenus();
						}
					}, 320 );
				};

				parent.addEventListener( 'pointerenter', openMenu );
				parent.addEventListener( 'pointerleave', scheduleClose );
				panel.addEventListener( 'pointerenter', openMenu );
				panel.addEventListener( 'pointerleave', scheduleClose );
				parent.addEventListener( 'focusin', openMenu );
				parent.addEventListener( 'focusout', scheduleClose );
				parent.addEventListener( 'keydown', function( event ) {
					if ( event.key !== 'Escape' ) {
						return;
					}

					const openChild = childMenus.find( function( entry ) {
						return entry.button.getAttribute( 'aria-expanded' ) === 'true';
					} );
					if ( openChild ) {
						setChildMenuState( openChild, false );
						openChild.button.focus();
						return;
					}

					cancelClose();
					parent.classList.remove( 'is-mega-menu-open' );
					const link = parent.querySelector( ':scope > a' );
					if ( link ) {
						link.focus();
					}
				} );
			} );
		}, 0 );
	} );
} )();

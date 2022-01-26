( function( $ ) {
	'use strict';

	$( document ).ready( function() {
		$( window ).on( 'resize', function() {
			// Reposition tooltips after resizing
			setTimeout( fixImmTooltips, 550 );
		} );

		$( window ).add( '.ld-focus-sidebar-wrapper' ).on( 'scroll', function( event ) {
			event.stopImmediatePropagation();
			// Reposition tooltips after scrolling
			setTimeout( fixImmTooltips, 550 );
		} );

		function fixImmTooltips() {
			const immTooltips = $( '.ld-table-list-item[data-ld-tooltip]' );
			immTooltips.each( function() {
				const anchor = $( this );
				const relId = anchor.attr( 'data-ld-tooltip-id' );
				const tooltip = $( '#ld-tooltip-' + relId );
				const newLeft = anchor.offset().left;
				if ( tooltip.length === 0 || tooltip.css( 'left' ) === '0px' ) {
					setTimeout( fixImmTooltips, 550 );
					return false; // waiting LD scripts
				}
				tooltip.css( 'left', newLeft );
				tooltip.css( 'top', anchor.offset().top + 15 );
			} );
		}
		fixImmTooltips();

		$( '.imm-ls-resource' ).each( function() {
			if ( $( this ).find( '.imm-ls-resource-status-content' ).length ) {
				let tallest = 0;

				$( this ).find( '.imm-ls-resource-status-content' ).each( function() {
					if ( $( this ).height() > tallest ) {
						tallest = $( this ).height();
					}
				} );
				$( this ).find( '.imm-ls-resource-status-content' ).height( tallest );
			}
		} );
	} );
}( jQuery ) );

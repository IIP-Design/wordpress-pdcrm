jQuery( document ).ready( function( $ ) {

	$( '#chief-sfc-object' ).change( function() {

		var select = $( this );

		// let's start
		select.next( '.spinner' ).addClass( 'is-active' );

		var row = select.closest( 'tr' );

		// remove every following row
		row.nextAll().remove();

		var data = {
			action: 'chief_sfc_object',
			value:  select.val(),
			form:   select.data( 'form' ),
			source: select.data( 'source' )
		};

		// send new dropdown value back to PHP
		$.post( ajaxurl, data, function( response ) { // ajaxurl already defined by WP

			// output response below the select
			if ( response )
				row.after( response );

			// all done
			select.next( '.spinner' ).removeClass( 'is-active' );

		} );

	} );

} );
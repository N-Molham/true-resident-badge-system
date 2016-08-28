/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, doc, undefined ) {
	$( function () {
		var $badges = $( '#badgeos-achievements-container' );
		if ( $badges.length < 1 ) {
			// stop if badges list not found
			return;
		}

		// popover init
		$( '.badgeos-achievements-list-item' ).livequery( function ( index, element ) {
			$( element ).webuiPopover();
		} );

		var body_classes = doc.body.className;
		if ( body_classes.indexOf( 'single' ) < 0 || body_classes.indexOf( 'single-job_listing' ) < 0 ) {
			// skip non-single page for a listing
			return;
		}

		var ids_match = body_classes.match( /postid-\d+/i );
		if ( null === ids_match ) {
			// skip un-related pages
			return;
		}

		// loaded listing ID in the current page
		var post_id = parseInt( ids_match[ 0 ].replace( 'postid-', '' ) );
		if ( isNaN( post_id ) ) {
			// skip invalid- post id
			return;
		}

		$.ajaxSetup( {
			data: {
				trbs_listing_id: post_id
			}
		} );
	} );
})( window, jQuery, document );
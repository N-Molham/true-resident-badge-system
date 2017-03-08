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

		// badges with challenges checklist
		(function () {
			// badges click
			$badges.on( 'click', '.badgeos-achievements-challenges-item', function ( e ) {
				var $this      = $( e.currentTarget ),
				    steps_data = $this.data( 'steps-data' );

				console.log( steps_data );
			} );
		})();

		// badges filter
		(function () {
			var $badges_filter = $( '#achievements_list_filter' );
			if ( $badges_filter.length ) {
				for ( option_value in trbs_badges.filter_labels ) {
					if ( !trbs_badges.filter_labels.hasOwnProperty( option_value ) ) {
						// skip non-properties index
						continue;
					}

					$badges_filter.find( 'option[value="' + option_value + '"]' ).text( trbs_badges.filter_labels[ option_value ] );
				}
			}
		})();

		// badges popover init
		(function () {
			$( '.badgeos-achievements-list-item' ).livequery( function ( index, element ) {
				$( element ).webuiPopover();
			} );
		})();

		// badges for current open listing
		(function () {
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
		})();
	} );
})( window, jQuery, document );
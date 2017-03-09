/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, doc, undefined ) {
	$( function () {
		var $container = $( '#badgeos-achievements-container' );
		if ( $container.length < 1 ) {
			// stop if badges list not found
			return;
		}

		// vars
		var listing_id   = '',
		    body_classes = doc.body.className,
		    is_single    = body_classes.indexOf( 'single-job_listing' ) > -1 && body_classes.indexOf( 'single' ) > -1;

		// badges with challenges checklist
		(function () {
			if ( false === is_single ) {
				// skip non-single page for a listing
				return;
			}

			// compile checklist template
			var render_checklist  = doT.template( $( '#trbs-checklist-template' ).html() ),
			    $badge_challenges = $( '#trbs-badges-challenges' ),
			    challenges        = [];

			// badges click
			$container.on( 'click', '.badgeos-achievements-challenges-item', function ( e ) {
				var badge      = $( e.currentTarget ),
				    steps_data = badge.data( 'steps-data' );

				// reset
				challenges = [];

				for ( var step_id in steps_data ) {
					if ( !steps_data.hasOwnProperty( step_id ) ) {
						// skip non properties
						continue;
					}

					// current step
					var badge_step = steps_data[ step_id ];

					// pass on the ID
					badge_step.id = step_id;

					if ( !( 'challenges_checklist_listing_id' in badge_step ) || badge_step.challenges_checklist_listing_id.toString() !== listing_id.toString() ) {
						// skip if these challenges aren't for the current listing
						continue;
					}

					if ( !( 'challenges_checklist' in badge_step ) || $.isEmptyObject( badge_step.challenges_checklist ) ) {
						//
						continue;
					}

					// render badge's step checklist
					challenges.push( render_checklist( badge_step ) );
				}

				// output checklist(s)
				$badge_challenges.html( challenges.join( '' ) );
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
			if ( false === is_single ) {
				// skip non-single page for a listing
				return;
			}

			var ids_match = body_classes.match( /postid-\d+/i );
			if ( null === ids_match ) {
				// skip un-related pages
				return;
			}

			// loaded listing ID in the current page
			listing_id = parseInt( ids_match[ 0 ].replace( 'postid-', '' ) );
			if ( isNaN( listing_id ) ) {
				// skip invalid- post id
				return;
			}

			$.ajaxSetup( {
				data: {
					trbs_listing_id: listing_id
				}
			} );
		})();
	} );
})( window, jQuery, document );
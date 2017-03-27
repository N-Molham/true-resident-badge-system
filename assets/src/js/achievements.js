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
		var $body        = $( doc.body ),
		    listing_id   = '',
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
			    challenges        = [],
			    requests_count    = 0;

			// when ajax login is successful
			$body.on( 'tr-login-register-ajax-success', function () {
				// force reload the page
				w.location.reload( true );
			} );

			// challenges checklist item checked/unchecked
			$badge_challenges.on( 'change tr-change', '.trbs-checklist-item input[type=checkbox]', function ( e ) {
				var $this      = $( e.currentTarget ),
				    point_data = $this.data();

				// user needs to login first
				if ( false === is_user_logged_in() ) {
					$( '#secondary-nav-menu' ).find( '.overlay-login' ).trigger( 'tr-click' );

					// toggle back
					$this.prop( 'checked', !$this.prop( 'checked' ) );

					return true;
				}

				// new request
				requests_count++;

				$.post( listifySettings.ajaxurl, $.extend( {}, point_data, {
					point  : e.currentTarget.value,
					checked: e.currentTarget.checked,
					nonce  : trbs_badges.checklist_nonce,
					action : 'challenges_checklist_update'
				} ), (function ( $input, badge_id ) {
					return function ( response ) {
						if ( false === response.success ) {
							// toggle back
							$input.prop( 'checked', !$input.prop( 'checked' ) );
						} else {
							// validate earning only if it's the last ajax request
							if ( requests_count > 1 ) {
								return;
							}

							// query all checklist inputs
							var $checklist_points = $input.closest( '.trbs-checklist' ).find( '.trbs-checklist-item input[type=checkbox]' ),
							    // filter only checked ones
							    $checked_points   = $checklist_points.filter( ':checked' );

							// not all points are checked
							if ( $checklist_points.length !== $checked_points.length ) {
								return;
							}

							// toggle earn status
							$( '#badgeos-achievements-list-item-' + badge_id ).removeClass( 'user-has-not-earned' ).addClass( 'user-has-earned' );
						}
					};
				})( $this, point_data.badge ), 'json' ).always( function () {
					// request done
					requests_count--;
					if ( requests_count < 0 ) {
						requests_count = 0;
					}
				} );
			} );

			// badges click
			$container.on( 'click tr-click', '.badgeos-achievements-challenges-item', function ( e ) {
				var $badge     = $( e.currentTarget ),
				    steps_data = $badge.data( 'steps-data' ),
				    badge_id   = $badge.data( 'id' );

				// reset
				challenges = [];

				for ( var step_id in steps_data ) {
					if ( !steps_data.hasOwnProperty( step_id ) ) {
						// skip non properties
						continue;
					}

					// current step
					var badge_step = $.extend( {}, steps_data[ step_id ], {
						step_id : step_id,
						badge_id: badge_id
					} );

					if ( !( 'challenges_checklist_listing_id' in badge_step ) || badge_step.challenges_checklist_listing_id.toString() !== listing_id.toString() ) {
						// skip if these challenges aren't for the current listing
						continue;
					}

					if ( !( 'challenges_checklist' in badge_step ) || $.isEmptyObject( badge_step.challenges_checklist ) ) {
						// step has not checklist!
						continue;
					}

					// render badge's step checklist
					challenges.push( render_checklist( badge_step ) );
				}

				if ( challenges.length < 1 ) {
					// no challenges found!
					return true;
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

			$( doc ).ajaxSuccess( function ( e, response, options ) {
				if ( 'get-achievements' === get_query_arg( 'action', options.url ) && 'badges' === get_query_arg( 'type', options.url ) ) {
					// trigger first badge click
					setTimeout( function () {
						$container.find( '.badgeos-achievements-challenges-item:first' ).trigger( 'tr-click' );
					}, 100 );
				}
			} );
		})();

		/**
		 * Check if there is logged in user or not
		 *
		 * @return {boolean}
		 */
		function is_user_logged_in() {
			return Boolean( listify_child_overlays && 'is_user_logged_in' in listify_child_overlays ? listify_child_overlays.is_user_logged_in : trbs_badges.is_logged_in );
		}

		/**
		 * Get Query string in url
		 *
		 * @param {String} name
		 * @param {String} url
		 * @return {String}
		 */
		function get_query_arg( name, url ) {
			if ( !url ) {
				url = window.location.href;
			}
			name        = name.replace( /[\[\]]/g, "\\$&" );
			var regex   = new RegExp( "[?&]" + name + "(=([^&#]*)|&|#|$)" ),
			    results = regex.exec( url );
			if ( !results ) return null;
			if ( !results[ 2 ] ) return '';
			return decodeURIComponent( results[ 2 ].replace( /\+/g, " " ) );
		}
	} );
})( window, jQuery, document );
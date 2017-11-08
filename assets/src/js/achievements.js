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
			    cache             = {},
			    last_request      = null;

			// when ajax login is successful
			$body.on( 'tr-login-register-ajax-success', function () {
				// force reload the page
				w.location.reload( true );
			} );

			// challenges checklist item checked/unchecked
			$badge_challenges.on( 'change tr-change', '.trbs-checklist-item input[type=checkbox]', function ( e ) {
				var $this      = $( e.currentTarget ),
				    point_data = $this.data(),
				    badge_id   = point_data.badge;

				// user needs to login first
				if ( false === is_user_logged_in() ) {
					$( '#secondary-nav-menu' ).find( '.overlay-login' ).trigger( 'tr-click' );

					// toggle back
					$this.prop( 'checked', !$this.prop( 'checked' ) );

					return true;
				}

				if ( undefined === cache[ badge_id ] ) {
					// cache needed data
					cache[ badge_id ] = {
						'$badge'           : $( '#badgeos-achievements-list-item-' + badge_id ),
						'$checklist_points': $this.closest( '.trbs-checklist' ).find( '.trbs-checklist-item input[type=checkbox]' )
					};
				}

				// completion percentage check 
				cache[ badge_id ].complete_percentage = Math.round( ( cache[ badge_id ].$checklist_points.filter( ':checked' ).length / cache[ badge_id ].$checklist_points.length ) * 100 );
				cache[ badge_id ].$badge.attr( 'data-completed', cache[ badge_id ].complete_percentage );

				if ( last_request ) {
					last_request.abort();
				}

				last_request = $.post( listifySettings.ajaxurl, $.extend( {}, point_data, {
					point  : e.currentTarget.value,
					checked: e.currentTarget.checked,
					nonce  : trbs_badges.checklist_nonce,
					action : 'challenges_checklist_update'
				} ), (function ( $input, badge_cache ) {
					return function ( response ) {
						if ( false === response.success ) {
							// toggle back
							$input.prop( 'checked', !$input.prop( 'checked' ) );
						} else {
							// update with the new complete percentage
							badge_cache.$badge
							.attr( 'data-completed', response.data.percentage )
							// update with the earning data
							.attr( 'data-last-earning', JSON.stringify( response.data.last_earning ) );

							// not all points are checked
							if ( badge_cache.$checklist_points.length !== badge_cache.$checklist_points.filter( ':checked' ).length ) {
								return;
							}

							// toggle earn status
							badge_cache.$badge.removeClass( 'user-has-not-earned' ).addClass( 'user-has-earned badgeos-glow' );

							setTimeout( (function ( $badge_link ) {
								return function () {
									// remove the glow
									$badge_link.removeClass( 'badgeos-glow' );
								};
							})( badge_cache.$badge ), 2000 );

							// reset checked points
							badge_cache.$checklist_points.prop( 'checked', false );
						}
					};
				})( $this, cache[ badge_id ] ), 'json' );
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

				// initialize popups
				$badge_challenges.find( '.trbs-suggestion-button' ).magnificPopup( {
					tClose         : listifySettings.l10n.magnific.tClose,
					tLoading       : listifySettings.l10n.magnific.tLoading,
					fixedContentPos: false,
					fixedBgPos     : true,
					overflowY      : 'scroll',
					type           : 'ajax'
				} );
			} );
		})();

		// badges filter
		(function () {
			var $container = $( '#badgeos-achievements-filter' );
			if ( $container.length ) {
				// new type filter starting tag
				var new_filters = [ '<ul class="badgeos-badge-types-filter">' ],
				    filter      = {};

				for ( var i = 0, len = trbs_badges.badge_filters.length; i < len; i++ ) {
					filter = trbs_badges.badge_filters[ i ];

					new_filters.push( '<li' + ( 0 === i ? ' class="current"' : '' ) + '><a href="#' + filter.value + '">' + filter.filter_name + '</a></li>' );
				}

				// filters list closing tag
				new_filters.push( '</ul>' );

				// hidden input
				new_filters.push( '<input type="hidden" name="achievements_list_filter" id="achievements_list_filter" value="all" />' );

				// replace current badge filter with the new one
				$container.html( new_filters.join( '' ) );

				var $filter_input = $( '#achievements_list_filter' );

				// badge type filter click
				$container.on( 'click', '.badgeos-badge-types-filter a', function ( e ) {
					e.preventDefault();

					// clicked link
					var $this    = $( e.currentTarget ),
					    $this_el = $this.closest( 'li' );

					if ( false === $this_el.hasClass( 'current' ) ) {
						// set selected badge type value
						$filter_input.val( $this.attr( 'href' ).replace( '#', '' ) )
						// trigger ajax update
						.trigger( 'change' );

						// set current badge type CSS class
						$container.find( 'li.current' ).removeClass( 'current' );
						$this_el.addClass( 'current' );
					}
				} );
			}
		})();

		// badges popover init
		(function () {
			$( '.badgeos-achievements-list-item' ).livequery( function ( index, element ) {
				$( element ).webuiPopover( {
					onShow: function ( $popover ) {
						// related badge
						var $badge    = $( '#badgeos-achievements-container' ).find( '.badgeos-achievements-list-item[data-target="' + $popover.attr( 'id' ) + '"]' ),
						    completed = Math.abs( $badge.attr( 'data-completed' ) ),
						    $earning  = $popover.find( '.badgeos-earning' );

						// 100% max
						completed = completed > 100 ? 100 : completed;

						// bar width
						$popover.find( '.badgeos-percentage-bar' ).css( 'width', completed + '%' );

						// bar text
						$popover.find( '.badgeos-percentage-number' ).html( completed + '&percnt;' );

						if ( $earning.length ) {
							// update last earning
							var last_earning = JSON.parse( $badge.attr( 'data-last-earning' ) );

							// earning count
							$earning.find( '.badgeos-earning-count' ).text( last_earning.earn_count );

							// earning date
							$earning.find( '.badgeos-earning-date' ).text( last_earning.date_earned_formatted );
						}
					}
				} );
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
				data      : {
					trbs_listing_id: listing_id
				},
				dataFilter: function ( data, type ) {
					if ( 'json' !== type ) {
						// return data with no change
						return data;
					}

					// parse raw data into json object
					var response = $.parseJSON( data );
					if ( response.data && response.data.badge_count < 1 ) {
						// no badges found, change empty badges message content
						response.data.message = trbs_badges.empty_message;
					}

					return JSON.stringify( response );
				}
			} );

			$( doc ).ajaxSuccess( function ( e, xhr, options, response ) {
				if ( 'get-achievements' === get_query_arg( 'action', options.url ) && 'badges' === get_query_arg( 'type', options.url ) ) {
					setTimeout( function () {
						// trigger first badge click
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
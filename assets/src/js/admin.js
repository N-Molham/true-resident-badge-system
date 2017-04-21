/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, undefined ) {
	$( function () {

		// POI badge checklist
		(function () {
			var $checklist_form = $( '#checklist-form' );

			if ( 0 === $checklist_form.length ) {
				// skip
				return false;
			}

			// repeatable init
			$checklist_form.find( '.checklist-repeatable' ).on( 'repeatable-completed', function ( e, $list ) {
				$list.sortable( {
					handle: '.sort-handle'
				} );
			} ).repeatable_item();
		})();

		// badge steps
		(function () {
			// vars
			var $steps_list              = $( '#steps_list' ),
			    step_triggers_selector   = '.select-trigger-type',
			    step_conditions_selector = '.true-resident-step-condition',
			    taxonomy_field_selector  = '.true-resident-tax-type',
			    autocomplete_cache       = {},
			    last_request             = null;

			// listing search
			$steps_list.on( 'keydown', 'input.true-resident-autocomplete', function ( e ) {
				var $this = $( e.currentTarget );

				if ( $this.hasClass( 'ui-autocomplete-input' ) ) {
					// skip if autocomplete already initialized
					return true;
				}

				// search bar autocomplete
				$this.autocomplete( {
					minLength: 2,
					focus    : function ( e ) {
						// prevent default behaviour
						e.preventDefault();
					},
					source   : (function ( $input ) {
						return function ( ui_request, ui_response ) {
							// vars
							var search_term  = ui_request.term.trim(),
							    empty_search = '' === search_term || search_term.length < 1;

							if ( last_request ) {
								// stop the last request
								last_request.abort();
							}

							if ( empty_search ) {
								// skip
								return;
							}

							if ( search_term in autocomplete_cache ) {
								// load from cache
								ui_response( autocomplete_cache[ search_term ] );
								return;
							}

							$input.addClass( 'ui-autocomplete-loading' );

							// AJAX request
							last_request = $.post( trbs_triggers.urls.get_listings, {
								search_keywords  : search_term,
								page             : 1,
								search_location  : '',
								per_page         : 30,
								orderby          : 'featured',
								order            : 'DESC',
								form_data        : '',
								trsc_autocomplete: true,
								show_pagination  : false
							}, function ( search_response ) {
								if ( search_response.success ) {
									var render_data = [];

									for ( var i in search_response.data ) {
										render_data.push( {
											label: search_response.data[ i ].text,
											value: search_response.data[ i ].id
										} )
									}

									// cache it for later
									autocomplete_cache[ search_term ] = render_data;
									ui_response( render_data );
								}
							} ).always( function () {
								$input.removeClass( 'ui-autocomplete-loading' );
							} );
						};
					})( $this )
				} );
			} );

			// when the trigger type changes
			$steps_list.on( 'change trbs-change', step_triggers_selector, function ( e ) {
				var $trigger = $( e.currentTarget );

				// look for the linked fields
				$trigger.siblings( step_conditions_selector ).hide().filter( '[data-toggle="' + $trigger.val() + '"]' ).show();
			} );

			$steps_list.on( 'change trbs-change', taxonomy_field_selector, function ( e ) {
				if ( e.currentTarget.value.length > 0 ) {
					// linked terms field
					var $this         = $( e.currentTarget ),
					    options       = [],
					    $loading      = $this.siblings( '.spinner' ),
					    $terms_field  = $this.siblings( '.true-resident-term' ),
					    selected_term = parseInt( $terms_field.data( 'value' ) );

					// disable the field and empty it
					$terms_field.empty().prop( 'disabled', true );
					$this.prop( 'disabled', true );
					$loading.addClass( 'is-active' );

					$.post( ajaxurl, {
						action  : 'trbs_get_taxonomy_terms',
						taxonomy: e.currentTarget.value
					}, function ( response ) {
						if ( response.success ) {
							for ( var term_id in response.data ) {
								if ( !response.data.hasOwnProperty( term_id ) ) {
									// skip invalid property
									continue;
								}

								// add the new option
								options.push( '<option value="' + term_id + '"' + ( selected_term === parseInt( term_id ) ? ' selected' : '' ) + '>' + response.data[ term_id ] + '</option>' );
							}

							// fill in the options
							$terms_field.html( options );
						} else {
							alert( response.data );
						}
					}, 'json' ).always( function () {
						// re-enable the field
						$terms_field.prop( 'disabled', false );
						$this.prop( 'disabled', false );
						$loading.removeClass( 'is-active' );
					} );
				}
			} );

			// trigger change on load
			$( [ step_triggers_selector, taxonomy_field_selector ].join( ', ' ) ).trigger( 'trbs-change' );

			// Inject our custom step details into the update step action
			$( document ).on( 'update_step_data', function ( e, step_data, $step ) {
				// get the give trigger type related conditions
				$step.find( step_conditions_selector + '[data-toggle="' + step_data.trigger_type + '"]' ).each( function ( index, element ) {
					// setup condition name as step data property with input value
					step_data[ element.getAttribute( 'name' ) ] = element.value;
				} );
			} );
		})();
	} );
})( window, jQuery );
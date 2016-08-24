/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, undefined ) {
	$( function () {
		// vars
		var $steps_list              = $( '#steps_list' ),
		    step_triggers_selector   = '.select-trigger-type',
		    step_conditions_selector = '.true-resident-step-condition',
		    taxonomy_field_selector  = '.true-resident-tax-type';

		// when the trigger type changes
		$steps_list.on( 'change trbs-change', step_triggers_selector, function ( e ) {
			var $trigger = $( e.currentTarget );

			// look for the linked fields
			$trigger.siblings( step_conditions_selector ).hide().filter( '[data-toggle="' + $trigger.val() + '"]' ).show();
		} );

		$steps_list.on( 'change trbs-change', taxonomy_field_selector, function ( e ) {
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
	} );
})( window, jQuery );
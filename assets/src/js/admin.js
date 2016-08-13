/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, undefined ) {
	$( function () {
		// vars
		var step_triggers_selector   = '.select-trigger-type',
		    step_conditions_selector = '.true-resident-step-condition';

		// when the trigger type changes
		$( '#steps_list' ).on( 'change trbs-change', step_triggers_selector, function ( e ) {
			var $trigger = $( e.currentTarget );

			// hide all available condition except for the linked one
			$trigger.siblings( step_conditions_selector ).hide().filter( '[data-toggle="' + $trigger.val() + '"]' ).show();
		} );

		// trigger change on load
		$( step_triggers_selector ).trigger( 'trbs-change' );

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
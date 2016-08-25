/**
 * Created by Nabeel on 2016-02-02.
 */
(function ( w, $, undefined ) {
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
	} );
})( window, jQuery );
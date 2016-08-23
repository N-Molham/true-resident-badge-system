<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Class Specific_Listing_Check_In_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class Specific_Listing_Check_In_Trigger implements True_Resident_Trigger_Interface
{
	/**
	 * Step meta key for listing ID
	 *
	 * @var string
	 */
	var $meta_key = '_trbs_listing_id';

	/**
	 * Target listing post type
	 *
	 * @var string
	 */
	var $listing_post_type = 'job_listing';

	/**
	 * Target listing ID field name
	 *
	 * @var string
	 */
	var $listing_id_field_name = 'check_in_listing_id';

	public function label()
	{
		return __( 'True Resident Specific Listing Check-in', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'true_resident_listing_new_check_in';
	}

	public function activity_trigger()
	{
		return 'true_resident_specific_listing_check_in';
	}

	public function activity_hook()
	{
		global $wpdb;

		// vars
		$blog_id      = get_current_blog_id();
		$user         = get_user_by( 'id', func_get_arg( 0 ) );
		$post_id      = func_get_arg( 1 );
		$this_trigger = $this->activity_trigger();

		// update count
		$trigger_count = badgeos_update_user_trigger_count( $user->ID, $this_trigger, $blog_id );

		// Mark the count in the log entry
		badgeos_post_log_entry( $post_id, $user->ID, $this_trigger, sprintf( __( '%1$s triggered %2$s (%3$dx)', TRBS_DOMAIN ), $user->display_name, $this_trigger, $trigger_count ) );

		// load achievements
		$achievements_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id as id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", $this->meta_key, $post_id ) );
		foreach ( $achievements_ids as $achievement_id )
		{
			// user reward if match
			badgeos_maybe_award_achievement_to_user( $achievement_id, $user->ID, $this_trigger, $blog_id );
		}
	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		global $wpdb;

		// If we're not dealing with a step, bail here
		if ( 'step' != get_post_type( $achievement_id ) )
		{
			return $return;
		}

		// get step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );
		if ( !isset( $requirements[ $this->listing_id_field_name ] ) )
		{
			// skip un-related type
			return $return;
		}

		// vars
		$table_name   = trbs_bookmarks()->table_name();
		$count_sql    = "SELECT COUNT(id) FROM {$table_name} WHERE user_id = %d AND post_id = %d";
		$count_params = [ $user_id, $requirements[ $this->listing_id_field_name ] ];

		// execute sql for the current count
		$check_in_count = absint( $wpdb->get_var( $wpdb->prepare( $count_sql, $count_params ) ) );
		if ( $check_in_count >= $requirements['count'] )
		{
			// target reached
			$return = true;
		}

		return $return;
	}

	public function get_data( $step_id, $trigger_type = '' )
	{
		if ( '' === $trigger_type || empty( $trigger_type ) )
		{
			// if step trigger type not passed
			$trigger_type = get_post_meta( $step_id, '_badgeos_trigger_type', true );
		}

		if ( $this->activity_trigger() !== $trigger_type )
		{
			// not the same trigger type
			return [ ];
		}

		return [
			$this->listing_id_field_name => absint( get_post_meta( $step_id, $this->meta_key, true ) ),
		];
	}

	public function save_data( $step_id, $step_data, $trigger_name = '' )
	{
		if ( $this->activity_trigger() !== $trigger_name || $trigger_name !== $step_data['trigger_type'] )
		{
			// skip non-related triggers
			return;
		}

		// save selected category
		update_post_meta( $step_id, $this->meta_key, absint( $step_data[ $this->listing_id_field_name ] ) );
	}

	public function user_interface( $step_id, $badge_id )
	{
		$listing_id = absint( get_post_meta( $step_id, $this->meta_key, true ) );
		if ( 0 === $listing_id )
		{
			// no value was set
			$listing_id = '';
		}

		// selected listing ID field
		printf( '<input type="text" size="6" name="%s" placeholder="%s" class="true-resident-autocomplete true-resident-step-condition" 
				data-toggle="%s" data-post-type="%s" data-return="id" value="%s" />',
			$this->listing_id_field_name,
			__( 'Listing ID', TRBS_DOMAIN ),
			$this->activity_trigger(),
			$this->listing_post_type,
			$listing_id
		);
	}
}
<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Class User_Register_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class User_Register_Trigger implements True_Resident_Trigger_Interface
{
	/**
	 * Step meta key for the selected user role
	 *
	 * @var string
	 */
	var $meta_key = '_trbs_user_role';

	/**
	 * Target user role field name
	 *
	 * @var string
	 */
	var $field_name = 'trbs_user_role';

	public function label()
	{
		return __( 'True Resident User Register', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'user_register';
	}

	public function activity_trigger()
	{
		return 'true_resident_user_register';
	}

	public function activity_hook()
	{
		global $wpdb;

		// vars
		$blog_id      = get_current_blog_id();
		$user         = get_user_by( 'id', func_get_arg( 0 ) );
		$this_trigger = $this->activity_trigger();

		// update count
		$trigger_count = badgeos_update_user_trigger_count( $user->ID, $this_trigger, $blog_id );

		// Mark the count in the log entry
		badgeos_post_log_entry( $user->ID, $user->ID, $this_trigger, sprintf( __( '%1$s triggered %2$s (%3$dx)', TRBS_DOMAIN ), $user->display_name, $this_trigger, $trigger_count ) );

		// load achievements
		$achievements = $wpdb->get_results( $wpdb->prepare( "SELECT post_id as id, meta_value as user_role FROM $wpdb->postmeta WHERE meta_key = %s", $this->meta_key ) );
		foreach ( $achievements as $achievement )
		{
			// achievement scope
			$do_award = 'any' === $achievement->user_role;
			if ( false === $do_award )
			{
				// check if the group is the one needed
				$do_award = in_array( $achievement->user_role, $user->roles );
			}

			if ( $do_award )
			{
				// give the award instantly because there are no additional conditions
				badgeos_award_achievement_to_user( $achievement->id, $user->ID, $this_trigger, $blog_id );
			}
		}
	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		if ( 'step' != get_post_type( $achievement_id ) )
		{
			// If we're not dealing with a step, bail here
			return $return;
		}

		if ( $this->activity_trigger() !== $this_trigger )
		{
			// skip un-related trigger
			return $return;
		}

		// step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );
		$return = 'any' === $requirements[ $this->field_name ];
		if ( false === $return )
		{
			// user info
			$user   = get_user_by( 'id', $user_id );
			$return = in_array( $requirements[ $this->field_name ], $user->roles );
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
			$this->field_name => get_post_meta( $step_id, $this->meta_key, true ),
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
		update_post_meta( $step_id, $this->meta_key, sanitize_key( $step_data[ $this->field_name ] ) );
	}

	public function user_interface( $step_id, $badge_id )
	{
		$settings = $this->get_data( $step_id, $this->activity_trigger() );

		// element start
		echo '<select name="', $this->field_name, '" class="true-resident-autocomplete true-resident-step-condition" data-toggle="', $this->activity_trigger(), '">';

		// option "Any"
		echo '<option value="any"', ( 'any' === $settings[ $this->field_name ] || '' === $settings[ $this->field_name ] ? ' selected' : '' ), '>',
		__( 'Any', TRBS_DOMAIN ),
		'</option>';

		// rest of available options
		wp_dropdown_roles( get_post_meta( $step_id, $this->meta_key, true ) );

		// element end
		echo '</select>';
	}
}
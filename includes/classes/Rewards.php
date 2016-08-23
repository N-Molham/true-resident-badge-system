<?php namespace True_Resident\Badge_System;

use True_Resident\Badge_System\Triggers\Listing_Category_Check_In_Trigger;
use True_Resident\Badge_System\Triggers\Listings_Reviews_Trigger;
use True_Resident\Badge_System\Triggers\Specific_Listing_Check_In_Trigger;
use True_Resident\Badge_System\Triggers\SpecificListing_Check_In_Trigger;

/**
 * BadgeOS rewards logic
 *
 * @package True_Resident\Badge_System
 */
class Rewards extends Component
{
	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// BadgeOS activity triggers action
		add_filter( 'badgeos_activity_triggers', [ &$this, 'badegos_register_new_triggers' ] );

		// BadgeOS step data requirements filter
		add_filter( 'badgeos_get_step_requirements', [ &$this, 'badgeos_step_data_requirements' ], 10, 2 );

		// WP Initialization
		add_action( 'init', [ &$this, 'badgeos_load_triggers' ] );
	}

	/**
	 * Load additional BadgeOS triggers hooks
	 *
	 * @return void
	 */
	public function badgeos_load_triggers()
	{
		$triggers = $this->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger )
		{
			// hook up trigger action
			$trigger_action = $trigger->trigger_action();
			if ( !is_array( $trigger_action ) )
			{
				// wrap single action in array
				$trigger_action = [ $trigger_action ];
			}

			foreach ( $trigger_action as $action_name )
			{
				add_action( $action_name, [ $trigger, 'activity_hook' ], 10, 20 );
			}

			// user deserves filter hook
			add_filter( 'user_deserves_achievement', [ $trigger, 'user_deserves_achievement_hook' ], 15, 6 );
		}
	}

	/**
	 * Update badgeos_get_step_requirements to include our custom requirements
	 *
	 * @param  array   $requirements The current step requirements
	 * @param  integer $step_id The given step's post ID
	 *
	 * @return array
	 */
	public function badgeos_step_data_requirements( $requirements, $step_id )
	{
		// vars
		$trigger_type = get_post_meta( $step_id, '_badgeos_trigger_type', true );
		$triggers     = $this->get_triggers();

		if ( isset( $triggers[ $trigger_type ] ) )
		{
			// get step extra data based on the trigger
			$requirements = array_merge( $requirements, call_user_func( [
				$triggers[ $trigger_type ],
				'get_data',
			], $step_id, $trigger_type ) );
		}

		return $requirements;
	}

	/**
	 * Register the new activity triggers for BadgeOS
	 *
	 * @param array $triggers
	 *
	 * @return array
	 */
	public function badegos_register_new_triggers( $triggers )
	{
		// list trigger with labels
		$triggers = array_merge( $triggers, array_map( function ( $trigger )
		{
			return $trigger->label();
		}, $this->get_triggers() ) );

		return $triggers;
	}

	/**
	 * List of new triggers
	 *
	 * @return array
	 */
	public function get_triggers()
	{
		// built-in triggers
		$listings_category_trigger = new Listing_Category_Check_In_Trigger();
		$specific_listing_trigger  = new Specific_Listing_Check_In_Trigger();
		$listing_review_trigger    = new Listings_Reviews_Trigger();

		/**
		 * Filters the list of built-in triggers in the add-on
		 *
		 * @param array $triggers
		 *
		 * @return array
		 */
		return apply_filters( 'trbs_rewards_activity_triggers', [
			$listings_category_trigger->activity_trigger() => &$listings_category_trigger,
			$specific_listing_trigger->activity_trigger()  => &$specific_listing_trigger,
			$listing_review_trigger->activity_trigger()    => &$listing_review_trigger,
		] );
	}
}

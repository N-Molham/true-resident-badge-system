<?php namespace True_Resident\Badge_System;

use True_Resident\Badge_System\Triggers\Listing_Category_Check_In_Trigger;

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
		add_filter( 'badgeos_activity_triggers', [ &$this, 'register_new_triggers' ] );

		// BadgeOS step data requirements filter
		add_filter( 'badgeos_get_step_requirements', [ &$this, 'step_data_requirements' ], 10, 2 );

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
			add_action( $trigger->trigger_action(), [ &$trigger, 'hook' ], 10, 20 );
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
	public function step_data_requirements( $requirements, $step_id )
	{
		// vars
		$trigger_type = get_post_meta( $step_id, '_badgeos_trigger_type', true );
		$triggers     = $this->get_triggers();

		if ( isset( $triggers[ $trigger_type ] ) )
		{
			// get step extra data based on the trigger
			$requirements = array_merge( $requirements, call_user_func( [
				&$triggers[ $trigger_type ],
				'get_data',
			], $step_id ) );
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
	public function register_new_triggers( $triggers )
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
		return apply_filters( 'trbs_rewards_activity_triggers', [
			'true_resident_listing_category_check_in' => new Listing_Category_Check_In_Trigger(),
		] );
	}
}

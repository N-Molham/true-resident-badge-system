<?php namespace True_Resident\Badge_System;

use ReflectionClass;

/**
 * BadgeOS rewards logic
 *
 * @package True_Resident\Badge_System
 */
class Rewards extends Component
{
	/**
	 * Additional triggers list holder
	 *
	 * @var array
	 */
	protected $triggers_list;

	/**
	 * Session key
	 *
	 * @var array
	 */
	protected $session_key = 'trbs_force_reload';

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
		$force_reload_status = filter_input( INPUT_GET, $this->session_key, FILTER_SANITIZE_STRING );
		switch ( $force_reload_status )
		{
			case 'reload':
				// force to reload data and discard cache for the next session request
				WC()->session->set( $this->session_key, true );
				break;

			case 'normal':
				WC()->session->set( $this->session_key, false );
				break;
		}

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
	 * @param  string  $trigger_type step trigger type
	 *
	 * @return array
	 */
	public function badgeos_step_data_requirements( $requirements, $step_id, $trigger_type = '' )
	{
		// vars
		$trigger_type = '' === $trigger_type || empty( $trigger_type ) ? $this->get_step_type( $step_id ) : $trigger_type;
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
	 * Load badges related to given listing
	 *
	 * @param int $listing_id
	 *
	 * @return array
	 */
	public function get_listings_badges( $listing_id )
	{
		// vars
		$cache_id     = 'trbs_listing_' . $listing_id . '_badges';
		$badges_found = get_transient( $cache_id );
		if ( false === true_resident_badge_system()->cache_disabled() && false !== $badges_found && false === WC()->session->get( $this->session_key, false ) )
		{
			// load from cache
			return $badges_found;
		}

		// not cached data, so calculate it.
		$triggers          = $this->get_triggers();
		$trigger_obj       = null;
		$badge_id          = null;
		$badge_steps       = null;
		$badges_found      = [];
		$step_trigger_type = null;

		$registered_badges = get_posts( [
			'post_type'          => 'badges',
			'nopaging'           => true,
			'trbs_listing_query' => true,
			'fields'             => 'ids',
		] );

		// walk through all badges
		for ( $i = 0, $badges_size = sizeof( $registered_badges ); $i < $badges_size; $i++ )
		{
			// badge required steps
			$badge_id    = $registered_badges[ $i ];
			$badge_steps = badgeos_get_required_achievements_for_achievement( $badge_id );
			foreach ( $badge_steps as $step )
			{
				$step_trigger_type = get_post_meta( $step->ID, '_badgeos_trigger_type', true );
				if ( empty( $step_trigger_type ) || !isset( $triggers[ $step_trigger_type ] ) )
				{
					// skip un-recognized trigger
					continue;
				}

				$trigger_obj = $triggers[ $step_trigger_type ];
				if ( $trigger_obj->related_to_listing( $listing_id, $step->ID ) )
				{
					// one of the steps are related to the listing so the badge is related also :)
					$badges_found[] = $badge_id;
					continue;
				}
			}
		}

		if ( sizeof( $badges_found ) > 0 )
		{
			// cache it for a day
			set_transient( $cache_id, $badges_found, 12 * HOUR_IN_SECONDS );
		}
		else
		{
			// nothing found
			$badges_found = [ 0 ];
		}

		return $badges_found;
	}

	/**
	 * List of new triggers
	 *
	 * @return array
	 */
	public function get_triggers()
	{
		if ( null == $this->triggers_list )
		{
			/**
			 * Filters the list of triggers' classes in the add-on
			 *
			 * @param array $triggers
			 *
			 * @return array
			 */
			$triggers_classes = apply_filters( 'trbs_rewards_activity_triggers', [
				'True_Resident\Badge_System\Triggers\Listing_Category_Check_In_Trigger',
				'True_Resident\Badge_System\Triggers\Listing_Tag_Check_In_Trigger',
				'True_Resident\Badge_System\Triggers\Specific_Listing_Check_In_Trigger',
				'True_Resident\Badge_System\Triggers\Listing_Challenges_Checklist_Trigger',
				'True_Resident\Badge_System\Triggers\Listings_Reviews_Trigger',
				'True_Resident\Badge_System\Triggers\User_Register_Trigger',
			] );

			foreach ( $triggers_classes as $trigger_class )
			{
				if ( !class_exists( $trigger_class ) )
				{
					// trigger class not found!
					continue;
				}

				// get instance
				$trigger = ( new ReflectionClass( $trigger_class ) )->newInstance();

				// append to list
				$this->triggers_list[ $trigger->activity_trigger() ] = $trigger;
			}
		}

		return $this->triggers_list;
	}

	/**
	 * Get completed percentage of the given step
	 *
	 * @param int $step_id
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function get_step_completed_percentage( $step_id, $user_id = null )
	{
		$step_type = $this->get_step_type( $step_id );
		if ( !isset( $this->triggers_list[ $step_type ] ) )
		{
			// step not in the additional type
			return 0;
		}

		if ( null === $user_id )
		{
			// current logged in user ID
			$user_id = get_current_user_id();
		}

		return $this->triggers_list[ $step_type ]->get_step_percentage( $step_id, $user_id );
	}

	/**
	 * Get achievement step trigger type
	 *
	 * @param int $step_id
	 *
	 * @return string
	 */
	public function get_step_type( $step_id )
	{
		return get_post_meta( $step_id, '_badgeos_trigger_type', true );
	}
}

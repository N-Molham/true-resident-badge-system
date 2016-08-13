<?php namespace True_Resident\Badge_System;

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

		if ( isset( $triggers[ $trigger_type ] ) && isset( $triggers[ $trigger_type ]['get_callback'] ) && is_callable( $triggers[ $trigger_type ]['get_callback'] ) )
		{
			// get step extra data based on the trigger
			$requirements = array_merge( $requirements, call_user_func( $triggers[ $trigger_type ]['get_callback'], $step_id ) );
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
			return $trigger['label'];
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
			'true_resident_listing_category_check_in' => [
				'label'         => __( 'True Resident Listing Category Check-in', TRBS_DOMAIN ),
				'ui_callback'   => [ &$this, 'listing_category_check_in_trigger_ui' ],
				'save_callback' => [ &$this, 'listing_category_check_in_trigger_save' ],
				'get_callback'  => [ &$this, 'listing_category_check_in_trigger_get' ],
			],
		] );
	}

	/**
	 * Get listings category trigger data
	 *
	 * @param int $step_id
	 *
	 * @return array
	 */
	public function listing_category_check_in_trigger_get( $step_id )
	{
		return [
			'check_in_listing_category' => absint( get_post_meta( $step_id, '_trbs_category', true ) ),
		];
	}

	/**
	 * Save listings category checking data
	 *
	 * @param int    $step_id
	 * @param array  $step_data
	 * @param array  $trigger_info
	 * @param string $trigger_name
	 *
	 * @return void
	 */
	public function listing_category_check_in_trigger_save( $step_id, $step_data, $trigger_info = null, $trigger_name = '' )
	{
		if ( 'true_resident_listing_category_check_in' !== $trigger_name || $trigger_name !== $step_data['trigger_type'] )
		{
			// skip non-related triggers
			return;
		}

		// save selected category
		update_post_meta( $step_id, '_trbs_category', absint( $step_data['check_in_listing_category'] ) );
	}

	/**
	 * Listings category check-in category list
	 *
	 * @param int $step_id
	 * @param int $badge_id
	 *
	 * @return void
	 */
	public function listing_category_check_in_trigger_ui( $step_id, $badge_id )
	{
		// categories dropdown
		echo str_replace( '<select', '<select data-toggle="true_resident_listing_category_check_in" ', wp_dropdown_categories( [
			'show_option_all' => __( 'Any Category', TRBS_DOMAIN ),
			'show_count'      => true,
			'hide_empty'      => false,
			'selected'        => absint( get_post_meta( $step_id, '_trbs_category', true ) ),
			'hierarchical'    => true,
			'echo'            => false,
			'name'            => "check_in_listing_category",
			'class'           => 'true-resident-listing-category true-resident-step-condition',
			'taxonomy'        => 'job_listing_category',
		] ) );
	}
}

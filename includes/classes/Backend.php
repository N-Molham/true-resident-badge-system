<?php namespace True_Resident\Badge_System;

/**
 * Backend logic
 *
 * @package True_Resident\Badge_System
 */
class Backend extends Component
{
	/**
	 * BadgeOS meta box field name prefix
	 *
	 * @var string
	 */
	protected $badge_field_prefix;

	/**
	 * Hidden pages from POI pages option name
	 *
	 * @var string
	 */
	protected $hidden_badges_option;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// vars
		$this->hidden_badges_option = '_trbs_hidden_badges';

		// WP initiation action hook
		add_action( 'init', [ &$this, 'badgeos_rewards_triggers_ui' ] );

		// WP Admin enqueue script action
		add_action( 'admin_enqueue_scripts', [ &$this, 'load_scripts' ] );

		// BadgeOS before saving step filter
		add_filter( 'badgeos_save_step', [ &$this, 'badgeos_save_step_triggers_options' ], 10, 3 );

		// BadgeOS badge meta box fields filter
		add_filter( 'badgeos_achievement_data_meta_box_fields', [
			&$this,
			'append_hide_from_listing_page_field',
		], 10, 3 );

		// WP post data save action
		add_action( 'save_post_badges', [ &$this, 'store_hidden_badges_as_option' ], 100 );

		// Listing data fields filter
		// add_filter( 'job_manager_job_listing_data_fields', [ &$this, 'add_listing_challenges_fields' ], 100 );
	}

	/**
	 * Add listing challenges data fields
	 *
	 * @return array
	 */
	public function add_listing_challenges_fields( $fields )
	{
		// query badges
		$badges = get_posts( [
			'post_type' => 'badges',
			'nopaging'  => true,
		] );

		// prepare badges list
		$badges_list = [ 'none' => __( 'None', TRBS_DOMAIN ) ];
		for ( $i = 0, $len = count( $badges ); $i < $len; $i++ )
		{
			// append badge to list
			$badges_list[ $badges[ $i ]->ID ] = $badges[ $i ]->post_title;
		}

		$fields['_challenges_badge'] = [
			'label'       => __( 'Reward Badge', TRBS_DOMAIN ),
			'description' => __( 'Which badge do user earn by completing the POI challenges', TRBS_DOMAIN ),
			'priority'    => 100,
			'type'        => 'select',
			'options'     => $badges_list,
		];

		return $fields;
	}

	/**
	 * Save hidden badge into option for later use
	 *
	 * @param int $badge_id
	 *
	 * @return void
	 */
	public function store_hidden_badges_as_option( $badge_id )
	{
		// hidden badges
		$hidden_badges = $this->get_hidden_badges();

		// if badge is hidden or not
		$is_hidden     = 'on' === get_post_meta( $badge_id, $this->badge_field_prefix . 'hide_from_listing', true );
		$in_list_index = array_search( $badge_id, $hidden_badges, true );

		if ( $is_hidden )
		{
			if ( false === $in_list_index )
			{
				// add to list
				$hidden_badges[] = $badge_id;
			}
		}
		else
		{
			if ( false !== $in_list_index )
			{
				// remove from the list
				unset( $hidden_badges[ $in_list_index ] );
			}
		}

		// update hidden list
		update_option( $this->hidden_badges_option, $hidden_badges, 'no' );
	}

	/**
	 * Append field for hiding badge from listing page
	 *
	 * @param array  $fields
	 * @param string $prefix
	 * @param array  $achievement_types
	 *
	 * @return array
	 */
	public function append_hide_from_listing_page_field( $fields, $prefix, $achievement_types )
	{
		if ( !in_array( 'badges', $achievement_types ) )
		{
			// skip if badge not in the achievements list
			return $fields;
		}

		// store the prefix for later
		$this->badge_field_prefix = $prefix;

		// add the new field
		$fields[] = [
			'name' => __( 'Hide Achievement From POI Page', TRBS_DOMAIN ),
			'desc' => ' ' . __( 'Yes, will hide this achievement from loading in the POI singular page.', TRBS_DOMAIN ),
			'id'   => $prefix . 'hide_from_listing',
			'type' => 'checkbox',
		];

		return $fields;
	}

	/**
	 * Save additional steps data
	 *
	 * @param  string  $title The original title for our step
	 * @param  integer $step_id The given step's post ID
	 * @param  array   $step_data Our array of all available step data
	 *
	 * @return string
	 */
	public function badgeos_save_step_triggers_options( $title, $step_id, $step_data )
	{
		$triggers = trbs_rewards()->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger )
		{
			// trigger additional UI
			call_user_func( [ $trigger, 'save_data' ], $step_id, $step_data, $trigger_name );
		}

		return $title;
	}

	/**
	 * Load script assets
	 *
	 * @return void
	 */
	public function load_scripts()
	{
		// main admin script
		wp_enqueue_script( 'trbs-triggers', Helpers::enqueue_path() . 'js/admin.js', [ 'jquery' ], trbs_version(), true );
	}

	/**
	 * Handle new rewards triggers UI
	 *
	 * @return void
	 */
	public function badgeos_rewards_triggers_ui()
	{
		$triggers = trbs_rewards()->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger )
		{
			// trigger additional UI
			add_action( 'badgeos_steps_ui_html_after_trigger_type', [ $trigger, 'user_interface' ], 10, 2 );
		}
	}

	/**
	 * Get list of hidden badges from POI singular pages
	 *
	 * @return array
	 */
	public function get_hidden_badges()
	{
		return array_values( get_option( $this->hidden_badges_option, [] ) );
	}
}

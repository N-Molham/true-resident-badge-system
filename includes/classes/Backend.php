<?php namespace True_Resident\Badge_System;

use True_Resident\Badge_System\Triggers\True_Resident_Trigger_Interface;

/**
 * Backend logic
 *
 * @package True_Resident\Badge_System
 */
class Backend extends Component
{
	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// WP initiation action hook
		add_action( 'init', [ &$this, 'badgeos_rewards_triggers_ui' ] );

		// WP Admin enqueue script action hook
		add_action( 'admin_enqueue_scripts', [ &$this, 'load_scripts' ] );

		// BadgeOS before saving step
		add_filter( 'badgeos_save_step', [ &$this, 'badgeos_save_step_triggers_options' ], 10, 3 );
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
}

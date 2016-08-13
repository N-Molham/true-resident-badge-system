<?php namespace True_Resident\Badge_System;

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
		add_action( 'init', [ &$this, 'rewards_triggers_ui' ] );

		// WP Admin enqueue script action hook
		add_action( 'admin_enqueue_scripts', [ &$this, 'load_scripts' ] );

		// BadgeOS before saving step
		add_filter( 'badgeos_save_step', [ &$this, 'save_step_triggers_options' ], 10, 3 );
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
	public function save_step_triggers_options( $title, $step_id, $step_data )
	{
		$triggers = trbs_rewards()->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger_info )
		{
			if ( isset( $trigger_info['save_callback'] ) && is_callable( $trigger_info['save_callback'] ) )
			{
				// trigger additional UI
				call_user_func( $trigger_info['save_callback'], $step_id, $step_data, $trigger_info, $trigger_name );
			}
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
		// base loading path
		$load_path = sprintf( '%s/assets/%s/', untrailingslashit( TRBS_URI ), Helpers::is_script_debugging() ? 'src' : 'dist' );

		// main admin script
		wp_enqueue_script( 'trbs-triggers', $load_path . 'js/admin.js', [ 'jquery' ], trbs_version(), true );
	}

	/**
	 * Handle new rewards triggers UI
	 *
	 * @return void
	 */
	public function rewards_triggers_ui()
	{
		$triggers = trbs_rewards()->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger_info )
		{
			if ( isset( $trigger_info['ui_callback'] ) && is_callable( $trigger_info['ui_callback'] ) )
			{
				// trigger additional UI
				add_action( 'badgeos_steps_ui_html_after_trigger_type', $trigger_info['ui_callback'], 10, 2 );
			}
		}
	}
}

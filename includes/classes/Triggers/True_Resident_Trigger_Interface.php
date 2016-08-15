<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Interface True_Resident_Trigger_Interface
 *
 * @package True_Resident\Badge_System\Triggers
 */
interface True_Resident_Trigger_Interface
{
	/**
	 * Trigger label
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Trigger WP action hook
	 *
	 * @return string
	 */
	public function trigger_action();

	/**
	 * Step trigger name
	 *
	 * @return string
	 */
	public function activity_trigger();

	/**
	 * Trigger Step UI
	 *
	 * @param int $step_id
	 * @param int $badge_id
	 *
	 * @return void
	 */
	public function user_interface( $step_id, $badge_id );

	/**
	 * Save trigger data
	 *
	 * @param int    $step_id
	 * @param array  $step_data
	 * @param array  $trigger_info
	 * @param string $trigger_name
	 *
	 * @return void
	 */
	public function save_data( $step_id, $step_data, $trigger_name = '' );

	/**
	 * Get trigger data
	 *
	 * @param int    $step_id
	 * @param string $trigger_type
	 *
	 * @return array
	 */
	public function get_data( $step_id, $trigger_type = '' );

	/**
	 * Activity trigger action hook callback
	 *
	 * @return void
	 */
	public function activity_hook();

	/**
	 * If user deserves achievement (badge) of not hook
	 *
	 * @param boolean $return
	 * @param int     $user_id
	 * @param int     $achievement_id
	 *
	 * @return boolean
	 */
	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id );
}
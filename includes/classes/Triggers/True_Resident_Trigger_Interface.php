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
	 * Trigger action hook
	 *
	 * @return string
	 */
	public function trigger_action();

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
	 * @param int $step_id
	 *
	 * @return array
	 */
	public function get_data( $step_id );

	/**
	 * Trigger action hook callback
	 *
	 * @return void
	 */
	public function hook();
}
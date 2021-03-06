<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Interface Trigger_Interface
 *
 * @package True_Resident\Badge_System\Triggers
 */
interface Trigger_Interface {

	/**
	 * Trigger label
	 *
	 * @return string
	 */
	public function label();

	/**
	 * Trigger WP action hook
	 *
	 * @return string|array
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
	 * @param string  $this_trigger
	 * @param int     $site_id
	 * @param array   $args
	 *
	 * @return boolean
	 */
	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args );

	/**
	 * Get completed percentage of the given step with this trigger for the given user
	 *
	 * @param int $step_id
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function get_step_percentage( $step_id, $user_id );

	/**
	 * Check of step with this trigger is linked to listings or not
	 *
	 * @param int $listing_id
	 * @param int $step_id
	 *
	 * @return boolean
	 */
	public function related_to_listing( $listing_id, $step_id );

	/**
	 * Get matched listings to given step if it's a listing page
	 *
	 * @param int $step_id
	 *
	 * @return array
	 */
	public function get_matching_listings( $step_id );

}
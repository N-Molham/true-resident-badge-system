<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Class Listings_Reviews_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class Listings_Reviews_Trigger implements True_Resident_Trigger_Interface
{
	/**
	 * Target listing post type
	 *
	 * @var string
	 */
	var $listing_post_type = 'job_listing';

	public function label()
	{
		return __( 'True Resident Review a Listing', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		// can happen on multiple actions
		return [
			'wp_insert_comment',
			'comment_approved_comment',
		];
	}

	public function activity_trigger()
	{
		return 'true_resident_listing_review';
	}

	public function activity_hook()
	{
		global $wpdb;

		$comment_data = func_get_arg( 1 );
		if ( is_object( $comment_data ) )
		{
			// swap with assoc array
			$comment_data = get_object_vars( $comment_data );
		}

		if ( 1 !== absint( $comment_data['comment_approved'] ) )
		{
			// skip un-approved reviews
			return;
		}

		if ( $this->listing_post_type !== get_post_type( $comment_data['comment_post_ID'] ) )
		{
			// skip un-related posts
			return;
		}

		// vars
		$blog_id      = get_current_blog_id();
		$comment_id   = func_get_arg( 0 );
		$user         = get_user_by( 'id', absint( $comment_data['user_id'] ) );
		$this_trigger = $this->activity_trigger();

		// update count
		$trigger_count = badgeos_update_user_trigger_count( $user->ID, $this_trigger, $blog_id );

		// Mark the count in the log entry
		badgeos_post_log_entry( $comment_id, $user->ID, $this_trigger, sprintf( __( '%1$s triggered %2$s (%3$dx)', TRBS_DOMAIN ), $user->display_name, $this_trigger, $trigger_count ) );

		// load achievements
		$achievements_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id as id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", '_badgeos_trigger_type', $this_trigger ) );
		foreach ( $achievements_ids as $achievement_id )
		{
			// user reward if match
			badgeos_maybe_award_achievement_to_user( $achievement_id, $user->ID, $this_trigger, $blog_id );
		}
	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		if ( 'step' != get_post_type( $achievement_id ) )
		{
			// If we're not dealing with a step, bail here
			return $return;
		}

		if ( $this->activity_trigger() !== $this_trigger )
		{
			// skip un-related trigger
			return $return;
		}

		// step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );

		// execute sql for the current count
		$comments_count = get_comments( [
			'user_id'   => $user_id,
			'post_type' => $this->listing_post_type,
			'status'    => 'approve',
			'count'     => true,
		] );
		if ( $comments_count >= $requirements['count'] )
		{
			// target reached
			$return = true;
		}

		return $return;
	}

	public function get_data( $step_id, $trigger_type = '' )
	{
		// not needed
		return [ ];
	}

	public function save_data( $step_id, $step_data, $trigger_name = '' )
	{
		// not needed
	}

	public function user_interface( $step_id, $badge_id )
	{
		// no additional information needed
	}

	public function get_step_percentage( $step_id, $user_id )
	{
		// step requirements
		$step_requirements = badgeos_get_step_requirements( $step_id );

		$comments_count = get_comments( [
			'user_id'   => $user_id,
			'post_type' => $this->listing_post_type,
			'status'    => 'approve',
			'count'     => true,
		] );

		return $comments_count ? round( ( $comments_count / $step_requirements['count'] ) * 100 ) : 0;
	}

	public function related_to_listing( $listing_id, $step_id )
	{
		return true;
	}
}
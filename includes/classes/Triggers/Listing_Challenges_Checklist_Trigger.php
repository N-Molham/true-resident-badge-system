<?php namespace True_Resident\Badge_System\Triggers;

use True_Resident\Badge_System\Helpers;

/**
 * Class Listing_Challenges_Checklist_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class Listing_Challenges_Checklist_Trigger implements Trigger_Interface
{
	/**
	 * Step meta key for listing ID
	 *
	 * @var string
	 */
	public $meta_key = '_trbs_listing_id';

	/**
	 * Step meta key for challenges checklist
	 *
	 * @var string
	 */
	public $checklist_meta_key = '_trbs_checklist';

	/**
	 * Target listing post type
	 *
	 * @var string
	 */
	public $listing_post_type = 'job_listing';

	/**
	 * Target listing ID field name
	 *
	 * @var string
	 */
	public $listing_id_field_name = 'challenges_checklist_listing_id';

	/**
	 * Challenges checklist field name
	 *
	 * @var string
	 */
	public $checklist_field_name = 'challenges_checklist';

	/**
	 * Listing_Challenges_Checklist_Trigger constructor.
	 */
	public function __construct()
	{
		// store WP path for later
		$_SESSION['trbs_wp_path'] = ABSPATH;

		if (
			'save_checklist' === filter_input( INPUT_POST, 'trbs_action', FILTER_SANITIZE_STRING ) &&
			wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING ), 'trbs_save_challenges_checklist' )
		)
		{
			// target step
			$step_id  = absint( filter_input( INPUT_POST, 'checklist_step', FILTER_SANITIZE_NUMBER_INT ) );
			$badge_id = absint( filter_input( INPUT_POST, 'checklist_badge', FILTER_SANITIZE_NUMBER_INT ) );

			// posted checklist points
			$checklist = array_map( 'sanitize_text_field', (array) filter_input( INPUT_POST, 'checklist_points', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY ) );

			$this->save_checklist( $checklist, $step_id, $badge_id );
		}
	}

	/**
	 * Save step/trigger challenges checklist
	 *
	 * @param array $checklist
	 * @param int   $step_id
	 * @param int   $badge_id
	 *
	 * @return void
	 */
	public function save_checklist( $checklist, $step_id, $badge_id = 0 )
	{
		/**
		 * Filter badge step challenges checklist
		 *
		 * @param array $checklist
		 * @param int   $step_id
		 * @param int   $badge_id
		 *
		 * @return array
		 */
		$checklist = apply_filters( 'trbs_save_step_challenges_checklist', $checklist, $step_id, $badge_id );

		// vars
		$max_index = max( array_keys( $checklist ) );
		$step_data = $this->get_data( $step_id );

		if ( $max_index > $step_data['checklist_max_index'] )
		{
			// update new max index
			update_post_meta( $step_id, '_trbs_checklist_max', $max_index );
		}

		// save meta
		update_post_meta( $step_id, $this->checklist_meta_key, $checklist );
	}

	public function label()
	{
		return __( 'True Resident Listing Challenges Checklist', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'trbs_checklist_mark_added';
	}

	public function activity_trigger()
	{
		return 'true_resident_listing_challenges_checklist';
	}

	public function activity_hook()
	{
		// vars
		$mark_id      = func_get_arg( 0 );
		$mark_fields  = func_get_arg( 1 );
		$blog_id      = get_current_blog_id();
		$user         = get_user_by( 'id', absint( $mark_fields['user_id'] ) );
		$this_trigger = $this->activity_trigger();

		// update count
		$trigger_count = badgeos_update_user_trigger_count( $user->ID, $this_trigger, $blog_id );

		// Mark the count in the log entry
		badgeos_post_log_entry( $mark_id, $user->ID, $this_trigger, sprintf( __( '%1$s triggered %2$s (%3$dx)', TRBS_DOMAIN ), $user->display_name, $this_trigger, $trigger_count ) );

		// user reward if match
		badgeos_maybe_award_achievement_to_user( $mark_fields['step_id'], $user->ID, $this_trigger, $blog_id, $mark_fields );
	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		// If we're not dealing with a step, bail here
		if ( 'step' !== get_post_type( $achievement_id ) )
		{
			return $return;
		}

		// get step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );
		if ( !isset( $requirements[ $this->listing_id_field_name ] ) || !isset( $requirements[ $this->checklist_field_name ] ) )
		{
			// skip un-related type
			return $return;
		}

		$last_earning = trbs_rewards()->get_last_badge_earning( $achievement_id, $user_id );
		if ( false !== $last_earning )
		{
			// get marks after the last time earnings
			$args['after'] = date( 'Y-m-d H:i:s', $last_earning->date_earned );
		}

		// get user current checklist marks
		$current_marks = trbs_rewards()->get_checklist_marks( $args );
		$marks_count   = count( $current_marks );

		if ( 0 === $marks_count )
		{
			// no marks found!
			return false;
		}

		// fetch only marked points
		$current_marks = array_unique( array_map( function ( $mark )
		{
			return absint( $mark->point_id );
		}, $current_marks ) );

		// checklist points IDs/indexes
		$checklist_points = array_keys( $requirements[ $this->checklist_field_name ] );

		// intersection points
		$common_points = array_intersect( $checklist_points, $current_marks );

		// all checklist points marked or not!
		return count( $checklist_points ) === count( $common_points );
	}

	public function get_data( $step_id, $trigger_type = '' )
	{
		if ( '' === $trigger_type || empty( $trigger_type ) )
		{
			// if step trigger type not passed
			$trigger_type = trbs_rewards()->get_step_type( $step_id );
		}

		if ( $this->activity_trigger() !== $trigger_type )
		{
			// not the same trigger type
			return [];
		}

		return [
			$this->listing_id_field_name => absint( get_post_meta( $step_id, $this->meta_key, true ) ),
			$this->checklist_field_name  => array_filter( (array) get_post_meta( $step_id, $this->checklist_meta_key, true ) ),
			'checklist_max_index'        => absint( get_post_meta( $step_id, '_trbs_checklist_max', true ) ),
		];
	}

	public function save_data( $step_id, $step_data, $trigger_name = '' )
	{
		if ( $this->activity_trigger() !== $trigger_name || $trigger_name !== $step_data['trigger_type'] )
		{
			// skip non-related triggers
			return;
		}

		// save selected listing
		update_post_meta( $step_id, $this->meta_key, absint( $step_data[ $this->listing_id_field_name ] ) );
	}

	public function user_interface( $step_id, $badge_id )
	{
		// values
		$listing_id = absint( get_post_meta( $step_id, $this->meta_key, true ) );

		if ( 0 === $listing_id )
		{
			// no value was set
			$listing_id = '';
		}

		// selected listing ID field
		printf( '<input type="text" size="6" name="%s" placeholder="%s" class="true-resident-autocomplete true-resident-step-condition" 
				data-toggle="%s" data-post-type="%s" data-return="id" value="%s" />',
			$this->listing_id_field_name,
			__( 'Listing ID', TRBS_DOMAIN ),
			$this->activity_trigger(),
			$this->listing_post_type,
			$listing_id
		);

		// challenges checklist button
		printf( '<a href="%s" title="%5$s" class="button thickbox true-resident-step-condition" data-toggle="%s" data-step="%d" data-badge="%d">%5$s</a>',
			add_query_arg( [
				'step_id'   => $step_id,
				'badge_id'  => $badge_id,
				'step_type' => $this->activity_trigger(),
				'nonce'     => wp_create_nonce( 'trbs_manage_challenges_checklist_' . $step_id ),
				'TB_iframe' => 'true',
				'width'     => '600',
				'height'    => '550',
			], TRBS_URI . 'views/external/manage_checklist.php' ),
			$this->activity_trigger(),
			$step_id,
			$badge_id,
			esc_attr( __( 'Manage Checklist', TRBS_DOMAIN ) )
		);
	}

	public function get_step_percentage( $step_id, $user_id )
	{
		// get step requirements
		$requirements = badgeos_get_step_requirements( $step_id );
		if ( !isset( $requirements[ $this->listing_id_field_name ] ) || !isset( $requirements[ $this->checklist_field_name ] ) )
		{
			// skip un-related type
			return 0;
		}

		$marks_args   = [ 'user_id' => $user_id, 'step_id' => $step_id ];
		$last_earning = trbs_rewards()->get_last_badge_earning( $step_id, $user_id );
		if ( false !== $last_earning )
		{
			// get marks after the last time earnings
			$marks_args['after'] = date( 'Y-m-d H:i:s', $last_earning->date_earned );
		}

		// get user current checklist marks
		$current_marks = trbs_rewards()->get_checklist_marks( $marks_args );
		$marks_count   = count( $current_marks );

		if ( 0 === $marks_count )
		{
			// no marks found!
			return 0;
		}

		// fetch only marked points
		$current_marks = array_unique( array_map( function ( $mark )
		{
			return absint( $mark->point_id );
		}, $current_marks ) );

		// checklist points IDs/indexes
		$checklist_points = array_keys( $requirements[ $this->checklist_field_name ] );

		// intersection points
		$common_points = array_intersect( $checklist_points, $current_marks );

		return round( ( count( $common_points ) / count( $checklist_points ) ) * 100 );
	}

	public function related_to_listing( $listing_id, $step_id )
	{
		// get step requirements
		$requirements = badgeos_get_step_requirements( $step_id );

		return $listing_id === $requirements[ $this->listing_id_field_name ];
	}

	/**
	 * Check if given step is checklist trigger type of not
	 *
	 * @param int $step_id
	 *
	 * @return bool
	 */
	public function is_checklist_step( $step_id )
	{
		$step_data = $this->get_data( $step_id, $this->activity_trigger() );

		return array_key_exists( $this->checklist_field_name, $step_data );
	}
}
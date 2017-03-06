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
	var $meta_key = '_trbs_listing_id';

	/**
	 * Step meta key for challenges checklist
	 *
	 * @var string
	 */
	var $checklist_meta_key = '_trbs_checklist';

	/**
	 * Target listing post type
	 *
	 * @var string
	 */
	var $listing_post_type = 'job_listing';

	/**
	 * Target listing ID field name
	 *
	 * @var string
	 */
	var $listing_id_field_name = 'challenges_checklist_listing_id';

	/**
	 * Challenges checklist field name
	 *
	 * @var string
	 */
	var $checklist_field_name = 'challenges_checklist_list';

	public function label()
	{
		return __( 'True Resident Listing Challenges Checklist', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'true_resident_listing_challenge_checked';
	}

	public function activity_trigger()
	{
		return 'true_resident_listing_challenges_checklist';
	}

	public function activity_hook()
	{

	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		return $return;
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
			$this->checklist_field_name  => get_post_meta( $step_id, $this->checklist_meta_key, true ),
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

		// save checklist
		update_post_meta( $step_id, $this->checklist_meta_key, Helpers::sanitize_text_field_with_linebreaks( $step_data[ $this->checklist_field_name ] ) );
	}

	public function user_interface( $step_id, $badge_id )
	{
		// values
		$checklist  = get_post_meta( $step_id, $this->checklist_meta_key, true );
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

		// challenges checklist
		printf( '<textarea type="text" name="%s" placeholder="%s" class="true-resident-autocomplete true-resident-step-condition" cols="58" rows="8"
				data-toggle="%s" data-post-type="%s" data-return="id">%s</textarea>',
			$this->checklist_field_name,
			__( 'Challenges Checklist, each point/challenge in new line', TRBS_DOMAIN ),
			$this->activity_trigger(),
			$this->listing_post_type,
			$checklist
		);
	}

	public function get_step_percentage( $step_id, $user_id )
	{
		return 0;
	}

	public function related_to_listing( $listing_id, $step_id )
	{
		// get step requirements
		$requirements = badgeos_get_step_requirements( $step_id );

		return $listing_id === $requirements[ $this->listing_id_field_name ];
	}
}
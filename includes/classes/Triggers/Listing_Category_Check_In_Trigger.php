<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Class Listing_Category_Check_In_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class Listing_Category_Check_In_Trigger implements True_Resident_Trigger_Interface
{
	/**
	 * Step meta key for category
	 *
	 * @var string
	 */
	var $meta_key = '_trbs_category';

	public function label()
	{
		return __( 'True Resident Listing Category Check-in', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'true_resident_listing_new_check_in';
	}

	public function hook()
	{
		dd(get_current_blog_id());
		dd( func_get_args() );
	}

	public function get_data( $step_id )
	{
		return [
			'check_in_listing_category' => absint( get_post_meta( $step_id, $this->meta_key, true ) ),
		];
	}

	public function save_data( $step_id, $step_data, $trigger_name = '' )
	{
		if ( 'true_resident_listing_category_check_in' !== $trigger_name || $trigger_name !== $step_data['trigger_type'] )
		{
			// skip non-related triggers
			return;
		}

		// save selected category
		update_post_meta( $step_id, $this->meta_key, absint( $step_data['check_in_listing_category'] ) );
	}

	public function user_interface( $step_id, $badge_id )
	{
		// categories dropdown
		echo str_replace( '<select', '<select data-toggle="true_resident_listing_category_check_in" ', wp_dropdown_categories( [
			'show_option_all' => __( 'Any Category', TRBS_DOMAIN ),
			'show_count'      => true,
			'hide_empty'      => false,
			'selected'        => absint( get_post_meta( $step_id, $this->meta_key, true ) ),
			'hierarchical'    => true,
			'echo'            => false,
			'name'            => "check_in_listing_category",
			'class'           => 'true-resident-listing-category true-resident-step-condition',
			'taxonomy'        => 'job_listing_category',
		] ) );
	}
}
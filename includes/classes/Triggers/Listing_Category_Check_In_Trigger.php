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

	/**
	 * Target category taxonomy
	 *
	 * @var string
	 */
	var $category_taxonomy = 'job_listing_category';

	public function label()
	{
		return __( 'True Resident Listing Category Check-in', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'true_resident_listing_new_check_in';
	}

	public function activity_trigger()
	{
		return 'true_resident_listing_category_check_in';
	}

	public function activity_hook()
	{
		global $wpdb;

		// vars
		$do_award            = false;
		$blog_id             = get_current_blog_id();
		$user                = get_user_by( 'id', func_get_arg( 0 ) );
		$post_id             = func_get_arg( 1 );
		$this_trigger        = $this->activity_trigger();
		$listings_categories = wp_get_post_terms( $post_id, $this->category_taxonomy, [ 'fields' => 'ids' ] );

		// update count
		$trigger_count = badgeos_update_user_trigger_count( $user->ID, $this_trigger, $blog_id );

		// Mark the count in the log entry
		badgeos_post_log_entry( $post_id, $user->ID, $this_trigger, sprintf( __( '%1$s triggered %2$s (%3$dx)', TRBS_DOMAIN ), $user->display_name, $this_trigger, $trigger_count ) );

		// load achievements
		$achievements = $wpdb->get_results( $wpdb->prepare( "SELECT post_id as id, meta_value as category_id FROM $wpdb->postmeta WHERE meta_key = %s", $this->meta_key ) );

		foreach ( $achievements as $achievement )
		{
			// achievement scope
			$achievement->category_id = absint( $achievement->category_id );
			if ( $achievement->category_id )
			{
				if ( in_array( $achievement->category_id, $listings_categories ) )
				{
					// specific listing category
					$do_award = true;
				}
			}
			else
			{
				// any category
				$do_award = true;
			}

			if ( $do_award )
			{
				badgeos_maybe_award_achievement_to_user( $achievement->id, $user->ID, $this_trigger, $blog_id );
				$do_award = false;
			}
		}
	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		global $wpdb;

		// If we're not dealing with a step, bail here
		if ( 'step' != get_post_type( $achievement_id ) )
		{
			return $return;
		}

		// get step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );
		if ( !isset( $requirements['check_in_listing_category'] ) )
		{
			// skip un-related type
			return $return;
		}

		// vars
		$table_name   = trbs_bookmarks()->table_name();
		$count_sql    = "SELECT COUNT(id) FROM {$table_name} WHERE user_id = %d";
		$count_params = [ $user_id ];

		$category_id = $requirements['check_in_listing_category'];
		if ( $category_id )
		{
			// specific category
			$category_listings = implode( ',', get_posts( [
				'post_type' => 'job_listing',
				'nopaging'  => true,
				'fields'    => 'ids',
				'tax_query' => [
					[ 'taxonomy' => $this->category_taxonomy, 'field' => 'term_id', 'terms' => $category_id ],
				],
			] ) );

			if ( !empty($category_listings) )
			{
				// filter listings scope
				$count_sql .= " AND post_id IN ({$category_listings})";
			}
		}

		// execute sql for the current count
		$check_in_count = absint( $wpdb->get_var( $wpdb->prepare( $count_sql, $count_params ) ) );
		if ( $check_in_count >= $requirements['count'] )
		{
			// target reached
			$return = true;
		}

		return $return;
	}

	public function get_data( $step_id, $trigger_type = '' )
	{
		if ( empty( $trigger_type ) )
		{
			// if step trigger type not passed
			$trigger_type = get_post_meta( $step_id, '_badgeos_trigger_type', true );
		}

		if ( $this->activity_trigger() !== $trigger_type )
		{
			// not the same trigger type
			return [ ];
		}

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
			'taxonomy'        => $this->category_taxonomy,
		] ) );
	}
}
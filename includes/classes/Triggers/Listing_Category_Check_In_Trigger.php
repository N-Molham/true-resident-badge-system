<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Class Listing_Category_Check_In_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class Listing_Category_Check_In_Trigger implements Trigger_Interface
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

	/**
	 * UI field name
	 *
	 * @var string
	 */
	var $category_field_name = 'check_in_listing_category';

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
		if ( 'step' != get_post_type( $achievement_id ) )
		{
			// If we're not dealing with a step, bail here
			return $return;
		}

		// get step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );
		if ( !isset( $requirements[ $this->category_field_name ] ) )
		{
			// skip un-related type
			return $return;
		}

		// execute sql for the current count
		$check_in_count = $this->get_check_ins_count( $user_id, $requirements[ $this->category_field_name ] );
		if ( $check_in_count >= $requirements['count'] )
		{
			// target reached
			$return = true;
		}

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
			$this->category_field_name => absint( get_post_meta( $step_id, $this->meta_key, true ) ),
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
		update_post_meta( $step_id, $this->meta_key, absint( $step_data[ $this->category_field_name ] ) );
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
			'name'            => $this->category_field_name,
			'class'           => 'true-resident-listing-category true-resident-step-condition',
			'taxonomy'        => $this->category_taxonomy,
		] ) );
	}

	public function get_step_percentage( $step_id, $user_id )
	{
		// vars
		$step_requirements = badgeos_get_step_requirements( $step_id );
		$check_ins_count   = $this->get_check_ins_count( $user_id, $step_requirements[ $this->category_field_name ] );
		if ( 0 === $check_ins_count )
		{
			// non-done yet
			return 0;
		}

		return round( ( $check_ins_count / $step_requirements['count'] ) * 100 );
	}

	/**
	 * Get check-ins count of the user
	 *
	 * @param int $user_id
	 * @param int $category_id
	 *
	 * @return int
	 */
	public function get_check_ins_count( $user_id, $category_id = 0 )
	{
		global $wpdb;

		// vars
		$table_name = trbs_bookmarks()->table_name();
		$count_sql  = "SELECT COUNT(id) FROM {$table_name} WHERE user_id = %d";

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

			if ( !empty( $category_listings ) )
			{
				// filter listings scope
				$count_sql .= " AND post_id IN ({$category_listings})";
			}
		}

		// execute sql for the current count
		return absint( $wpdb->get_var( $wpdb->prepare( $count_sql, [ $user_id ] ) ) );
	}

	public function related_to_listing( $listing_id, $step_id )
	{
		// get step requirements
		$requirements = badgeos_get_step_requirements( $step_id );
		if ( 0 === $requirements[ $this->category_field_name ] )
		{
			// will work on any listing despite the category
			return true;
		}

		// listing linked terms for comparison
		$listing_terms = wp_get_post_terms( $listing_id, $this->category_taxonomy, [ 'fields' => 'ids' ] );

		return is_array( $listing_terms ) && in_array( $requirements[ $this->category_field_name ], $listing_terms );
	}
}
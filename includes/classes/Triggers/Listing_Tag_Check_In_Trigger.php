<?php namespace True_Resident\Badge_System\Triggers;

/**
 * Class Listing_Tag_Check_In_Trigger
 *
 * @package True_Resident\Badge_System\Triggers
 */
class Listing_Tag_Check_In_Trigger implements Trigger_Interface
{
	/**
	 * Step meta keys
	 *
	 * @var array
	 */
	public $meta_keys = [
		'taxonomy' => '_trbs_taxonomy',
		'term'     => '_trbs_term',
	];

	/**
	 * UI field names
	 *
	 * @var array
	 */
	public $field_names = [
		'taxonomy' => 'check_in_listing_taxonomy',
		'term'     => 'check_in_listing_term',
	];

	/**
	 * Excluded taxonomies
	 *
	 * @var array
	 */
	public $exclude_taxonomy = [
		'job_listing_region',
		'job_listing_category',
	];

	public function label()
	{
		return __( 'True Resident Listing Tags Check-in', TRBS_DOMAIN );
	}

	public function trigger_action()
	{
		return 'true_resident_listing_new_check_in';
	}

	public function activity_trigger()
	{
		return 'true_resident_listing_tag_check_in';
	}

	public function activity_hook()
	{
		global $wpdb;

		// vars
		$blog_id      = get_current_blog_id();
		$user         = get_user_by( 'id', func_get_arg( 0 ) );
		$post_id      = func_get_arg( 1 );
		$this_trigger = $this->activity_trigger();

		// update count
		$trigger_count = badgeos_update_user_trigger_count( $user->ID, $this_trigger, $blog_id );

		// Mark the count in the log entry
		badgeos_post_log_entry( $post_id, $user->ID, $this_trigger, sprintf( __( '%1$s triggered %2$s (%3$dx)', TRBS_DOMAIN ), $user->display_name, $this_trigger, $trigger_count ) );

		// load achievements
		$achievements = $wpdb->get_results( $wpdb->prepare( "SELECT tax_meta.post_id AS id, tax_meta.meta_value AS taxonomy, term_meta.meta_value as term_id
FROM {$wpdb->postmeta} as tax_meta
LEFT JOIN {$wpdb->postmeta} as term_meta ON term_meta.post_id = tax_meta.post_id AND term_meta.meta_key = %s
WHERE tax_meta.meta_key = %s", $this->meta_keys['term'], $this->meta_keys['taxonomy'] ) );
		foreach ( $achievements as $achievement )
		{
			// achievement scope
			$post_terms = wp_get_post_terms( $post_id, $achievement->taxonomy, [ 'fields' => 'ids' ] );
			if ( is_wp_error( $post_terms ) )
			{
				// skip invalid link
				continue;
			}

			if ( in_array( $achievement->term_id, $post_terms ) )
			{
				// do it
				badgeos_maybe_award_achievement_to_user( $achievement->id, $user->ID, $this_trigger, $blog_id );
			}
		}
	}

	public function user_deserves_achievement_hook( $return, $user_id, $achievement_id, $this_trigger, $site_id, $args )
	{
		if ( 'step' !== get_post_type( $achievement_id ) )
		{
			// If we're not dealing with a step, bail here
			return $return;
		}

		// get step requirements
		$requirements = badgeos_get_step_requirements( $achievement_id );
		if ( !isset( $requirements[ $this->field_names['term'] ], $requirements[ $this->field_names['taxonomy'] ] ) )
		{
			// skip un-related type
			return $return;
		}

		// execute sql for the current count
		$check_in_count = $this->get_check_ins_count( $user_id, $requirements[ $this->field_names['term'] ], $requirements[ $this->field_names['taxonomy'] ] );
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

		$data = [];
		foreach ( $this->field_names as $field_key => $field_name )
		{
			$data[ $field_name ] = get_post_meta( $step_id, $this->meta_keys[ $field_key ], true );
		}

		return $data;
	}

	public function save_data( $step_id, $step_data, $trigger_name = '' )
	{
		if ( $this->activity_trigger() !== $trigger_name || $trigger_name !== $step_data['trigger_type'] )
		{
			// skip non-related triggers
			return;
		}

		foreach ( $this->field_names as $field_key => $field_name )
		{
			if ( !isset( $step_data[ $field_name ] ) )
			{
				// field value wasn't set
				continue;
			}

			// save field value
			update_post_meta( $step_id, $this->meta_keys[ $field_key ], $step_data[ $field_name ] );
		}
	}

	public function user_interface( $step_id, $badge_id )
	{
		// vars
		$taxonomies        = $this->get_taxonomies();
		$step_data         = $this->get_data( $step_id, $this->activity_trigger() );
		$selected_taxonomy = $step_data[ $this->field_names['taxonomy'] ];

		// taxonomies start
		echo '<select name="', $this->field_names['taxonomy'], '" class="true-resident-step-condition true-resident-tax-type" data-toggle="', $this->activity_trigger(), '">';
		echo '<option value="">', __( '-- Tag Type --', TRBS_DOMAIN ), '</option>';
		foreach ( $taxonomies as $taxonomy_name => $taxonomy_label )
		{
			echo '<option value="', $taxonomy_name, '"', selected( $selected_taxonomy, $taxonomy_name, false ), '>', $taxonomy_label, '</option>';
		}
		// taxonomies end
		echo '</select>';

		// taxonomies start
		echo '<select name="', $this->field_names['term'], '" data-value="', $step_data[ $this->field_names['term'] ], '"class="true-resident-step-condition true-resident-term" data-toggle="', $this->activity_trigger(), '">';
		echo '<option value="">', __( '-- Select A Tag --', TRBS_DOMAIN ), '</option>';
		// taxonomies end
		echo '</select>';
	}

	public function get_taxonomies()
	{
		$taxonomies = get_object_taxonomies( 'job_listing', 'object' );
		foreach ( $this->exclude_taxonomy as $exclude_tax )
		{
			if ( isset( $taxonomies[ $exclude_tax ] ) )
			{
				// remove excluded taxonomy
				unset( $taxonomies[ $exclude_tax ] );
			}
		}

		return array_map( function ( $tax_obj )
		{
			// get taxonomy name
			return get_taxonomy_labels( $tax_obj )->name;
		}, $taxonomies );
	}

	public function get_step_percentage( $step_id, $user_id )
	{
		// vars
		$step_requirements = badgeos_get_step_requirements( $step_id );
		if ( !isset( $step_requirements[ $this->field_names['term'] ], $step_requirements[ $this->field_names['taxonomy'] ] ) )
		{
			// un-filled data
			return 0;
		}

		$check_ins_count = $this->get_check_ins_count( $user_id, $step_requirements[ $this->field_names['term'] ], $step_requirements[ $this->field_names['taxonomy'] ] );
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
	 * @param int    $user_id
	 * @param int    $term_id
	 * @param string $taxonomy
	 *
	 * @return int
	 */
	public function get_check_ins_count( $user_id, $term_id, $taxonomy )
	{
		global $wpdb;

		// vars
		$table_name = trbs_bookmarks()->table_name();
		$term_posts = implode( ',', get_posts( [
			'post_type' => 'job_listing',
			'nopaging'  => true,
			'fields'    => 'ids',
			'tax_query' => [
				[ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $term_id ],
			],
		] ) );

		// execute sql for the current count
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name} WHERE user_id = %d AND post_id IN ({$term_posts})", [ $user_id ] ) ) );
	}

	public function related_to_listing( $listing_id, $step_id )
	{
		// get step requirements
		$requirements  = badgeos_get_step_requirements( $step_id );
		$listing_terms = wp_get_post_terms( $listing_id, $requirements[ $this->field_names['taxonomy'] ], [ 'fields' => 'ids' ] );

		return is_array( $listing_terms ) && in_array( $requirements[ $this->field_names['term'] ], $listing_terms );
	}
}
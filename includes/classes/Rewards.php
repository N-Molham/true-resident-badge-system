<?php namespace True_Resident\Badge_System;

use ReflectionClass;
use stdClass;
use True_Resident\Badge_System\Triggers\Trigger_Interface;
use WP_Error;
use WP_Post;

/**
 * BadgeOS rewards logic
 *
 * @package True_Resident\Badge_System
 */
class Rewards extends Component
{
	/**
	 * Additional triggers list holder
	 *
	 * @var array
	 */
	protected $triggers_list;

	/**
	 * Session key
	 *
	 * @var array
	 */
	protected $session_key = 'trbs_force_reload';

	/**
	 * Activity suggestion form setting option name
	 *
	 * @var string
	 */
	protected $suggestion_form_option_name = 'trbs_suggestion_form';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// BadgeOS activity triggers action
		add_filter( 'badgeos_activity_triggers', [ &$this, 'badegos_register_new_triggers' ] );

		// BadgeOS step data requirements filter
		add_filter( 'badgeos_get_step_requirements', [ &$this, 'badgeos_step_data_requirements' ], 10, 2 );

		// WP Initialization
		add_action( 'init', [ &$this, 'setup_db_tables_names' ], 1 );
		add_action( 'init', [ &$this, 'badgeos_load_triggers' ] );
	}

	/**
	 * Set checklist marks database table name
	 *
	 * @return void
	 */
	public function setup_db_tables_names()
	{
		global $wpdb;

		$wpdb->checklist_marks = $wpdb->prefix . 'checklist_marks';
	}

	/**
	 * Load additional BadgeOS triggers hooks
	 *
	 * @return void
	 */
	public function badgeos_load_triggers()
	{
		$force_reload_status = filter_input( INPUT_GET, $this->session_key, FILTER_SANITIZE_STRING );
		switch ( $force_reload_status )
		{
			case 'reload':
				// force to reload data and discard cache for the next session request
				WC()->session->set( $this->session_key, true );
				break;

			case 'normal':
				WC()->session->set( $this->session_key, false );
				break;
		}

		$triggers = $this->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger )
		{
			// hook up trigger action
			$trigger_action = $trigger->trigger_action();
			if ( !is_array( $trigger_action ) )
			{
				// wrap single action in array
				$trigger_action = [ $trigger_action ];
			}

			foreach ( $trigger_action as $action_name )
			{
				add_action( $action_name, [ $trigger, 'activity_hook' ], 10, 20 );
			}

			// user deserves filter hook
			add_filter( 'user_deserves_achievement', [ $trigger, 'user_deserves_achievement_hook' ], 15, 6 );
		}
	}

	/**
	 * Update badgeos_get_step_requirements to include our custom requirements
	 *
	 * @param  array   $requirements The current step requirements
	 * @param  integer $step_id The given step's post ID
	 * @param  string  $trigger_type step trigger type
	 *
	 * @return array
	 */
	public function badgeos_step_data_requirements( $requirements, $step_id, $trigger_type = '' )
	{
		// vars
		$trigger_type = '' === $trigger_type || empty( $trigger_type ) ? $this->get_step_type( $step_id ) : $trigger_type;
		$triggers     = $this->get_triggers();

		if ( isset( $triggers[ $trigger_type ] ) )
		{
			// get step extra data based on the trigger
			$requirements = array_merge( $requirements, call_user_func( [
				$triggers[ $trigger_type ],
				'get_data',
			], $step_id, $trigger_type ) );
		}

		return $requirements;
	}

	/**
	 * Register the new activity triggers for BadgeOS
	 *
	 * @param array $triggers
	 *
	 * @return array
	 */
	public function badegos_register_new_triggers( $triggers )
	{
		// list trigger with labels
		$triggers = array_merge( $triggers, array_map( function ( $trigger )
		{
			return $trigger->label();
		}, $this->get_triggers() ) );

		return $triggers;
	}

	/**
	 * Load badges related to given listing
	 *
	 * @param int $listing_id
	 *
	 * @return array
	 */
	public function get_listings_badges( $listing_id )
	{
		// vars
		$cache_id     = 'trbs_listing_' . $listing_id . '_badges';
		$badges_found = get_transient( $cache_id );
		if ( false === true_resident_badge_system()->cache_disabled() && false !== $badges_found && false === WC()->session->get( $this->session_key, false ) )
		{
			// load from cache
			return $badges_found;
		}

		// not cached data, so calculate it.
		$triggers          = $this->get_triggers();
		$trigger_obj       = null;
		$badge_id          = null;
		$badge_steps       = null;
		$badges_found      = [];
		$step_trigger_type = null;

		$registered_badges = get_posts( [
			'post_type'          => 'badges',
			'nopaging'           => true,
			'trbs_listing_query' => true,
			'fields'             => 'ids',
		] );

		// walk through all badges
		for ( $i = 0, $badges_size = count( $registered_badges ); $i < $badges_size; $i++ )
		{
			// badge required steps
			$badge_id    = $registered_badges[ $i ];
			$badge_steps = badgeos_get_required_achievements_for_achievement( $badge_id );
			foreach ( $badge_steps as $step )
			{
				$step_trigger_type = get_post_meta( $step->ID, '_badgeos_trigger_type', true );
				if ( empty( $step_trigger_type ) || !isset( $triggers[ $step_trigger_type ] ) )
				{
					// skip un-recognized trigger
					continue;
				}

				$trigger_obj = $triggers[ $step_trigger_type ];
				if ( $trigger_obj->related_to_listing( $listing_id, $step->ID ) )
				{
					// one of the steps are related to the listing so the badge is related also :)
					$badges_found[] = $badge_id;
					continue;
				}
			}
		}

		if ( count( $badges_found ) > 0 )
		{
			// cache it for a day
			set_transient( $cache_id, $badges_found, 12 * HOUR_IN_SECONDS );
		}
		else
		{
			// nothing found
			$badges_found = [ 0 ];
		}

		return $badges_found;
	}

	/**
	 * List of new triggers
	 *
	 * @return array
	 */
	public function get_triggers()
	{
		if ( null === $this->triggers_list )
		{
			/**
			 * Filters the list of triggers' classes in the add-on
			 *
			 * @param array $triggers
			 *
			 * @return array
			 */
			$triggers_classes = (array) apply_filters( 'trbs_rewards_activity_triggers', [
				'True_Resident\Badge_System\Triggers\Listing_Category_Check_In_Trigger',
				'True_Resident\Badge_System\Triggers\Listing_Tag_Check_In_Trigger',
				'True_Resident\Badge_System\Triggers\Specific_Listing_Check_In_Trigger',
				'True_Resident\Badge_System\Triggers\Listing_Challenges_Checklist_Trigger',
				'True_Resident\Badge_System\Triggers\Listings_Reviews_Trigger',
				'True_Resident\Badge_System\Triggers\User_Register_Trigger',
			] );

			foreach ( $triggers_classes as $trigger_class )
			{
				if ( !class_exists( $trigger_class ) )
				{
					// trigger class not found!
					continue;
				}

				// get instance
				$trigger = ( new ReflectionClass( $trigger_class ) )->newInstance();

				// append to list
				$this->triggers_list[ $trigger->activity_trigger() ] = $trigger;
			}
		}

		return $this->triggers_list;
	}

	/**
	 * Get completed percentage of the given step
	 *
	 * @param int $step_id
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function get_step_completed_percentage( $step_id, $user_id = null )
	{
		$step_type = $this->get_step_type( $step_id );
		if ( !isset( $this->triggers_list[ $step_type ] ) )
		{
			// step not in the additional type
			return 0;
		}

		if ( null === $user_id )
		{
			// current logged in user ID
			$user_id = get_current_user_id();
		}

		return $this->triggers_list[ $step_type ]->get_step_percentage( $step_id, $user_id );
	}

	/**
	 * Get achievement step trigger type
	 *
	 * @param int $step_id
	 *
	 * @return string
	 */
	public function get_step_type( $step_id )
	{
		return get_post_meta( $step_id, '_badgeos_trigger_type', true );
	}

	/**
	 * Get step data
	 *
	 * @param int    $step_id
	 * @param string $step_type
	 *
	 * @return array|bool
	 */
	public function get_step_data( $step_id, $step_type = '' )
	{
		$trigger = $this->get_step_trigger_object( $step_id, $step_type );
		if ( false === $trigger )
		{
			// unknown trigger type
			return false;
		}

		return $trigger->get_data( $step_id, $step_type );
	}

	/**
	 * Check if given step is checklist trigger type of not
	 *
	 * @param int    $step_id
	 * @param string $step_type
	 *
	 * @return bool
	 */
	public function is_checklist_step( $step_id, $step_type = '' )
	{
		$trigger = $this->get_step_trigger_object( $step_id, $step_type );
		if ( false === $trigger || false === method_exists( $trigger, 'is_checklist_step' ) )
		{
			// wrong step type
			return false;
		}

		return $trigger->is_checklist_step( $step_id );
	}

	/**'
	 * Get given step trigger object
	 *
	 * @param        $step_id
	 * @param string $step_type
	 *
	 * @return Trigger_Interface|boolean
	 */
	public function get_step_trigger_object( $step_id, $step_type = '' )
	{
		$step_type = '' === $step_type || empty( $step_type ) ? $this->get_step_type( $step_id ) : $step_type;

		$triggers = $this->get_triggers();
		if ( !isset( $triggers[ $step_type ] ) )
		{
			// unknown step type
			return false;
		}

		// step type object
		return $triggers[ $step_type ];
	}

	/**
	 * Query checklist marks
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_checklist_marks( $args )
	{
		global $wpdb;

		// default args
		$args = wp_parse_args( $args, [
			'user_id'  => 0,
			'step_id'  => 0,
			'badge_id' => 0,
			'before'   => '',
			'after'    => '',
		] );

		// base sql
		$sql_stmt = "SELECT * FROM {$wpdb->checklist_marks} WHERE 1 = 1";
		$sql_vars = [];

		foreach ( $args as $arg_name => $arg_value )
		{
			switch ( $arg_name )
			{
				// count for specific IDs
				case 'user_id':
				case 'step_id':
				case 'badge_id':
					if ( 0 !== $arg_value )
					{
						$sql_stmt   .= " AND {$arg_name} = %d";
						$sql_vars[] = $arg_value;
					}
					break;

				case 'before':
					if ( '' !== $arg_value || !empty( $arg_value ) )
					{
						$sql_stmt   .= " AND mark_datetime < %s";
						$sql_vars[] = $arg_value;
					}
					break;

				case 'after':
					if ( '' !== $arg_value || !empty( $arg_value ) )
					{
						$sql_stmt   .= " AND mark_datetime > %s";
						$sql_vars[] = $arg_value;
					}
					break;
			}
		}

		/**
		 * Filter checklist marks query SQL statement
		 *
		 * @param string $sql_stmt
		 * @param array  $args
		 *
		 * @return string
		 */
		$sql_stmt = apply_filters( 'trbs_checklist_marks_query_sql', $sql_stmt, $args );

		/**
		 * Filter checklist marks query SQL variables
		 *
		 * @param string $sql_vars
		 * @param array  $args
		 *
		 * @return array
		 */
		$sql_vars = apply_filters( 'trbs_checklist_marks_query_vars', $sql_vars, $args );

		// execute query
		$results = $wpdb->get_results( $wpdb->prepare( $sql_stmt, $sql_vars ) );
		// dd( $wpdb->last_query );

		/**
		 * Filter checklist marks query results
		 *
		 * @param array $results
		 * @param array $args
		 *
		 * @return array
		 */
		return apply_filters( 'trbs_checklist_marks_query_results', $results, $args );
	}

	/**
	 * Update given checklist point mark
	 *
	 * @param array $mark_args
	 *
	 * @return WP_Error|boolean
	 */
	public function update_checklist_mark( $mark_args )
	{
		global $wpdb;

		// get checklist point last mark
		$mark_id = $this->get_checklist_mark( $mark_args );

		if ( null === $mark_id && $mark_args['checked'] )
		{
			/**
			 * Filter new checklist mark fields' data
			 *
			 * @param array $mark_fields
			 *
			 * @return array
			 */
			$mark_fields = apply_filters( 'trbs_checklist_mark_fields', [
				'user_id'       => $mark_args['user'],
				'point_id'      => $mark_args['point'],
				'step_id'       => $mark_args['step'],
				'badge_id'      => $mark_args['badge'],
				'mark_datetime' => current_time( 'mysql', true ),
			] );

			// add/insert check
			if ( false === $wpdb->insert( $wpdb->checklist_marks, $mark_fields, [ '%d', '%d', '%d', '%d', '%s' ] ) )
			{
				// DB error trying to add the new mark
				return new WP_Error( 'trbs_error_adding_mark', __( 'Error adding checklist mark!', TRBS_DOMAIN ) );
			}

			$mark_id = $wpdb->insert_id;

			/**
			 * New challenges checklist mark added
			 *
			 * @param int   $mark_id
			 * @param array $mark_fields
			 */
			do_action( 'trbs_checklist_mark_added', $mark_id, $mark_fields );
		}

		if ( null !== $mark_id && false === $mark_args['checked'] )
		{
			// remove/delete mark
			$delete_mark = $wpdb->delete( $wpdb->checklist_marks, [ 'mark_id' => $mark_id ], [ '%d' ] );
			if ( false === $delete_mark || 0 === $delete_mark )
			{
				// DB error trying to delete the mark
				return new WP_Error( 'trbs_error_removing_mark', __( 'Error removing checklist mark!', TRBS_DOMAIN ) );
			}

			/**
			 * Challenges checklist mark removed
			 *
			 * @param int $mark_id
			 */
			do_action( 'trbs_checklist_mark_removed', $mark_id );
		}

		return true;
	}

	/**
	 * Get checklist point last mark
	 *
	 * @param array $mark_args
	 *
	 * @return null|string|WP_Error
	 */
	public function get_checklist_mark( $mark_args )
	{
		global $wpdb;

		// query badge info
		$badge = $this->get_badge( $mark_args['badge'] );
		if ( is_wp_error( $badge ) )
		{
			// badge error!
			return $badge;
		}

		// Grab our Badge's required steps
		$badge_steps = $this->get_badge_steps( $badge, true );
		if ( 0 === count( $badge_steps ) )
		{
			// badge doesn't have any steps!
			return new WP_Error( 'trbs_badge_has_no_steps', __( 'Badge has not steps!', TRBS_DOMAIN ) );
		}

		// get the target step
		$target_step = null;
		foreach ( $badge_steps as $badge_step )
		{
			if ( $badge_step->ID === $mark_args['step'] )
			{
				$target_step = $badge_step;
				break;
			}
		}

		if ( null === $target_step )
		{
			// step not found!
			return new WP_Error( 'trbs_step_not_found', __( 'Badge step not found!', TRBS_DOMAIN ) );
		}

		if (
			!isset( $target_step->step_data ) ||
			!isset( $target_step->step_data['challenges_checklist'] ) ||
			!isset( $target_step->step_data['challenges_checklist'][ $mark_args['point'] ] )
		)
		{
			// checklist point not found!
			return new WP_Error( 'trbs_checklist_point_not_found', __( 'Checklist point not foudn!', TRBS_DOMAIN ) );
		}

		// last mark for that point
		$mark_sql  = "SELECT mark_id FROM $wpdb->checklist_marks WHERE user_id = %d AND point_id = %d AND step_id = %d AND badge_id = %d";
		$mark_vars = [ $mark_args['user'], $mark_args['point'], $mark_args['step'], $mark_args['badge'] ];

		// last time user earned that step
		$last_earning = $this->get_last_badge_earning( $mark_args['step'], $mark_args['user'] );
		if ( false !== $last_earning && isset( $last_earning->date_earned ) )
		{
			// get mark after the last earning datetime
			$mark_sql    .= " AND mark_datetime > %s";
			$mark_vars[] = date( 'Y-m-d H:i:s', $last_earning->date_earned );
		}

		// order by datetime descending
		$mark_sql .= " ORDER BY mark_datetime DESC LIMIT 1";

		// execute SQL
		$mark_id = $wpdb->get_var( $wpdb->prepare( $mark_sql, $mark_vars ) );

		/**
		 * Filter queried checklist point mark
		 *
		 * @param null|string $mark_id
		 * @param array       $mark_args
		 *
		 * @return null|string
		 */
		return apply_filters( 'trbs_checklist_point_last_mark', $mark_id, $mark_args );
	}

	/**
	 * Get last time the given user earned given achievement
	 *
	 * @param int|array $achievement_id
	 * @param int       $user_id
	 *
	 * @return stdClass|bool
	 */
	public function get_last_badge_earning( $achievement_id, $user_id = 0 )
	{
		$last_earning = null;
		$earnings     = badgeos_get_user_achievements( [
			'user_id'        => $user_id,
			'achievement_id' => $achievement_id,
		] );

		if ( !isset( $earnings[0] ) )
		{
			// user earned nothing like that yet
			return false;
		}

		$earnings_count = count( $earnings );
		if ( 1 === $earnings_count )
		{
			// just one time earning
			$last_earning = $earnings[0];
		}

		if ( null === $last_earning )
		{
			// sort object by earn date descending
			usort( $earnings, function ( $a, $b )
			{
				return $a->date_earned < $b->date_earned ? 1 : -1;
			} );

			// get the latest one
			$last_earning = array_shift( $earnings );
		}

		// format earn date
		$last_earning->date_earned_formatted = date( 'M j, Y', $last_earning->date_earned );

		// append earnings count
		$last_earning->earn_count = $earnings_count;

		return $last_earning;
	}

	/**
	 * Grab given Badge's required steps
	 *
	 * @param WP_Post $badge
	 * @param boolean $with_extra
	 *
	 * @return array
	 */
	public function get_badge_steps( $badge, $with_extra = false )
	{
		$required_steps = get_posts( [
			'post_type'           => 'step',
			'posts_per_page'      => -1,
			'suppress_filters'    => false,
			'connected_direction' => 'to',
			'connected_type'      => 'step-to-' . $badge->post_type,
			'connected_items'     => $badge->ID,
		] );

		// Loop through steps
		foreach ( $required_steps as $step_index => $required_step )
		{
			if ( !isset( $required_step->p2p_to ) || $badge->ID !== absint( $required_step->p2p_to ) )
			{
				// step is not for the badge
				unset( $required_steps[ $step_index ] );
				continue;
			}

			// set sort order
			$required_step->order = get_step_menu_order( $required_step->ID );

			if ( $with_extra )
			{
				// step trigger object
				$required_step->trigger = $this->get_step_trigger_object( $required_step->ID );

				// step data
				$required_step->step_data = $this->get_step_data( $required_step->ID );
			}
		}

		// Sort the steps by their order
		uasort( $required_steps, 'badgeos_compare_step_order' );

		/**
		 * Filter badge required steps
		 *
		 * @param array   $required_steps
		 * @param WP_Post $badge
		 *
		 * @return array
		 */
		return apply_filters( 'trbs_required_badge_steps', $required_steps, $badge );
	}

	/**
	 * Get badge object
	 *
	 * @param int $badge_id
	 *
	 * @return WP_Error|WP_Post
	 */
	public function get_badge( $badge_id )
	{
		$badge = get_post( $badge_id );
		if ( null === $badge )
		{
			// badge not found
			return new WP_Error( 'trbs_badge_not_found', __( 'Badge not found!', TRBS_DOMAIN ) );
		}

		if ( 'badges' !== $badge->post_type || 'publish' !== $badge->post_status )
		{
			// invalid badge post type
			return new WP_Error( 'trbs_invalid_badge', __( 'Invalid badge!', TRBS_DOMAIN ) );
		}

		return $badge;
	}

	/**
	 * Get registered badge types
	 *
	 * @return array
	 */
	public function get_badge_types()
	{
		return apply_filters( 'trbs_badge_types', [
			[
				'name'        => __( 'Explorer', TRBS_DOMAIN ),
				'value'       => 'explorer',
				'filter_name' => __( 'Explorer Badges', TRBS_DOMAIN ),
			],
			[
				'name'        => __( 'Challenge', TRBS_DOMAIN ),
				'value'       => 'challenge',
				'filter_name' => __( 'Challenge Badges', TRBS_DOMAIN ),
			],
			[
				'name'        => __( 'Milestone', TRBS_DOMAIN ),
				'value'       => 'milestone',
				'filter_name' => __( 'Milestone Badges', TRBS_DOMAIN ),
			],
		] );
	}

	/**
	 * Get selected suggestion form information
	 *
	 * @return WP_Error|array
	 */
	public function get_suggestion_form()
	{
		if ( false === class_exists( 'GFAPI' ) )
		{
			// GForms is missing
			return new WP_Error( 'trbs_gform_missing', __( 'Gravity Forms not installed or active!', TRBS_DOMAIN ) );
		}

		$form_id = absint( get_option( $this->get_suggestion_form_option_name(), 0 ) );
		if ( 0 === $form_id )
		{
			// form not set/selected
			return new WP_Error( 'trbs_suggestion_form_not_set', __( 'Suggestion form not set!', TRBS_DOMAIN ) );
		}

		$form = \GFAPI::get_form( $form_id );
		if ( false === $form )
		{
			// invalid form
			return new WP_Error( 'trbs_suggestion_form_invalid', __( 'Invalid suggestion form!', TRBS_DOMAIN ) );
		}

		// check for missing fields
		$required_fields = array_filter( $form['fields'], function ( $field )
		{
			/* @var $field \GF_Field */
			if ( 'hidden' !== $field->get_input_type() )
			{
				// not hidden field
				return false;
			}

			if ( 'trbs_listing_id' === $field->label || 'trbs_badge_id' === $field->label )
			{
				// is the target one
				return true;
			}

			// unrelated field
			return false;
		} );

		if ( 2 !== count( $required_fields ) )
		{
			// missing required fields
			return new WP_Error( 'trbs_suggestion_fields_missing', __( 'Missing required fields in the suggestion form! <code>listing_id</code> or <code>badge_id</code>', TRBS_DOMAIN ) );
		}

		return $form;
	}

	/**
	 * @return string
	 */
	public function get_suggestion_form_option_name()
	{
		return $this->suggestion_form_option_name;
	}
}

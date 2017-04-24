<?php namespace True_Resident\Badge_System;

use WP_Post;
use WP_Query;

/**
 * Frontend logic
 *
 * @package True_Resident\Badge_System
 */
class Frontend extends Component
{
	/**
	 * Badges popover args
	 *
	 * @var array
	 */
	public $popover_args;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// BadgeOS achievement render
		add_filter( 'badgeos_render_achievement', [ &$this, 'badge_render_output' ], 10, 2 );

		// WP Styles printing action hook
		add_action( 'wp_enqueue_scripts', [ &$this, 'badgeos_achievements_list_styling' ] );

		// vars
		$this->popover_args = [
			'data-trigger'   => 'hover',
			'data-placement' => 'top',
			'data-width'     => '240',
			'data-closeable' => 'true',
		];

		if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() )
		{
			// mobile request or not
			$this->popover_args['data-trigger']   = 'click';
			$this->popover_args['data-placement'] = 'auto-top';
			$this->popover_args['data-width']     = '200';
		}

		// Badges list pre-query
		add_action( 'pre_get_posts', [ &$this, 'badgeos_query_list_all' ] );
	}

	/**
	 * Load all badges without pagination
	 *
	 * @param WP_Query $query
	 *
	 * @return void
	 */
	public function badgeos_query_list_all( $query )
	{
		$post_type = $query->get( 'post_type' );
		if ( is_string( $post_type ) )
		{
			// wrap in array
			$post_type = [ $post_type ];
		}

		if ( !in_array( 'badges', $post_type ) || !defined( 'DOING_AJAX' ) || !DOING_AJAX )
		{
			// skip un-related query
			return;
		}

		$per_page = $query->get( 'posts_per_page' );
		if ( $per_page > 0 || $query->get( 'trbs_listing_query' ) )
		{
			// skip un-related query
			return;
		}

		// disable pagination
		$query->set( 'nopaging', true );

		// looking for specific type of badges 
		$selected_type = isset( $_REQUEST['filter'] ) ? sanitize_key( $_REQUEST['filter'] ) : null;
		if ( null !== $selected_type && !empty( $selected_type ) )
		{
			// look for the selected type info
			$selected_type = array_filter( trbs_rewards()->get_badge_types(), function ( $badge_type ) use ( $selected_type )
			{
				return $badge_type['value'] === $selected_type;
			} );

			if ( count( $selected_type ) > 0 )
			{
				$selected_type = array_shift( $selected_type );

				// if found then filter badge based on it
				$meta_query = $query->get( 'meta_query' );
				if ( '' === $meta_query || !is_array( $meta_query ) )
				{
					// initialize array
					$meta_query = [];
				}

				// update meta query
				$meta_query[] = [
					'key'     => '_badgeos_badge_type',
					'value'   => $selected_type['value'],
					'compare' => '=',
				];
				$query->set( 'meta_query', $meta_query );
			}
		}

		// check for related badges for a specific listing
		$listing_id = isset( $_REQUEST['trbs_listing_id'] ) ? absint( $_REQUEST['trbs_listing_id'] ) : null;
		if ( null === $listing_id || 0 === $listing_id )
		{
			// skip invalid passed listing ID
			return;
		}

		// vars
		$listing_badges = trbs_rewards()->get_listings_badges( $listing_id );
		$hidden_badges  = trbs_backend()->get_hidden_badges();

		if ( isset( $hidden_badges[0] ) )
		{
			// exclude from list hidden badges from loading in the listing singular page
			$listing_badges = array_values( array_diff( $listing_badges, $hidden_badges ) );
		}

		if ( empty( $listing_badges ) )
		{
			// if all badges are hidden, then force stop loading any other badges
			$listing_badges = [ 0 ];
		}

		// load only these two badges
		$query->set( 'post__in', $listing_badges );
	}

	/**
	 * Enqueue achievements extra styling when the main one
	 *
	 * @return void
	 */
	public function badgeos_achievements_list_styling()
	{
		if ( !wp_script_is( 'badgeos-achievements', 'enqueued' ) )
		{
			// skip un-related content
			return;
		}

		// assets base path
		$base_path      = Helpers::enqueue_path();
		$assets_version = Helpers::assets_version();

		// WebUI Poppver
		wp_register_style( 'trbs-webui-popover', $base_path . 'css/jquery.webui-popover.css' );
		wp_register_script( 'trbs-webui-popover', $base_path . 'js/jquery.webui-popover.js', [ 'jquery' ], '1.2.16', true );

		// doT template engine
		wp_register_script( 'trbs-dot-engine', $base_path . 'js/doT.js', null, '1.0.3', true );

		// jQuety Livequery
		wp_register_script( 'trbs-livequery', $base_path . 'js/jquery.livequery.js', [ 'jquery' ], '1.3.6', true );

		// enqueue badges script and style
		wp_enqueue_style( 'trbs-achievements', $base_path . 'css/achievements.css', [
			'badgeos-front',
			'trbs-webui-popover',
		], $assets_version );
		wp_enqueue_script( 'trbs-achievements', $base_path . 'js/achievements.js', [
			'trbs-webui-popover',
			'trbs-livequery',
			'trbs-dot-engine',
		], $assets_version, false );

		wp_localize_script( 'trbs-achievements', 'trbs_badges', [
			'badge_filters'   => array_merge( [
				[ 'filter_name' => __( 'All Badges', TRBS_DOMAIN ), 'value' => 'all' ],
				[ 'filter_name' => __( 'Completed', TRBS_DOMAIN ), 'value' => 'completed' ],
				[ 'filter_name' => __( 'Uncompleted', TRBS_DOMAIN ), 'value' => 'not-completed' ],
			], trbs_rewards()->get_badge_types() ),
			'is_mobile'       => wp_is_mobile(),
			'is_logged_in'    => is_user_logged_in(),
			'checklist_nonce' => wp_create_nonce( 'trbs_challenges_checklist_change' ),
		] );
	}

	/**
	 * BadgeOS achievement updated render
	 *
	 * @param string $output
	 * @param int    $badge_id
	 *
	 * @return string
	 */
	public function badge_render_output( $output, $badge_id )
	{
		// vars
		$user_id        = get_current_user_id();
		$badge          = get_post( $badge_id );
		$multi_earnings = -1 === (int) $badge->_badgeos_maximum_earnings;
		$has_challenges = false;
		$steps_data     = [];

		// check if user has earned this Achievement, and add an 'earned' class
		$last_earning  = trbs_rewards()->get_last_badge_earning( $badge_id, $user_id );
		$is_earned     = false !== $last_earning;
		$earned_status = $is_earned ? 'user-has-earned' : 'user-has-not-earned';

		$css_classes = [
			'badgeos-achievements-list-item',
			$earned_status,
		];

		// credly API
		$credly_ID = '';

		// If the achievement is earned and givable, override our credly classes
		if ( 'user-has-earned' === $earned_status && $giveable = credly_is_achievement_giveable( $badge_id, $user_id ) )
		{
			$css_classes = array_merge( $css_classes, [ 'share-credly', 'addCredly' ] );
			$credly_ID   = 'data-credlyid="' . $badge_id . '"';
		}

		// badge steps
		$steps           = badgeos_get_required_achievements_for_achievement( $badge_id );
		$steps_count     = count( $steps );
		$steps_completed = 0;

		for ( $i = 0; $i < $steps_count; $i++ )
		{
			// vars
			$step_id         = $steps[ $i ]->ID;
			$step_type       = trbs_rewards()->get_step_type( $step_id );
			$step_completed  = count( badgeos_get_user_achievements( [
					'user_id'        => $user_id,
					'achievement_id' => $step_id,
					'since'          => absint( badgeos_achievement_last_user_activity( $badge_id, $user_id ) ),
				] ) ) > 0;
			$steps_completed += $step_completed ? 100 : trbs_rewards()->get_step_completed_percentage( $step_id );

			if ( false === $has_challenges )
			{
				// check if badge has challenges checklist step or not
				$has_challenges = trbs_rewards()->is_checklist_step( $step_id, $step_type );
			}

			// get step data
			$steps_data[ $step_id ] = trbs_rewards()->get_step_data( $step_id, $step_type );

			// clear un-wanted data
			unset( $steps_data[ $step_id ]['checklist_max_index'] );

			// with step title
			$steps_data[ $step_id ]['title'] = $steps[ $i ]->post_title;
		}

		// clear
		unset( $step_id, $step_type, $step_completed );

		// overall percentage ( positive and 100% max )
		$earned_percentage = abs( round( $steps_completed ? $steps_completed / $steps_count : 0 ) );
		$earned_percentage = $earned_percentage > 100 ? 100 : $earned_percentage;

		if ( $has_challenges )
		{
			// Challenges badge mark
			$css_classes[] = 'badgeos-achievements-challenges-item';

			foreach ( $steps_data as $step_id => &$step_data )
			{
				if ( !isset( $step_data['challenges_checklist'] ) )
				{
					// skip
					continue;
				}

				$step_data['challenges_checklist_marks'] = [];

				// get points' marks
				$points_indexes = array_keys( $step_data['challenges_checklist'] );
				foreach ( $points_indexes as $point_id )
				{
					$step_data['challenges_checklist_marks'][ $point_id ] = null !== trbs_rewards()->get_checklist_mark( [
							'badge' => $badge_id,
							'step'  => $step_id,
							'point' => $point_id,
							'user'  => $user_id,
						] );
				}
			}

			// clear
			unset( $step_id );
		}

		// buffer start
		ob_start();

		// Achievement Content
		$popover_content = '<div id="badgeos-achievements-item-description-' . $badge_id . '" class="badgeos-item-description">';

		// Achievement Title
		$popover_content .= '<h2 class="badgeos-item-title">' . get_the_title( $badge ) . '</h2>';

		// Achievement Short Description
		$excerpt         = '' === $badge->post_excerpt || empty( $badge->post_excerpt ) ? $badge->post_content : $badge->post_excerpt;
		$popover_content .= '<div class="badgeos-item-excerpt">' . wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
		$popover_content .= '<span class="badgeos-percentage"><span class="badgeos-percentage-bar" style="width: ' . $earned_percentage . '%;"></span>';
		$popover_content .= '<span class="badgeos-percentage-number">' . $earned_percentage . '&percnt;</span></span>';

		if ( $is_earned && isset( $last_earning->date_earned ) )
		{
			// earn date
			$popover_content .= '<span class="badgeos-earning">';

			if ( $multi_earnings && isset( $last_earning->earn_count ) )
			{
				// earn count
				$popover_content .= sprintf( __( 'Earned <span class="badgeos-earning-count">%d</span> %s<br/>', TRBS_DOMAIN ), $last_earning->earn_count, _n( 'time', 'times', $last_earning->earn_count, TRBS_DOMAIN ) );
			}

			$popover_content .= $multi_earnings ? __( 'Last earned on', TRBS_DOMAIN ) : __( 'Earned on', TRBS_DOMAIN );
			$popover_content .= ' <span class="badgeos-earning-date">' . $last_earning->date_earned_formatted . '</span>';
			$popover_content .= '</span>';
		}

		$popover_content .= '</div><!-- .badgeos-item-excerpt --></div><!-- .badgeos-item-description -->';

		// Each Achievement
		echo '<a href="javascript:void(0)" id="badgeos-achievements-list-item-', $badge_id, '" data-id="', $badge_id, '" ',
		'data-content="', esc_attr( $popover_content ), '" ', Helpers::parse_attributes( $this->popover_args ),
		'class="', implode( ' ', $css_classes ), '"', $credly_ID,
		' data-steps-data="', esc_attr( json_encode( $steps_data ) ), '" data-completed="', $earned_percentage, '" data-last-earning="', esc_attr( json_encode( $last_earning ) ), '">';
		// Achievement Image
		echo '<span class="badgeos-item-image">', badgeos_get_achievement_post_thumbnail( $badge ), '</span></a><!-- .badgeos-achievements-list-item -->';

		return ob_get_clean();
	}

	/**
	 * Render challenges suggestions form
	 *
	 * @param WP_Post $listing
	 * @param WP_Post $badge
	 *
	 * @return void
	 */
	public function render_activities_suggestion_form( $listing, $badge )
	{
		// title
		echo '<h2 class="popup-title">', sprintf( __( 'Suggestions for "%s"', TRBS_DOMAIN ), $listing->post_title ), '</h2>';

		$form = trbs_rewards()->get_suggestion_form();
		if ( is_wp_error( $form ) )
		{
			// error loading form
			echo $form->get_error_message();

			return;
		}

		// set global ids
		$_GET['trbs_listing_id'] = $listing->ID;
		$_GET['trbs_badge_id']   = $badge->ID;

		// render form's shortcode
		echo do_shortcode( '[gravityform id="' . $form['id'] . '" title="true" description="false" ajax="true"]' );
	}
}

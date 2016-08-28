<?php namespace True_Resident\Badge_System;

use WP_Query;

/**
 * Frontend logic
 *
 * @package True_Resident\Badge_System
 */
class Frontend extends Component
{
	/**
	 * Bagdes popover trigger
	 *
	 * @var string
	 */
	var $popover_trigger;

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

		// mobile request or not
		$this->popover_trigger = function_exists( 'wp_is_mobile' ) ? ( wp_is_mobile() ? 'click' : 'hover' ) : 'click';

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

		$query->set( 'nopaging', true );

		// check for related badges for a specific listing
		$listing_id = isset( $_REQUEST['trbs_listing_id'] ) ? absint( $_REQUEST['trbs_listing_id'] ) : null;
		if ( null === $listing_id || 0 === $listing_id )
		{
			// skip invalid passed listing ID
			return;
		}

		// TEMP: load only these two badges
		$query->set( 'post__in', trbs_rewards()->get_listings_badges( $listing_id ) );
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
		$base_path = Helpers::enqueue_path();

		// WebUI Poppver
		wp_register_style( 'trbs-webui-popover', $base_path . 'css/jquery.webui-popover.css' );
		wp_register_script( 'trbs-webui-popover', $base_path . 'js/jquery.webui-popover.js', [ 'jquery' ], '1.2.12', true );

		// jQuety Livequery
		wp_register_script( 'trbs-livequery', $base_path . 'js/jquery.livequery.js', [ 'jquery' ], '1.3.6', true );

		// enqueue badges script and style
		wp_enqueue_style( 'trbs-achievements', $base_path . 'css/achievements.css', [
			'badgeos-front',
			'trbs-webui-popover',
		], trbs_version() );
		wp_enqueue_script( 'trbs-achievements', $base_path . 'js/achievements.js', [
			'trbs-webui-popover',
			'trbs-livequery',
		], trbs_version(), false );
	}

	/**
	 * BadgeOS achievement updated render
	 *
	 * @param string $output
	 * @param int    $achievement_id
	 *
	 * @return string
	 */
	public function badge_render_output( $output, $achievement_id )
	{
		// vars
		$user_id     = get_current_user_id();
		$achievement = get_post( $achievement_id );

		// check if user has earned this Achievement, and add an 'earned' class
		$is_earned         = count( badgeos_get_user_achievements( [
				'user_id'        => $user_id,
				'achievement_id' => $achievement_id,
			] ) ) > 0;
		$earned_status     = $is_earned ? 'user-has-earned' : 'user-has-not-earned';
		$earned_percentage = $is_earned ? 100 : 0;

		$css_classes = [
			'badgeos-achievements-list-item',
			$earned_status,
		];

		// credly API
		$credly_ID = '';

		// If the achievement is earned and givable, override our credly classes
		if ( 'user-has-earned' == $earned_status && $giveable = credly_is_achievement_giveable( $achievement_id, $user_id ) )
		{
			$css_classes[] = 'share-credly addCredly';
			$credly_ID     = 'data-credlyid="' . $achievement_id . '"';
		}

		// buffer start
		ob_start();

		if ( false === $is_earned )
		{
			// badge steps
			$steps            = badgeos_get_required_achievements_for_achievement( $achievement_id );
			$steps_count      = count( $steps );
			$steps_percentage = $steps_count * 100;
			$steps_completed  = 0;

			for ( $i = 0; $i < $steps_count; $i++ )
			{
				// vars
				$step_id        = $steps[ $i ]->ID;
				$step_completed = count( badgeos_get_user_achievements( [
						'user_id'        => $user_id,
						'achievement_id' => $step_id,
						'since'          => absint( badgeos_achievement_last_user_activity( $achievement_id, $user_id ) ),
					] ) ) > 0;

				$steps_completed += $step_completed ? 100 : trbs_rewards()->get_step_completed_percentage( $step_id );
			}

			$earned_percentage = round( $steps_completed ? $steps_completed / $steps_count : 0 );
		}

		// Achievement Content
		$popover_content = '<div id="badgeos-achievements-item-description-' . $achievement_id . '" class="badgeos-item-description">';

		// Achievement Title
		$popover_content .= '<h2 class="badgeos-item-title">' . get_the_title( $achievement ) . '</h2>';

		// Achievement Short Description
		$excerpt = '' === $achievement->post_excerpt || empty( $achievement->post_excerpt ) ? $achievement->post_content : $achievement->post_excerpt;
		$popover_content .= '<div class="badgeos-item-excerpt">' . wpautop( apply_filters( 'get_the_excerpt', $excerpt ) );
		$popover_content .= '<span class="badgeos-percentage"><span class="badgeos-percentage-bar" style="width: ' . $earned_percentage . '%;"></span>';
		$popover_content .= '<span class="badgeos-percentage-number">' . $earned_percentage . '&percnt;</span>';
		$popover_content .= '</span></div><!-- .badgeos-item-description --></div><!-- .badgeos-item-description -->';

		// Each Achievement
		echo '<a href="javascript:void(0)" id="badgeos-achievements-list-item-', $achievement_id, '" ',
		'data-trigger="', $this->popover_trigger, '" data-content="', esc_attr( $popover_content ), '" data-placement="top" data-width="240" ',
		'class="', implode( ' ', $css_classes ), '"', $credly_ID, '>';
		// Achievement Image
		echo '<span class="badgeos-item-image">', badgeos_get_achievement_post_thumbnail( $achievement ), '</span></a><!-- .badgeos-achievements-list-item -->';

		return ob_get_clean();
	}
}

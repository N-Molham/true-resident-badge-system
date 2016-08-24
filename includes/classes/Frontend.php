<?php namespace True_Resident\Badge_System;

/**
 * Frontend logic
 *
 * @package True_Resident\Badge_System
 */
class Frontend extends Component
{
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

		// Each Achievement
		echo '<div id="badgeos-achievements-list-item-', $achievement_id, '" class="', implode( ' ', $css_classes ), '"', $credly_ID, '>';

		// Achievement Image
		echo '<div class="badgeos-item-image">', badgeos_get_achievement_post_thumbnail( $achievement ), '</div>';

		// Achievement Content
		echo '<div class="badgeos-item-description">';

		// Achievement Title
		echo '<h2 class="badgeos-item-title">', get_the_title( $achievement ), '</h2>';

		// Achievement Short Description
		$excerpt = !empty( $achievement->post_excerpt ) ? $achievement->post_excerpt : $achievement->post_content;
		echo '<div class="badgeos-item-excerpt">',
		'<span class="badgeos-percentage">', $earned_percentage, '&percnt;</span>',
		wpautop( apply_filters( 'get_the_excerpt', $excerpt ) ), '</div>';

		echo '</div><!-- .badgeos-item-description -->',
		'</div><!-- .badgeos-achievements-list-item -->';

		return ob_get_clean();
	}
}

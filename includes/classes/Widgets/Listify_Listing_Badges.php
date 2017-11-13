<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 28-Aug-16
 * Time: 5:07 PM
 */

namespace True_Resident\Badge_System\Widgets;

use True_Resident\Badge_System\Helpers;
use WP_Widget;

/**
 * Class Listify Listing Rewards
 *
 * @package True_Resident\Badge_System\Widgets
 */
class Listify_Listing_Badges extends WP_Widget {
	/**
	 * Sets up the widgets name etc
	 *
	 * @return Listify_Listing_Badges
	 */
	public function __construct() {
		$widget_ops = [
			'classname'   => 'trbs_listing_rewards',
			'description' => __( 'Display the current opened listing\'s related badges which can be earned by unlocking that listing.', TRBS_DOMAIN ),
		];

		parent::__construct( 'trbs_listing_rewards', __( 'True Resident Listings Badges', TRBS_DOMAIN ), $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->default_options() );

		// widget before layout
		echo $args['before_widget'];

		if ( '' !== $instance['title'] || ! empty( $instance['title'] ) ) {
			// Only show the title if there actually is a title
			echo $args['before_title'], esc_attr( $instance['title'] ), $args['after_title'];
		}

		$badges_shortcode = '[badgeos_achievements_list type="badges" wpms="false" ';
		$badges_shortcode .= 'limit="' . $instance['limit'] . '" orderby="' . $instance['order_by'] . '" order="' . $instance['order'] . '" ]';

		echo do_shortcode( $badges_shortcode );

		// Challenges checklist template
		trbs_view( 'frontend/badges/challenges' );

		// widget after layout
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->default_options() );

		echo '<p><label for="', esc_attr( $this->get_field_id( 'title' ) ), '">', __( 'Widget Title', TRBS_DOMAIN ), ',</label>',
		'<input type="text" class="widefat" id="', esc_attr( $this->get_field_id( 'title' ) ), '" ',
		'name="', esc_attr( $this->get_field_name( 'title' ) ), '" value="', esc_attr( $instance['title'] ), '">',
		'</p>';

		echo '<p><label for="', esc_attr( $this->get_field_id( 'order_by' ) ), '">', __( 'Order By', TRBS_DOMAIN ), '</label>',
		'<select class="widefat" id="', esc_attr( $this->get_field_id( 'order_by' ) ), '" name="', esc_attr( $this->get_field_name( 'order_by' ) ), '">',
		Helpers::array_to_options( $this->get_order_by_options(), $instance['order_by'] ),
		'</select></p>';

		echo '<p><label for="', esc_attr( $this->get_field_id( 'order' ) ), '">', __( 'Order', TRBS_DOMAIN ), '</label>',
		'<select class="widefat" id="', esc_attr( $this->get_field_id( 'order' ) ), '" name="', esc_attr( $this->get_field_name( 'order' ) ), '">',
		Helpers::array_to_options( [
			'ASC'  => __( 'Ascending', TRBS_DOMAIN ),
			'DESC' => __( 'Descending', TRBS_DOMAIN ),
		], $instance['order'] ),
		'</select></p>';

		echo '<p><label for="', esc_attr( $this->get_field_id( 'limit' ) ), '">', __( 'Limit', TRBS_DOMAIN ), ',</label>',
		'<input type="number" class="widefat" id="', esc_attr( $this->get_field_id( 'limit' ) ), '" ',
		'name="', esc_attr( $this->get_field_name( 'limit' ) ), '" value="', esc_attr( $instance['limit'] ), '">',
		'</p>';
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$order_bys = array_values( $this->get_order_by_options() );

		$old_instance['title']    = sanitize_text_field( $new_instance['title'] );
		$old_instance['order_by'] = in_array( $new_instance['order_by'], $order_bys, true ) ? $new_instance['order_by'] : array_shift( $order_bys );
		$old_instance['order']    = 'ASC' === $new_instance['order'] || 'DESC' === $new_instance['order'] ? $new_instance['order'] : 'ASC';
		$old_instance['limit']    = absint( $new_instance['limit'] );

		return $old_instance;
	}

	/**
	 * Badges list Order by options
	 *
	 * @return array
	 */
	public function get_order_by_options() {
		return [
			'menu_order' => __( 'Menu Order', TRBS_DOMAIN ),
			'ID'         => __( 'ID', TRBS_DOMAIN ),
			'title'      => __( 'Title', TRBS_DOMAIN ),
			'date'       => __( 'Published Date', TRBS_DOMAIN ),
			'modified'   => __( 'Last Modified Date', TRBS_DOMAIN ),
			'author'     => __( 'Author', TRBS_DOMAIN ),
			'rand'       => __( 'Random', TRBS_DOMAIN ),
		];
	}

	/**
	 * Widget Default options
	 *
	 * @return array
	 */
	public function default_options() {
		return [
			'title'    => __( 'Listing Badges', TRBS_DOMAIN ),
			'order_by' => 'menu_order',
			'order'    => 'ACS',
			'limit'    => 10,
		];
	}
}
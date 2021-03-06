<?php namespace True_Resident\Badge_System;

/**
 * AJAX handler
 *
 * @package True_Resident\Badge_System
 */
class Ajax_Handler extends Component {

	/**
	 * List of public ajax requests that without login status
	 *
	 * @var array
	 */
	protected $public_requests;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init() {

		parent::init();

		$this->public_requests = [ 'activity_suggestion_form' ];

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$action = filter_var( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '', FILTER_SANITIZE_STRING );
			if ( method_exists( $this, $action ) ) {
				// hook into action if it's method exists
				add_action( 'wp_ajax_' . $action, [ $this, $action ] );

				if ( in_array( $action, $this->public_requests, true ) ) {
					// hook into action if it's method exists
					add_action( 'wp_ajax_nopriv_' . $action, [ $this, $action ] );
				}
			}
		}
	}

	/**
	 * Load/Render activities suggestion form
	 *
	 * @return void
	 */
	public function activity_suggestion_form() {

		// vars
		$listing_id = absint( filter_input( INPUT_GET, 'trbs_listing_id', FILTER_SANITIZE_NUMBER_INT ) );

		// popup start
		echo '<div class="popup">';

		$listing = get_post( $listing_id );
		if ( false === is_object( $listing ) || 'job_listing' !== $listing->post_type ) {
			// invalid badge!
			die( __( 'Unknown listing!', TRBS_DOMAIN ) . '</div>' );
		}

		// render
		trbs_frontend()->render_activities_suggestion_form( $listing );

		// popup end
		echo '</div>';

		die();
	}

	/**
	 * Check/Un-check checklist point
	 *
	 * @return void
	 */
	public function challenges_checklist_update() {

		// security check
		check_admin_referer( 'trbs_challenges_checklist_change', 'nonce' );

		// mark args/inputs
		$mark_args = filter_input_array( INPUT_POST, [
			'badge'   => FILTER_VALIDATE_INT,
			'checked' => [
				'filter'  => FILTER_CALLBACK,
				'options' => function ( $value ) {

					return 'true' === sanitize_key( $value );
				},
			],
			'point'   => FILTER_VALIDATE_INT,
			'step'    => FILTER_VALIDATE_INT,
		] );

		if ( in_array( null, $mark_args, true ) ) {
			// missing data
			$this->error( __( 'Missing or Invalid input!', TRBS_DOMAIN ) );
		}

		$mark_args['user'] = get_current_user_id();

		// update mark
		$update_point_mark = trbs_rewards()->update_checklist_mark( $mark_args );
		if ( is_wp_error( $update_point_mark ) ) {
			// error occurred
			$this->error( $update_point_mark->get_error_message() );
		}

		// respond with updated completion percentage
		$this->success( [
			'percentage'   => trbs_rewards()->get_step_completed_percentage( $mark_args['step'], $mark_args['user'] ),
			'last_earning' => trbs_rewards()->get_last_badge_earning( $mark_args['badge'], $mark_args['user'] ),
		] );
	}

	/**
	 * Get taxonomy terms
	 *
	 * @return void
	 */
	public function trbs_get_taxonomy_terms() {

		if ( ! current_user_can( 'manage_options' ) ) {
			// don't have access
			$this->error( __( 'Invalid access.', TRBS_DOMAIN ) );
		}

		$taxonomy = sanitize_key( isset( $_REQUEST['taxonomy'] ) ? $_REQUEST['taxonomy'] : '' );
		if ( '' === $taxonomy || empty( $taxonomy ) || false === get_taxonomy( $taxonomy ) ) {
			// unknown taxonomy
			$this->error( __( 'Invalid taxonomy.', TRBS_DOMAIN ) );
		}

		$this->success( get_terms( [
			'taxonomy'     => $taxonomy,
			'hide_empty'   => false,
			'fields'       => 'id=>name',
			'hierarchical' => false,
		] ) );
	}

	/**
	 * AJAX Debug response
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function debug( $data ) {

		// return dump
		$this->error( $data );
	}

	/**
	 * AJAX Debug response ( dump )
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args
	 *
	 * @return void
	 */
	public function dump( $args ) {

		// return dump
		$this->error( print_r( func_num_args() === 1 ? $args : func_get_args(), true ) );
	}

	/**
	 * AJAX Error response
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function error( $data ) {

		wp_send_json_error( $data );
	}

	/**
	 * AJAX success response
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function success( $data ) {

		wp_send_json_success( $data );
	}

	/**
	 * AJAX JSON Response
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response
	 *
	 * @return void
	 */
	public function response( $response ) {

		// send response
		wp_send_json( $response );
	}
}

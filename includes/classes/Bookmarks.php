<?php namespace True_Resident\Badge_System;

use WP_Job_Manager_Bookmarks;

/**
 * Bookmarks/checking-in logic
 *
 * @package True_Resident\Badge_System
 */
class Bookmarks extends Component {
	/*
	 * @var string
	 */
	protected $bookmark_mode;

	/**
	 * Original Job Manager Bookmarks addon component
	 *
	 * @var WP_Job_Manager_Bookmarks
	 */
	protected $wp_job_manager_bookmarks;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init() {
		parent::init();

		// WP all plugin loaded action hook
		add_action( 'plugins_loaded', [ &$this, 'replace_bookmark_handler' ] );
	}

	/**
	 * Replace bookmark handler with our version
	 *
	 * @return void
	 */
	public function replace_bookmark_handler() {
		$this->wp_job_manager_bookmarks = isset( $GLOBALS['job_manager_bookmarks'] ) ? $GLOBALS['job_manager_bookmarks'] : null;
		if ( null === $this->wp_job_manager_bookmarks ) {
			// skip, Job Manager bookmarks object not found
			return;
		}

		// replace the default handler
		remove_action( 'wp', [ $this->wp_job_manager_bookmarks, 'bookmark_handler' ] );
		add_action( 'wp', [ &$this, 'bookmark_handler' ] );
	}

	/**
	 * Handle the bookmark form
	 *
	 * @return void
	 */
	public function bookmark_handler() {
		global $wpdb;

		$user_id = get_current_user_id();
		$post_id = absint( filter_input( INPUT_POST, 'bookmark_post_id', FILTER_SANITIZE_NUMBER_INT ) );

		if ( empty( $user_id ) || empty( $_POST['submit_bookmark'] ) || 0 === $post_id ) {
			// skip non-login users
			return;
		}

		if ( false === wp_verify_nonce( $_REQUEST['_wpnonce'], 'update_bookmark_' . $post_id ) ) {
			// invalid nonce

			if ( ! function_exists( 'um_user_last_login_timestamp' ) ) {
				return;
			}

			// check if user just logged in (within 10 min)
			$last_login_timestamp = (int) um_user_last_login_timestamp( $user_id );
			if ( $last_login_timestamp && ( current_time( 'timestamp' ) - $last_login_timestamp ) > MINUTE_IN_SECONDS * 10 ) {
				// user logged in long time ago, don't bypass
				return;
			}
		}

		if ( 'job_listing' !== get_post_type( $post_id ) ) {
			return;
		}

		$bookmark_note   = wp_kses_post( stripslashes( filter_input( INPUT_POST, 'bookmark_notes', FILTER_SANITIZE_STRING ) ) );
		$update_bookmark = 'yes' === sanitize_key( filter_input( INPUT_POST, 'bookmark_update', FILTER_SANITIZE_STRING ) );
		$is_bookmarked   = $this->wp_job_manager_bookmarks->is_bookmarked( $post_id );
		$bookmark_mode   = $this->get_bookmark_mode();

		if ( 'single' === $bookmark_mode ) {

			if ( $is_bookmarked ) {

				$this->delete_bookmark( $post_id, $user_id );

			} else {

				$this->add_bookmark( $post_id, $user_id, $bookmark_note );

			}

		} else {

			if ( $update_bookmark && $is_bookmarked ) {

				// update existing one
				$wpdb->update( $this->table_name(),
					[ 'bookmark_note' => $bookmark_note ],
					[ 'post_id' => $post_id, 'user_id' => $user_id ],
					[ '%s' ], [ '%d', '%d' ]
				);

			} else {

				$this->add_bookmark( $post_id, $user_id, $bookmark_note );

			}

		}

		// clear cache
		delete_transient( 'bookmark_count_' . $post_id );
		delete_transient( $this->get_cache_key( $user_id, $post_id ) );
	}

	/**
	 * @param int $post_id
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function delete_bookmark( $post_id, $user_id ) {
		global $wpdb;

		$wpdb->delete( $this->table_name(), [
			'user_id' => $user_id,
			'post_id' => $post_id,
		], [ '%d', '%d' ] );
	}

	/**
	 * @param int    $post_id
	 * @param int    $user_id
	 * @param string $bookmark_note
	 *
	 * @return void
	 */
	public function add_bookmark( $post_id, $user_id, $bookmark_note = '' ) {
		global $wpdb;

		$bookmark_data = [
			'user_id'       => $user_id,
			'post_id'       => $post_id,
			'bookmark_note' => $bookmark_note,
			'date_created'  => current_time( 'mysql' ),
		];

		// new bookmark
		$wpdb->insert( $this->table_name(), $bookmark_data, [ '%d', '%d', '%s', '%s' ] );

		/**
		 * New listing check-in happened
		 *
		 * @param int    $user_id
		 * @param int    $post_id
		 * @param string $bookmark_note
		 * @param string $date_created
		 */
		do_action_ref_array( 'true_resident_listing_new_check_in', $bookmark_data );
	}

	/**
	 * Get listing bookmarks count
	 *
	 * @param int $post_id
	 * @param int $user_id , if set will get the bookmarks only made by that user
	 *
	 * @return int
	 */
	public function get_listing_bookmarks_count( $post_id, $user_id = 0 ) {
		if ( 0 === $user_id ) {
			// get count
			return $this->wp_job_manager_bookmarks->bookmark_count( $post_id );
		}

		$cached = get_transient( $this->get_cache_key( $user_id, $post_id ) );
		if ( false !== $cached ) {
			// return the cached value
			return $cached;
		}

		global $wpdb;

		$table_name   = $this->table_name();
		$count_sql    = "SELECT COUNT(id) FROM {$table_name} WHERE user_id = %d AND post_id = %d";
		$count_params = [ $user_id, $post_id ];

		// execute sql for the current count
		$bookmark_count = absint( $wpdb->get_var( $wpdb->prepare( $count_sql, $count_params ) ) );

		// caching
		set_transient( $this->get_cache_key( $user_id, $post_id ), $bookmark_count, WEEK_IN_SECONDS );

		return $bookmark_count;
	}

	/**
	 * @param int $post_id
	 * @param int $user_id , if set will get the bookmarks only made by that user
	 *
	 * @return null|string
	 */
	public function get_listing_last_bookmark( $post_id, $user_id = 0 ) {
		global $wpdb;

		$table_name = $this->table_name();
		$sql        = "SELECT date_created FROM {$table_name} WHERE post_id = %d";
		$params     = [ $post_id ];

		if ( $user_id ) {
			$sql      .= " AND user_id = %d";
			$params[] = $user_id;
		}

		$sql .= " ORDER BY date_created DESC LIMIT 1";

		return $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Bookmarks DB table name
	 *
	 * @return string
	 */
	public function table_name() {
		global $wpdb;

		return "{$wpdb->prefix}job_manager_bookmarks";
	}

	/**
	 * @param int $user_id
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function get_cache_key( $user_id, $post_id ) {
		return 'user_' . $user_id . '_bookmark_count_' . $post_id;
	}

	/**
	 * @return string
	 */
	public function get_bookmark_mode() {

		if ( null !== $this->bookmark_mode ) {
			return $this->bookmark_mode;
		}

		$this->bookmark_mode = get_option( $this->bookmark_mode_option_name(), 'multiple' );
		if ( ! is_string( $this->bookmark_mode ) || ! in_array( $this->bookmark_mode, [ 'multiple', 'single' ] ) ) {
			$this->bookmark_mode = 'multiple';
		}

		return $this->bookmark_mode;
	}

	/**
	 * Get WP Job Manager Bookmarks instance
	 *
	 * @return WP_Job_Manager_Bookmarks
	 */
	public function get_wp_job_manager_bookmarks() {
		return $this->wp_job_manager_bookmarks;
	}

	/**
	 * @return string
	 */
	public function bookmark_mode_option_name() {
		return 'bookmark_unlock_mode';
	}
}

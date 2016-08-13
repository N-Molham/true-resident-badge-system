<?php namespace True_Resident\Badge_System;

use WP_Job_Manager_Bookmarks;

/**
 * Bookmarks/checking-in logic
 *
 * @package True_Resident\Badge_System
 */
class Bookmarks extends Component
{
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
	protected function init()
	{
		parent::init();

		// WP all plugin loaded action hook
		add_action( 'plugins_loaded', [ &$this, 'replace_bookmark_handler' ] );
	}

	/**
	 * Replace bookmark handler with our version
	 *
	 * @return void
	 */
	public function replace_bookmark_handler()
	{
		$this->wp_job_manager_bookmarks = isset( $GLOBALS['job_manager_bookmarks'] ) ? $GLOBALS['job_manager_bookmarks'] : null;
		if ( null === $this->wp_job_manager_bookmarks )
		{
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
	public function bookmark_handler()
	{
		global $wpdb;

		if ( !is_user_logged_in() )
		{
			// skip non-login users
			return;
		}

		// vars
		$user_id = get_current_user_id();

		// insert/update bookmark
		if ( !empty( $_POST['submit_bookmark'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'update_bookmark' ) )
		{
			// vars
			$post_id         = absint( filter_input( INPUT_POST, 'bookmark_post_id', FILTER_SANITIZE_NUMBER_INT ) );
			$note            = wp_kses_post( stripslashes( filter_input( INPUT_POST, 'bookmark_notes', FILTER_SANITIZE_STRING ) ) );
			$update_bookmark = 'yes' === filter_input( INPUT_POST, 'bookmark_update', FILTER_SANITIZE_STRING );

			if ( $post_id && in_array( get_post_type( $post_id ), [ 'job_listing', 'resume' ] ) )
			{
				if ( $this->wp_job_manager_bookmarks->is_bookmarked( $post_id ) && $update_bookmark )
				{
					// update existing one
					$wpdb->update( $this->table_name(),
						[ 'bookmark_note' => $note ],
						[ 'post_id' => $post_id, 'user_id' => $user_id ],
						[ '%s' ], [ '%d', '%d' ]
					);
				}
				else
				{
					$bookmark_data = [
						'user_id'       => $user_id,
						'post_id'       => $post_id,
						'bookmark_note' => $note,
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
					do_action_ref_array( 'listing_new_checkin', $bookmark_data );
				}

				delete_transient( 'bookmark_count_' . $post_id );
			}
		}

		// delete bookmark
		/*if ( !empty( $_GET['remove_bookmark'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'remove_bookmark' ) )
		{
			$post_id = absint( $_GET['remove_bookmark'] );

			$wpdb->delete(
				"{$wpdb->prefix}job_manager_bookmarks",
				[ 'post_id' => $post_id, 'user_id' => get_current_user_id() ],
				[ '%d', '%d' ]
			);

			delete_transient( 'bookmark_count_' . $post_id );
		}*/
	}

	/**
	 * Bookmarks DB table name
	 *
	 * @return string
	 */
	public function table_name()
	{
		global $wpdb;

		return "{$wpdb->prefix}job_manager_bookmarks";
	}
}

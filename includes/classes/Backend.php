<?php namespace True_Resident\Badge_System;

/**
 * Backend logic
 *
 * @package True_Resident\Badge_System
 */
class Backend extends Component
{
	/**
	 * BadgeOS meta box field name prefix
	 *
	 * @var string
	 */
	protected $badge_field_prefix;

	/**
	 * Hidden pages from POI pages option name
	 *
	 * @var string
	 */
	protected $hidden_badges_option;

	/**
	 * List of admin dashboard messages to display
	 *
	 * @var array
	 */
	protected $dashboard_messages;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function init()
	{
		parent::init();

		// vars
		$this->dashboard_messages   = [];
		$this->hidden_badges_option = '_trbs_hidden_badges';

		// WP admin dashboard messages area
		add_action( 'admin_notices', [ &$this, 'display_notice_messages' ] );

		// WP initiation action hook
		add_action( 'init', [ &$this, 'badgeos_rewards_triggers_ui' ] );

		// WP Admin enqueue script action
		add_action( 'admin_enqueue_scripts', [ &$this, 'load_scripts' ] );

		// BadgeOS before saving step filter
		add_filter( 'badgeos_save_step', [ &$this, 'badgeos_save_step_triggers_options' ], 10, 3 );

		// BadgeOS badge meta box fields filter
		add_filter( 'badgeos_achievement_data_meta_box_fields', [
			&$this,
			'append_hide_from_listing_page_field',
		], 10, 3 );
		add_filter( 'badgeos_achievement_data_meta_box_fields', [ &$this, 'append_badge_type_field' ], 15, 3 );

		// WP post data save action
		add_action( 'save_post_badges', [ &$this, 'store_hidden_badges_as_option' ], 100 );

		// WP admin dashboard action
		add_action( 'admin_action_trbs_run_command', [ &$this, 'manually_trigger_command' ] );

		// Job Manager settings fields
		add_filter( 'job_manager_settings', [ &$this, 'activities_suggestion_form_setting' ], 999 );

		// GForms entries field value
		add_action( 'gform_entries_column', [ &$this, 'append_listing_badge_links_to_entry_value' ], 999, 3 );
		add_filter( 'gform_field_content', [ &$this, 'append_listing_badge_links_to_entry_value' ], 999, 3 );
	}

	/**
	 * Add post edit link to listing & badge in form entry display
	 *
	 * @return string
	 */
	public function append_listing_badge_links_to_entry_value()
	{
		$args = func_get_args();

		// if entry page
		$field = $args[1];
		if ( false === is_object( $field ) )
		{
			$is_entry_page = false;

			// if entries list page
			$field = \GFFormsModel::get_field( \GFAPI::get_form( $args[0] ), $args[1] );
		}
		else
		{
			$is_entry_page = true;
		}

		if ( 'hidden' !== $field->get_input_type() )
		{
			// not a hidden field
			return $is_entry_page ? $args[0] : '';
		}

		if ( !in_array( $field->label, [ 'trbs_listing_id', 'trbs_badge_id' ] ) )
		{
			// unrelated fields
			return $is_entry_page ? $args[0] : '';
		}

		// generate edit post link
		ob_start();
		edit_post_link( __( 'Edit', TRBS_DOMAIN ), '', '', absint( $args[2] ) );
		$edit_link = '&nbsp;' . ob_get_clean();

		if ( $is_entry_page )
		{
			preg_match_all( '/<td.+ class="entry-view-field-value(.+)?">(.+)<\/td>/', $args[0], $matches );

			if ( isset( $matches[0], $matches[2] ) && !empty( $matches[0] ) && !empty( $matches[2] ) )
			{
				// modify value
				return str_replace( $matches[2][0], $matches[2][0] . $edit_link, $args[0] );
			}

			return $args[0];
		}

		echo $edit_link;
	}

	/**
	 * Append activities suggestion form dropdown setting
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function activities_suggestion_form_setting( $settings )
	{
		if ( false === class_exists( 'RGFormsModel' ) )
		{
			// Gravity Form is not installed/active
			return $settings;
		}

		// vars
		$active_forms    = array_merge( [
			[ 'title' => __( 'Please select a form', TRBS_DOMAIN ), 'id' => 0 ],
		], \GFAPI::get_forms() );
		$setting_options = [];

		foreach ( $active_forms as $form )
		{
			// build options array
			$setting_options[ $form['id'] ] = $form['title'];
		}

		// clear data
		unset( $form, $active_forms );

		$settings['job_listings'][1][] = [
			'name'    => trbs_rewards()->get_suggestion_form_option_name(),
			'std'     => null,
			'label'   => __( 'Activities Suggestion Form', TRBS_DOMAIN ),
			'desc'    => '',
			'type'    => 'select',
			'options' => $setting_options,
		];

		return $settings;
	}

	/**
	 * Manually trigger specific command
	 *
	 * @return void
	 */
	public function manually_trigger_command()
	{
		// target command
		$cmd_name = sanitize_key( filter_input( INPUT_GET, 'command_name', FILTER_SANITIZE_STRING ) );

		if ( method_exists( $this, $cmd_name ) )
		{
			// run command if found
			call_user_func( [ &$this, $cmd_name ] );
		}
	}

	/**
	 * Update custom database table(s) schema
	 *
	 * @return void
	 */
	public function update_db_tables()
	{
		// load db API
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;

		$sql = "CREATE TABLE {$wpdb->checklist_marks} (
          mark_id bigint(20) unsigned NOT NULL auto_increment,
          user_id bigint(20) unsigned NOT NULL,
          point_id bigint(20) unsigned NOT NULL,
          step_id bigint(20) unsigned NOT NULL,
          badge_id bigint(20) unsigned NOT NULL default '0',
          mark_datetime datetime NOT NULL default '0000-00-00 00:00:00',
          PRIMARY KEY  (mark_id),
          KEY user_id (user_id),
          KEY step_id (step_id),
          KEY point_id (point_id),
          KEY badge_id (badge_id)
     ) {$wpdb->get_charset_collate()}; ";

		$sql_results = dbDelta( $sql );

		$this->add_notice_message( 'Database custom table(s) updated: <code>' . implode( ', ', $sql_results ) . '</code>', 10, false, true );
	}

	/**
	 * Save hidden badge into option for later use
	 *
	 * @param int $badge_id
	 *
	 * @return void
	 */
	public function store_hidden_badges_as_option( $badge_id )
	{
		// hidden badges
		$hidden_badges = $this->get_hidden_badges();

		// if badge is hidden or not
		$is_hidden     = 'on' === get_post_meta( $badge_id, $this->badge_field_prefix . 'hide_from_listing', true );
		$in_list_index = array_search( $badge_id, $hidden_badges, true );

		if ( $is_hidden )
		{
			if ( false === $in_list_index )
			{
				// add to list
				$hidden_badges[] = $badge_id;
			}
		}
		else
		{
			if ( false !== $in_list_index )
			{
				// remove from the list
				unset( $hidden_badges[ $in_list_index ] );
			}
		}

		// update hidden list
		update_option( $this->hidden_badges_option, $hidden_badges, 'no' );
	}

	/**
	 * Append field for hiding badge from listing page
	 *
	 * @param array  $fields
	 * @param string $prefix
	 * @param array  $achievement_types
	 *
	 * @return array
	 */
	public function append_hide_from_listing_page_field( $fields, $prefix, $achievement_types )
	{
		if ( !in_array( 'badges', $achievement_types ) )
		{
			// skip if badge not in the achievements list
			return $fields;
		}

		// store the prefix for later
		$this->badge_field_prefix = $prefix;

		// add the new field
		$fields[] = [
			'name' => __( 'Hide Achievement From POI Page', TRBS_DOMAIN ),
			'desc' => ' ' . __( 'Yes, will hide this achievement from loading in the POI singular page.', TRBS_DOMAIN ),
			'id'   => $prefix . 'hide_from_listing',
			'type' => 'checkbox',
		];

		return $fields;
	}

	/**
	 * Append field for badge type
	 *
	 * @param array  $fields
	 * @param string $prefix
	 * @param array  $achievement_types
	 *
	 * @return array
	 */
	public function append_badge_type_field( $fields, $prefix, $achievement_types )
	{
		if ( !in_array( 'badges', $achievement_types ) )
		{
			// skip if badge not in the achievements list
			return $fields;
		}

		// store the prefix for later
		$this->badge_field_prefix = $prefix;

		// add the new field
		$fields[] = [
			'name'    => __( 'Badge Type', TRBS_DOMAIN ),
			'desc'    => '',
			'id'      => $prefix . 'badge_type',
			'type'    => 'select',
			'options' => array_merge( [
				[
					'name'  => __( 'None', TRBS_DOMAIN ),
					'value' => 'none',
				],
			], trbs_rewards()->get_badge_types() ),
		];

		return $fields;
	}

	/**
	 * Save additional steps data
	 *
	 * @param  string  $title The original title for our step
	 * @param  integer $step_id The given step's post ID
	 * @param  array   $step_data Our array of all available step data
	 *
	 * @return string
	 */
	public function badgeos_save_step_triggers_options( $title, $step_id, $step_data )
	{
		$triggers = trbs_rewards()->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger )
		{
			// trigger additional UI
			call_user_func( [ $trigger, 'save_data' ], $step_id, $step_data, $trigger_name );
		}

		return $title;
	}

	/**
	 * Load script assets
	 *
	 * @return void
	 */
	public function load_scripts()
	{
		// main admin script
		wp_enqueue_script( 'trbs-triggers', Helpers::enqueue_path() . 'js/admin.js', [ 'jquery' ], Helpers::assets_version(), true );

		// load checkbox
		add_thickbox();

		// main css
		wp_enqueue_style( 'trbs-triggers', Helpers::enqueue_path() . 'css/admin.css', null, Helpers::assets_version() );
	}

	/**
	 * Handle new rewards triggers UI
	 *
	 * @return void
	 */
	public function badgeos_rewards_triggers_ui()
	{
		$triggers = trbs_rewards()->get_triggers();
		foreach ( $triggers as $trigger_name => $trigger )
		{
			// trigger additional UI
			add_action( 'badgeos_steps_ui_html_after_trigger_type', [ $trigger, 'user_interface' ], 10, 2 );
		}
	}

	/**
	 * Get list of hidden badges from POI singular pages
	 *
	 * @return array
	 */
	public function get_hidden_badges()
	{
		return array_values( get_option( $this->hidden_badges_option, [] ) );
	}

	/**
	 * Add/Register new admin notice message
	 *
	 * @param string $body
	 * @param int    $priority priority in display order, lower means show first
	 * @param bool   $is_error
	 * @param bool   $is_dismissible
	 *
	 * @return void
	 */
	public function add_notice_message( $body, $priority = 10, $is_error = false, $is_dismissible = false )
	{
		$this->dashboard_messages[] = compact( 'body', 'priority', 'is_error', 'is_dismissible' );
	}

	/**
	 * Display admin messages
	 *
	 * @return void
	 */
	public function display_notice_messages()
	{
		// sort by higher priority
		usort( $this->dashboard_messages, function ( $a, $b )
		{
			return $a['priority'] - $b['priority'];
		} );

		foreach ( $this->dashboard_messages as $message )
		{
			// message css classes
			$css_classes   = [ 'notice' ];
			$css_classes[] = $message['is_error'] ? 'error' : 'updated';
			$css_classes[] = $message['is_dismissible'] ? 'is-dismissible' : '';

			echo '<div class="', esc_attr( implode( ' ', $css_classes ) ), '"><p>', $message['body'], '</p></div>';
		}
	}
}

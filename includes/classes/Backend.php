<?php namespace True_Resident\Badge_System;

use GFAPI;
use GFFormsModel;
use ReflectionException;
use WP_Job_Manager_Ajax;

/**
 * Backend logic
 *
 * @package True_Resident\Badge_System
 */
class Backend extends Component {

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
	protected function init() {

		parent::init();

		// vars
		$this->dashboard_messages   = [];
		$this->hidden_badges_option = '_trbs_hidden_badges';

		// WP admin dashboard messages area
		add_action( 'admin_notices', [ $this, 'display_notice_messages' ] );

		// WP Admin enqueue script action
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );

		// WP admin dashboard action
		add_action( 'admin_action_trbs_run_command', [ $this, 'manually_trigger_command' ] );

		// Job Manager settings fields
		add_filter( 'job_manager_settings', [ $this, 'activities_suggestion_form_setting' ], 999 );
		add_filter( 'job_manager_settings', [ $this, 'bookmark_mode_toggle_switch' ], 1000 );

		// GForms entries field value
		add_action( 'gform_entries_column', [ $this, 'append_listing_badge_links_to_entry_value' ], 999, 3 );
		add_filter( 'gform_field_content', [ $this, 'append_listing_badge_links_to_entry_value' ], 999, 3 );

	}

	/**
	 * Add post edit link to listing & badge in form entry display
	 *
	 * @return string
	 */
	public function append_listing_badge_links_to_entry_value(): string {

		$args = func_get_args();

		// if entry page
		$field = $args[1];
		if ( false === is_object( $field ) ) {

			$is_entry_page = false;

			// if entries list page
			$field = GFFormsModel::get_field( GFAPI::get_form( $args[0] ), $args[1] );

		} else {

			$is_entry_page = true;

		}

		if ( $field && 'hidden' !== $field->get_input_type() ) {
			// not a hidden field
			return $is_entry_page ? $args[0] : '';
		}

		if ( $field && ! in_array( $field->label, [ 'trbs_listing_id', 'trbs_badge_id' ], true ) ) {
			// unrelated fields
			return $is_entry_page ? $args[0] : '';
		}

		// generate edit post link
		ob_start();
		edit_post_link( __( 'Edit', TRBS_DOMAIN ), '', '', absint( $args[2] ) );
		$edit_link = '&nbsp;' . ob_get_clean();

		if ( $is_entry_page ) {
			preg_match_all( '/<td.+ class="entry-view-field-value(.+)?">(.+)<\/td>/', $args[0], $matches );

			if ( ! empty( $matches[0] ) && ! empty( $matches[2] ) ) {
				// modify value
				return str_replace( $matches[2][0], $matches[2][0] . $edit_link, $args[0] );
			}

			return $args[0];
		}

		echo $edit_link;

		return '';

	}

	/**
	 * Append activities suggestion form dropdown setting
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function activities_suggestion_form_setting( $settings ): array {

		$active_forms = true_resident_get_gravity_forms();

		if ( is_wp_error( $active_forms ) ) {

			// Gravity Form is not installed/active
			return $settings;

		}

		// vars
		$setting_options = [
			0 => __( 'Please select a form', TRBS_DOMAIN ),
		];

		foreach ( $active_forms as $form ) {

			// build options array
			$setting_options[ $form['id'] ] = $form['title'];

		}

		// clear data
		unset( $active_forms );

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
	 * @param array $settings
	 *
	 * @return array
	 */
	public function bookmark_mode_toggle_switch( $settings ): array {

		$settings['job_listings'][1][] = [
			'name'    => trbs_bookmarks()->bookmark_mode_option_name(),
			'std'     => 'multiple',
			'label'   => __( 'Unlock Mode', TRBS_DOMAIN ),
			'type'    => 'radio',
			'options' => [
				'multiple' => __( 'Multiple Unlocks', TRBS_DOMAIN ),
				'single'   => __( 'Single Unlock', TRBS_DOMAIN ),
			],
		];

		return $settings;
	}

	/**
	 * Manually trigger specific command
	 *
	 * @return void
	 */
	public function manually_trigger_command(): void {

		// target command
		$cmd_name = sanitize_key( filter_input( INPUT_GET, 'command_name', FILTER_SANITIZE_STRING ) );

		if ( method_exists( $this, $cmd_name ) ) {
			// run command if found
			$this->$cmd_name();
		}

	}

	/**
	 * Update custom database table(s) schema
	 *
	 * @return void
	 */
	public function update_db_tables(): void {

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
	 * Load script assets
	 *
	 * @return void
	 */
	public function load_scripts(): void {

		// main admin script
		wp_enqueue_script( 'trbs-triggers', Helpers::enqueue_path() . 'js/admin.js', [
			'jquery',
			'jquery-ui-autocomplete',
		], Helpers::assets_version(), true );

		// load checkbox
		add_thickbox();

		// main css
		wp_enqueue_style( 'trbs-triggers', Helpers::enqueue_path() . 'css/admin.css', null, Helpers::assets_version() );

		// localization
		wp_localize_script( 'trbs-triggers', 'trbs_triggers', [
			'urls' => [
				'get_listings' => WP_Job_Manager_Ajax::get_endpoint( 'get_listings' ),
			],
		] );

	}

	/**
	 * Get list of hidden badges from POI singular pages
	 *
	 * @return array
	 */
	public function get_hidden_badges(): array {

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
	public function add_notice_message( $body, $priority = 10, $is_error = false, $is_dismissible = false ): void {

		$this->dashboard_messages[] = compact( 'body', 'priority', 'is_error', 'is_dismissible' );

	}

	/**
	 * Display admin messages
	 *
	 * @return void
	 */
	public function display_notice_messages(): void {

		// sort by higher priority
		usort( $this->dashboard_messages, static function ( $a, $b ) {

			return $a['priority'] - $b['priority'];

		} );

		foreach ( $this->dashboard_messages as $message ) {
			// message css classes
			$css_classes   = [ 'notice' ];
			$css_classes[] = $message['is_error'] ? 'error' : 'updated';
			$css_classes[] = $message['is_dismissible'] ? 'is-dismissible' : '';

			echo '<div class="', esc_attr( implode( ' ', $css_classes ) ), '"><p>', $message['body'], '</p></div>';
		}
	}
}

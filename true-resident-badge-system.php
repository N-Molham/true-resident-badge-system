<?php namespace True_Resident\Badge_System;

/**
 * Plugin Name: True Resident BadgeOS Customization
 * Description: Customize badeOS plugin with website badge system functions
 * Version: 1.2.1
 * Author: Nabeel Molham
 * Author URI: http://nabeel.molham.me/
 * Text Domain: true-resident-badge-system
 * Domain Path: /languages
 * License: GNU General Public License, version 3, http://www.gnu.org/licenses/gpl-3.0.en.html
 */

if ( !defined( 'WPINC' ) )
{
	// Exit if accessed directly
	die();
}

/**
 * Constants
 */

// plugin master file
define( 'TRBS_MAIN_FILE', __FILE__ );

// plugin DIR
define( 'TRBS_DIR', plugin_dir_path( TRBS_MAIN_FILE ) );

// plugin URI
define( 'TRBS_URI', plugin_dir_url( TRBS_MAIN_FILE ) );

// localization text Domain
define( 'TRBS_DOMAIN', 'true-resident-badge-system' );

require_once TRBS_DIR . 'includes/classes/Singular.php';
require_once TRBS_DIR . 'includes/helpers.php';
require_once TRBS_DIR . 'includes/functions.php';

/**
 * Plugin main component
 *
 * @package True_Resident\Badge_System
 */
class Plugin extends Singular
{
	/**
	 * Plugin version
	 *
	 * @var string
	 */
	var $version = '1.0.0';

	/**
	 * Backend
	 *
	 * @var Backend
	 */
	var $backend;

	/**
	 * Backend
	 *
	 * @var Frontend
	 */
	var $frontend;

	/**
	 * Bookmarks
	 *
	 * @var Bookmarks
	 */
	var $bookmarks;

	/**
	 * Backend
	 *
	 * @var Ajax_Handler
	 */
	var $ajax;

	/**
	 * BadgeOS rewards
	 *
	 * @var Rewards
	 */
	var $rewards;

	/**
	 * List of dependency plugin(s)
	 *
	 * @var array
	 */
	protected $dependency_plugins;

	/**
	 * Initialization
	 *
	 * @return void
	 */
	protected function init()
	{
		// load language files
		add_action( 'plugins_loaded', [ &$this, 'load_language' ] );

		// autoloader register
		spl_autoload_register( [ &$this, 'autoloader' ] );

		// vars
		$has_missing_plugins      = false;
		$this->dependency_plugins = [
			'badgeos/badgeos.php'                                   => [
				'title'    => 'BadegOS',
				'link'     => 'https://wordpress.org/plugins/badgeos/',
				'inactive' => false,
			],
			'wp-job-manager/wp-job-manager.php'                     => [
				'title'    => 'WP Job Manager',
				'link'     => 'https://wordpress.org/plugins/wp-job-manager/',
				'inactive' => false,
			],
			'wp-job-manager-bookmarks/wp-job-manager-bookmarks.php' => [
				'title'    => 'WP Job Manager Bookmarks',
				'link'     => 'https://wpjobmanager.com/add-ons/bookmarks/',
				'inactive' => false,
			],
		];

		foreach ( $this->dependency_plugins as $plugin_name => &$plugin_info )
		{
			// check if plugin is active or not
			$plugin_info['inactive'] = Helpers::is_plugin_inactive( $plugin_name );
			if ( $plugin_info['inactive'] )
			{
				// the plugin is missing
				$has_missing_plugins = true;
			}
		}

		if ( $has_missing_plugins )
		{
			add_action( 'admin_notices', [ &$this, 'missing_dependency_plugin_notice' ] );

			// skip as the dependencies aren't available
			return;
		}

		// modules
		$this->rewards   = Rewards::get_instance();
		$this->bookmarks = Bookmarks::get_instance();
		$this->ajax      = Ajax_Handler::get_instance();
		$this->backend   = Backend::get_instance();
		$this->frontend  = Frontend::get_instance();

		// plugin loaded hook
		do_action_ref_array( 'trbs_loaded', [ &$this ] );
	}

	/**
	 * Display error messages for missing plugins
	 *
	 * @return void
	 */
	public function missing_dependency_plugin_notice()
	{
		$missing_plugins = array_map( function ( $item )
		{
			return sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $item['link'] ), $item['title'] );
		}, array_filter( $this->dependency_plugins, function ( $item )
		{
			return $item['inactive'];
		} ) );

		echo '<div class="error" style="padding:15px; position:relative;">',
		sprintf( __( '<b>[True Resident BadgeOS Customization]</b> Missing dependency plugins: <b>%s</b>', TRBS_DOMAIN ), implode( ', ', $missing_plugins ) ),
		'</div>';
	}

	/**
	 * Load view template
	 *
	 * @param string $view_name
	 * @param array  $args ( optional )
	 *
	 * @return void
	 */
	public function load_view( $view_name, $args = null )
	{
		// build view file path
		$__view_name     = $view_name;
		$__template_path = TRBS_DIR . 'views/' . $__view_name . '.php';
		if ( !file_exists( $__template_path ) )
		{
			// file not found!
			wp_die( sprintf( __( 'Template <code>%s</code> File not found, calculated path: <code>%s</code>', TRBS_DOMAIN ), $__view_name, $__template_path ) );
		}

		// clear vars
		unset( $view_name );

		if ( !empty( $args ) )
		{
			// extract passed args into variables
			extract( $args, EXTR_OVERWRITE );
		}

		/**
		 * Before loading template hook
		 *
		 * @param string $__template_path
		 * @param string $__view_name
		 */
		do_action_ref_array( 'trbs_load_template_before', [ &$__template_path, $__view_name, $args ] );

		/**
		 * Loading template file path filter
		 *
		 * @param string $__template_path
		 * @param string $__view_name
		 *
		 * @return string
		 */
		require apply_filters( 'trbs_load_template_path', $__template_path, $__view_name, $args );

		/**
		 * After loading template hook
		 *
		 * @param string $__template_path
		 * @param string $__view_name
		 */
		do_action( 'trbs_load_template_after', $__template_path, $__view_name, $args );
	}

	/**
	 * Language file loading
	 *
	 * @return void
	 */
	public function load_language()
	{
		load_plugin_textdomain( TRBS_DOMAIN, false, dirname( plugin_basename( TRBS_MAIN_FILE ) ) . '/languages' );
	}

	/**
	 * System classes loader
	 *
	 * @param $class_name
	 *
	 * @return void
	 */
	public function autoloader( $class_name )
	{
		if ( strpos( $class_name, __NAMESPACE__ ) === false )
		{
			// skip non related classes
			return;
		}

		$class_path = TRBS_DIR . 'includes' . DIRECTORY_SEPARATOR . 'classes' . str_replace( [
				__NAMESPACE__,
				'\\',
			], [ '', DIRECTORY_SEPARATOR ], $class_name ) . '.php';

		if ( file_exists( $class_path ) )
		{
			// load class file if found
			require_once $class_path;
		}
	}
}

// boot up the system
true_resident_badge_system();
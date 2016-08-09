<?php
/**
 * Created by Nabeel
 * Date: 2016-01-22
 * Time: 2:38 AM
 *
 * @package True_Resident\Badge_System
 */

use True_Resident\Badge_System\Plugin;

if ( !function_exists( 'true_resident_badge_system' ) ):
	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	function true_resident_badge_system()
	{
		return Plugin::get_instance();
	}
endif;

if ( !function_exists( 'trbs_view' ) ):
	/**
	 * Load view
	 *
	 * @param string  $view_name
	 * @param array   $args
	 * @param boolean $return
	 *
	 * @return void
	 */
	function trbs_view( $view_name, $args = null, $return = false )
	{
		if ( $return )
		{
			// start buffer
			ob_start();
		}

		true_resident_badge_system()->load_view( $view_name, $args );

		if ( $return )
		{
			// get buffer flush
			return ob_get_clean();
		}
	}
endif;

if ( !function_exists( 'trbs_version' ) ):
	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	function trbs_version()
	{
		return true_resident_badge_system()->version;
	}
endif;
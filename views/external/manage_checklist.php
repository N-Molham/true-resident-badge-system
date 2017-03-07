<?php
/**
 * Manage badge challenges checklist
 *
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 07-Mar-17
 * Time: 3:57 PM
 */

// start session
session_start();

// WordPress path
$wp_path = isset( $_SESSION['trbs_wp_path'] ) ? $_SESSION['trbs_wp_path'] : null;

if ( null === $wp_path )
{
	die( 'Unknown Request!' );
}

// don't load theme
define( 'WP_USE_THEMES', false );

// load WP env
require rtrim( $wp_path, '/\\' ) . '/wp-load.php';

// Target Step
$step_id  = absint( filter_input( INPUT_GET, 'step_id', FILTER_SANITIZE_NUMBER_INT ) );
$badge_id = absint( filter_input( INPUT_GET, 'badge_id', FILTER_SANITIZE_NUMBER_INT ) );

if ( !current_user_can( 'manage_options' ) || !wp_verify_nonce( filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_STRING ), 'trbs_manage_challenges_checklist_' . $step_id ) )
{
	// permission error
	wp_die( 'Permission denied!' );
}

$step_data = trbs_rewards()->badgeos_step_data_requirements( [], $step_id );

// load page view
trbs_view( 'admin/badges/challenges_checklist', compact( 'step_id', 'badge_id', 'step_data' ) );
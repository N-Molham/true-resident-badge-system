<?php

/**
 * Sample test case.
 */
class Rewards_Tests extends WP_UnitTestCase {

	public function test_badge_types_count() {

		$this->assertCount( 3, true_resident_badge_system()->rewards->get_badge_types() );

	}

	public function test_checklist_table_name() {

		global $wpdb;

		$actual_value = true_resident_badge_system()->rewards->get_checkist_table_name();

		$expected_value = $wpdb->prefix . 'checklist_marks';

		$this->assertSame( $expected_value, $actual_value );

	}
}

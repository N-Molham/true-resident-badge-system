<?php

/**
 * Sample test case.
 */
class Rewards_Tests extends WP_UnitTestCase {

	public function test_badge_types_count() {

		$this->assertCount( 3, true_resident_badge_system()->rewards->get_badge_types() );

	}
}

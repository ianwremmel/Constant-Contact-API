<?php

require_once 'lib/resource/activity.php';
require_once 'config.php';

class ActivityResourceTest extends PHPUnit_Framework_TestCase {
	/**
	 * Create an activity.
	 */
	public function testCreate() {
		$this->markTestIncomplete();
	}

	/**
	 * Retrieve an activity.
	 */
	public function testRetrieve() {
		$this->markTestIncomplete();
	}

	/**
	 * Retrieve all activities.
	 */
	public function testRetrieveBulk() {
		$ar = new ActivityResource();
		$activities = $ar->retrieve();

		$this->assertTrue(is_array($activities));
	}

	/**
	 * Update is not a valid action.
	 */
	public function testUpdateAlwaysThrows() {
		$ar = new ActivityResource();

		$this->setExpectedException('BadMethodCallException');
		$ar->update();
	}

	/**
	 * Delete is not a valid action.
	 */
	public function testDeleteAlwaysThrows() {
		$ar = new ActivityResource();

		$this->setExpectedException('BadMethodCallException');
		$ar->delete();
	}
}

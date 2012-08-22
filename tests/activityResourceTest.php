<?php

require_once 'lib/resource/activity.php';
require_once 'abstract.php';

class ActivityResourceTest extends ConstantContactTestCase {
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
		$ar = $this->makeResource('ActivityResource');
		$activities = $ar->retrieve();

		$this->assertTrue(is_array($activities));
	}

	/**
	 * Update is not a valid action.
	 */
	public function testUpdateAlwaysThrows() {
		$ar = $this->makeResource('ActivityResource');

		$this->setExpectedException('BadMethodCallException');
		$ar->update();
	}

	/**
	 * Delete is not a valid action.
	 */
	public function testDeleteAlwaysThrows() {
		$ar = $this->makeResource('ActivityResource');

		$this->setExpectedException('BadMethodCallException');
		$ar->delete();
	}
}

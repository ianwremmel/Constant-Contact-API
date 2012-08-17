<?php

require_once 'lib/resource/activity.php';
require_once 'config.php';

class ActivityResourceTest extends PHPUnit_Framework_TestCase {
	public function testCreate() {
		$this->markTestIncomplete();
	}

	public function testRetrieve() {
		$this->markTestIncomplete();
	}

	public function testRetrieveBulk() {
		$ar = new ActivityResource();
		$activities = $ar->retrieve();

		$this->assertTrue(is_array($activities));
	}

	public function testUpdateAlwaysThrows() {
		$ar = new ActivityResource();

		$this->setExpectedException('BadMethodCallException');
		$ar->update();
	}

	public function testDeleteAlwaysThrows() {
		$ar = new ActivityResource();

		$this->setExpectedException('BadMethodCallException');
		$ar->delete();
	}
}

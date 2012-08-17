<?php

require_once 'lib/resource/activity.php';
require_once 'config.php';

class ActivityResourceTest extends PHPUnit_Framework_TestCase {
	public function testRetrieveBulk() {
		$ar = new ActivityResource();
		$activities = $ar->retrieve();

		$this->assertTrue(is_array($activities));
	}
}

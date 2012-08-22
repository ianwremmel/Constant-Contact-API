<?php

require_once 'config.php';

abstract class ConstantContactTestCase extends PHPUnit_Framework_TestCase {
	protected function makeResource($resourceClass) {
		return new $resourceClass(CC_API_USERNAME, CC_API_PASSWORD, CC_API_KEY);
	}

	protected function makeEmailAddress($label) {
		return $label . '-' . microtime(TRUE) . '@mailinator.com';
	}
}

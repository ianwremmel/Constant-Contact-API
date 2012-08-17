<?php

require_once 'lib/resource.php';

class ActivityResource extends Resource {
	protected $endpoint = 'activities';
	protected $objectType = 'Activity';
	protected $itemNodeNames = array(
		'Errors' => 'Error',
	);

	/*************************************************************************\
	 * CRUD FUNCTIONS
	\*************************************************************************/
	public function update() {
		throw new BadMethodCallException('The update operation is not supported by the Activity resource');
	}

	public function delete() {
		throw new BadMethodCallException('The delete operation is not supported by the Activity resource');
	}
}

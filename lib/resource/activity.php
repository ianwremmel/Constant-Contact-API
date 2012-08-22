<?php

require_once dirname(dirname(__FILE__)) . '/resource.php';

/**
 * Represents an Activity.
 */
class ActivityResource extends Resource {
	protected $endpoint = 'activities';
	protected $objectType = 'Activity';
	protected $itemNodeNames = array(
		'Errors' => 'Error',
	);

	/*************************************************************************\
	 * CRUD FUNCTIONS
	\*************************************************************************/
	/**
	 * Update is not a valid Activity action.
	 */
	public function update() {
		throw new BadMethodCallException('The update operation is not supported by the Activity resource');
	}

	/**
	 * Delete is not a valid Activity action.
	 */
	public function delete() {
		throw new BadMethodCallException('The delete operation is not supported by the Activity resource');
	}
}

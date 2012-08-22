<?php

require_once 'lib/resource.php';

/**
 * Represents a ContactList.
 */
class ContactListResource extends Resource {
	protected $endpoint = 'lists';
	protected $objectType = 'ContactList';
	protected $itemNodeNames = array();

	/**
	 * Converts booleans to the strings expected by the API.
	 */
	public function setOptInDefault($oid) {
		if (is_bool($oid)) {
			$oid = $oid ? 'true' : 'false';
		}

		call_user_func(array($this, '__call'), 'setOptInDefault', array($oid));
	}

	/**
	 * Converts strings from the API to booleans.
	 */
	public function getOptInDefault() {
		if (!array_key_exists('OptInDefault', $this->data)) {
			$this->data['OptInDefault'] = 'false';
		}
		switch (strtolower($this->data['OptInDefault'])) {
			case 'true':
				return TRUE;
				break;
			case 'false';
				return FALSE;
				break;
			default:
				throw UnexpectedValueException();
		}
	}

	/**
	 * Retrieves all of the members of the list
	 * @param boolean $full If true, will call retrieve for each retrieved
	 * member (note: this may be expensive).
	 * @return array an array of Contact objects.
	 */
	public function members($full = FALSE) {
		return $this->objects('/members', 'ContactResource', 'resource/contact.php', $full);
	}

	public function retrieve() {
		// if ID is not set, we'll definitely need to perform a bulk operation
		if (is_null($this->getId())) {
			$lists = parent::retrieve();
			// if Name is not set, we simply need to return $lists
			if (is_null($this->getName())) {
				return $lists;
			}

			// otherwise, we need to find the list identified by Name and finish
			// retrieving it
			// The list won't be sort by name, so it's linear search time.
			$name = $this->getName();
			foreach ($lists as $list) {
				/* @var $list ContactListResource */
				if ($list->getName() === $name) {
					// Surprisingly, this actually works (let's hope it also
					// works in PHP 5.2)
					$this->data = $list->data;
					return;
				}
			}

			// if we made it this far, no list with the specified name exists.
			throw new RuntimeException('No list found with name ' . $name);
		}

		parent::retrieve();
	}
}










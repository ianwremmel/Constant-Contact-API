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
}

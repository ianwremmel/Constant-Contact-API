<?php

require_once 'lib/resource.php';

class ContactListResource extends Resource {
	protected $endpoint = 'lists';
	protected $objectType = 'ContactList';
	protected $itemNodeNames = array();

	public function setOptInDefault($oid) {
		if (is_bool($oid)) {
			$oid = $oid ? 'true' : 'false';
		}

		call_user_func(array($this, '__call'), 'setOptInDefault', array($oid));
	}

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
	 * @param boolean $full If true, will call retrieve for each retrieved
	 * member (note: this may be expensive).
	 */
	public function members($full = FALSE) {
		return $this->objects('members', 'ContactResource', 'resource/contact.php', $full);
	}
}

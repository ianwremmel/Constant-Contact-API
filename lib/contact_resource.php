<?php

require_once 'resource.php';

class ContactResource extends Resource {
	protected $endpoint = 'contacts';
	protected $objectType = 'Contact';

	/*************************************************************************\
	 * PUBLIC FUNCTIONS
	\*************************************************************************/
	public function addList($list) {
		$lists = $this->getLists();

		$lists[] = $list;
		$lists = array_unique($lists);

		$this->setLists($lists);
	}

	public function removeList() {
		$lists = $this->getLists();

		$index = array_search($list, $lists);
		$lists = array_splice($lists, $index, 1);

		$this->setLists($lists);
	}

	public function getLists() {
		if (!isset($this->data['lists'])) {
			return array();
		}

		return $this->data['lists'];
	}

	public function setLists($lists) {
		if (!is_array($lists)) {
			throw new InvalidArgumentException('$lists must be an array.');
		}

		$this->data['lists'] = $lists;
	}

	/*************************************************************************\
	 * CRUD FUNCTIONS
	\*************************************************************************/
	// public function create() {
		// $contactListPrefix = 'http://' . CC_API_URL . 'lists';
//
		// $contact = array(
			// 'EmailAddress' => $this->getEmailAddress(),
			// 'OptInSource' => $this->getOptInSource(),
			// 'ContactLists' => array(),
		// );
//
		// foreach ($this->getLists() as $list) {
			// $contact['ContactLists']['@id'] = $contactListPrefix . $list;
		// }
//
		// $this->execute('POST', $contact);
	// }
//
	// public function retrieve() {
//
	// }
//
	// public function update() {
//
	// }
//
	// public function delete() {
//
	// }
}

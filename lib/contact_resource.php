<?php

require_once 'resource.php';

class ContactResource extends Resource {
	protected $endpoint = 'contacts';
	protected $objectType = 'Contact';

	protected $itemNodeNames = array(
		'ContactLists' => 'ContactList',
	);

	/*************************************************************************\
	 * PUBLIC FUNCTIONS
	\*************************************************************************/
	public function __construct() {
		parent::__construct();
		$this->setOptInSource(self::ACTION_BY_CUSTOMER);
	}

	public function addList($list) {
		// TODO ensure $list is not a full URI

		$list = $this->_listIdToString($list);

		$lists = $this->getLists();

		$lists[] = $list;
		$lists = array_unique($lists);

		$this->setLists($lists);
	}

	public function removeList($list) {
		// TODO ensure $list is not a full URI

		$list = $this->_listIdToString($list);

		$lists = $this->getLists();

		$index = array_search($list, $lists);
		$lists = array_splice($lists, $index, 1);

		$this->setLists($lists);
	}

	public function getLists() {
		if (!isset($this->data['ContactLists'])) {
			return array();
		}

		return $this->data['ContactLists'];
	}

	public function setLists($lists) {
		if (!is_array($lists)) {
			throw new InvalidArgumentException('$lists must be an array.');
		}

		$this->data['ContactLists'] = $lists;
	}

	/**
	 * @deprecated use self::generateIdString('lists', $id) instead.
	 */
	protected function _listIdToString($id) {
		return self::generateIdString('lists', $id);
	}

	/*************************************************************************\
	 * CRUD FUNCTIONS
	\*************************************************************************/
	public function create() {
		if (is_null($this->getEmailAddress())) {
			throw new RuntimeException('EmailAddress must be set before calling create().');
		}

		if (is_null($this->getOptInSource())) {
			throw new RuntimeException('OptInSource must be set before calling create().');
		}

		$lists = $this->getLists();
		if (empty($lists)) {
			throw new RuntimeException('Contact must be assigned to at least one list before calling create().');
		}

		parent::create();
	}

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

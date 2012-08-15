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
		// TODO add do-not-email list here too
		if ($list === 'removed') {
			$this->setLists(array());
		}


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

	/**
	 * Retries a contact by email address or id.  Note: if using email address,
	 * two calls will be made to Constant Contact.
	 */
	public function retrieve() {
		if (is_null($this->getId())) {
			if (is_null($this->getEmailAddress())) {
				throw new RuntimeException('Id or EmailAddress must be set before calling retrieve().');
			}
			else {
				$this->setId('?email=' . $this->getEmailAddress());
				// If we're retrieving by email, we'll only get a partial
				// response, so we do an initial retrieval here to get the id.
				// and then we'll let the id-based retrieval occur as nromal
				// below.
				parent::retrieve();
			}
		}

		parent::retrieve();
	}
//
	// public function update() {
//
	// }
//
	/**
	 * @var boolean $permanent If true, will move the contact to the Do Not
	 * Email list.  Once on this list, contacts cannot be readded to any list.
	 * If false, they will be added to the Removed list which can be undone.
	 */
	public function delete($permanent = FALSE) {
		if ($permanent) {
			parent::delete();
		}
		else {
			$this->addList('removed');
			$this->update();
			print_r($this);
			exit();
		}
	}

	/*************************************************************************\
	 * RETRIEVAL PROCESSING FUNCTIONS
	\*************************************************************************/
	protected function addContactList($item) {
		$id = self::extractIdFromString($item['@attributes']['id']);
		$this->addList($id);
	}
}

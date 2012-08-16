<?php

require_once 'lib/resource.php';

class ContactResource extends Resource {
	protected $endpoint = 'contacts';
	protected $objectType = 'Contact';

	protected $itemNodeNames = array(
		'ContactLists' => 'ContactList',
	);

	const STATUS_ACTIVE = 'Active';
	const STATUS_DONOTMAIL = 'Do Not Mail';
	const STATUS_REMOVED = 'Removed';

	/*************************************************************************\
	 * PUBLIC FUNCTIONS
	\*************************************************************************/
	public function __construct() {
		parent::__construct();
		$this->setOptInSource(self::ACTION_BY_CUSTOMER);
	}

	public function addList($list) {
		if (is_numeric($list)) {
			$list = self::generateIdString('lists', $list);
		}

		$this->addContactList($list);
	}

	public function removeList($list) {
		if (is_numeric($list)) {
			$list = self::generateIdString('lists', $list);
		}

		$this->remContactList($list);
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

		$lists = $this->getContactLists();
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
			$this->setContactLists(array());
			$this->update();
		}
	}
}

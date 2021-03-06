<?php

require_once dirname(dirname(__FILE__)) . '/resource.php';

/**
 * Represents a Contact.
 */
class ContactResource extends Resource {
	protected $endpoint = 'contacts';
	protected $objectType = 'Contact';

	protected $itemNodeNames = array(
		'ContactLists' => 'ContactList',
	);

	const STATUS_ACTIVE = 'Active';
	const STATUS_DONOTMAIL = 'Do Not Mail'; // Permanent
	const STATUS_REMOVED = 'Removed'; // Temporary

	const LIST_TYPE_ACTIVE = 'active';
	const LIST_TYPE_DONOTMAIL = 'do-not-mail'; // Permanent
	const LIST_TYPE_REMOVED = 'removed'; // Temporary

	/*************************************************************************\
	 * PUBLIC FUNCTIONS
	\*************************************************************************/
	/**
	 * Add the Contact to the specified list using just the list id instead of
	 * the list's full URI.
	 * @param integer $list A list ID
	 */
	public function addList($list) {
		if (is_numeric($list)) {
			$list = self::generateIdString('lists', $list);
		}

		$this->addContactList($list);
	}

	/**
	 * Remove the Contact from the specified list using just the list id
	 * instead of the list's full URI.
	 * @param integer $list A list ID
	 */
	public function removeList($list) {
		if (is_numeric($list)) {
			$list = self::generateIdString('lists', $list);
		}

		$this->remContactList($list);
	}

	/**
	 * Checks if the contact is a member of a list
	 * @param string|integer $list may be one of a numeric list id, list URI, or list name
	 */
	public function onList($list) {
		// Both Id and EmailAddress will have been set if the contact has been
		// retrieved.
		if (is_null($this->getId()) || is_null($this->getEmailAddress())) {
			throw new RuntimeException('Contact must be retrieved before calling onList().');
		}

		$lists = $this->getContactLists();
		if (!is_array($lists)) {
			return FALSE;
		}

		// if $list is an ID, turn it into a URI
		if (is_numeric($list)) {
			$list = $this->generateIdString('lists', $list);
		}
		// otherwise, if it's not a URI, figure out its URI from its name
		else if (!preg_match('/^http/', $list)) {
			$clr = new ContactListResource($this->username, $this->password, $this->apiKey);
			$clr->setName($list);
			$clr->retrieve();

			$list = $this->generateIdString('lists', $clr->getId());
		}

		return in_array($list, $lists);
	}

	/*************************************************************************\
	 * CRUD FUNCTIONS
	\*************************************************************************/
	/**
	 * @see Resource::create()
	 */
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
	 * @see Resource::retrieve()
	 */
	public function retrieve($full = FALSE) {
		if (is_null($this->getId())) {
			// If we have neither an email address or an id, this is a bulk
			// operation and we need to return the array of contacts.
			if (is_null($this->getEmailAddress())) {
				if (array_key_exists('updatedsince', $this->data)) {
					if (!array_key_exists('listid', $this->data) && !array_key_exists('listtype', $this->data)) {
						throw new RuntimeException('"updatedsince" requires either "listid" or "listtype" to be set.');
					}
				}
				return parent::retrieve($full);
			}
			// Otherwise, we need to substitute the email address for the id and
			// call retrieve twice.
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

	/**
	 * @param boolean $permanent If true, will move the contact to the Do Not
	 * Email list.  Once on this list, contacts cannot be added to any list.
	 * If false, they will be added to the Removed list which can be undone.
	 * @see Resource::delete()
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

<?php

require_once 'lib/resource/contact.php';
require_once 'abstract.php';

class ContactResourceTest extends ConstantContactTestCase {

	/**
	 * Creates a new unique email address.
	 * @deprecated use parent::makeEmailAddress directly.
	 */
	protected function makeEmailAddress() {
		return parent::makeEmailAddress('test');
	}

	/**
	 * Create a new Contact with the minimum set of required fields.
	 */
	public function testCreate() {
		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress($this->makeEmailAddress());
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->addList(1);
		$cr->create();

		$this->assertFalse(is_null($cr->getId()));
	}

	/**
	 * Retrieve a Contact by numeric ID.
	 */
	public function testRetrieveById() {
		$cr = $this->makeResource('ContactResource');
		$cr->setId(1);

		$cr->retrieve();

		$this->assertEquals(USER_ONE_EMAIL, $cr->getEmailAddress());
	}

	/**
	 * Retrieve a Contact by EmailAddress.
	 */
	public function testRetrieveByEmail() {
		// TODO create contact here so we don't need to rely on USER_ONE_EMAIL
		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress(USER_ONE_EMAIL);

		$cr->retrieve();

		$this->assertEquals(1, $cr->getId());
	}

	/**
	 * Retrieve all Contacts.
	 */
	public function testRetrieveBulk() {
		$cr = $this->makeResource('ContactResource');

		$contacts = $cr->retrieve();

		$this->assertTrue(is_array($contacts));
		$this->assertNotEmpty($contacts);

		foreach ($contacts as $contact) {
			$this->assertInstanceOf('ContactResource', $contact);
		}
	}

	/**
	 * Retrieve all Contact alltered since a certain time on a certain list.
	 */
	public function testRetrieveBulkSinceXByListId() {
		// store the function start time
		$time = time();

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress('testRetrieveBulkSinceXByListId' . microtime(TRUE) . '@mailinator.com');
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->addList(1);
		$cr->create();

		$crBulk = $this->makeResource('ContactResource');
		$crBulk->setupdatedsince(date(DATE_ATOM, $time));
		$crBulk->setlistid(1);
		$updates = $crBulk->retrieve();

		$this->assertTrue(is_array($updates));
		$this->assertNotEmpty($updates);

		$ids = array();
		foreach ($updates as $update) {
			/* @var $update ContactResource */
			$this->assertInstanceOf('ContactResource', $update);
			$ids[] = $update->getId();
		}

		$this->assertContains($cr->getId(), $ids);
	}

	/**
	 * Retrieve all Contacts altered since a certain time with a certain status.
	 */
	public function testRetrieveBulkSinceXByListType() {
		$time = time();

		// Create a contact
		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress('testRetrieveBulkSinceXByListType' . microtime(TRUE) . '@mailinator.com');
		$cr->addList(1);
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->create();
		$cr->retrieve();

		// make sure it's active
		$this->assertEquals(ContactResource::STATUS_ACTIVE, $cr->getStatus());
		// make sure it comes back via bulk retrieve
		$crBulk = $this->makeResource('ContactResource');
		$crBulk->setupdatedsince(date(DATE_ATOM, $time));
		$crBulk->setlisttype(ContactResource::LIST_TYPE_ACTIVE);

		$updates = $crBulk->retrieve();
		$this->assertNotEmpty($updates);

		$ids = array();
		foreach ($updates as $update) {
			/* @var $update ContactResource */
			$this->assertInstanceOf('ContactResource', $update);
			$ids[] = $update->getId();
		}

		$this->assertContains($cr->getId(), $ids);


		// remove it from all lists
		$cr->delete();
		$cr->retrieve();

		// make sure it's removed
		$this->assertEquals(ContactResource::STATUS_REMOVED, $cr->getStatus());
		// Make sure it's out of the active group
		$updates = $crBulk->retrieve();

		$ids = array();
		foreach ($updates as $update) {
			/* @var $update ContactResource */
			$this->assertInstanceOf('ContactResource', $update);
			$ids[] = $update->getId();
		}

		$this->assertNotContains($cr->getId(), $ids);

		// and part of the removed group
		$crBulk->setlisttype(ContactResource::LIST_TYPE_REMOVED);
		$updates = $crBulk->retrieve();
		$this->assertNotEmpty($updates);

		$ids = array();
		foreach ($updates as $update) {
			/* @var $update ContactResource */
			$this->assertInstanceOf('ContactResource', $update);
			$ids[] = $update->getId();
		}

		$this->assertContains($cr->getId(), $ids);

		// delete it
		$cr->delete(TRUE);
		$cr->retrieve();

		// make sure it's do-not-email
		$this->assertEquals(ContactResource::STATUS_DONOTMAIL, $cr->getStatus());
		// Make sure it's out of the removed group
		$updates = $crBulk->retrieve();

		$ids = array();
		foreach ($updates as $update) {
			/* @var $update ContactResource */
			$this->assertInstanceOf('ContactResource', $update);
			$ids[] = $update->getId();
		}

		$this->assertNotContains($cr->getId(), $ids);

		// and part of the do not mail group
		$crBulk->setlisttype(ContactResource::LIST_TYPE_DONOTMAIL);
		$updates = $crBulk->retrieve();
		$this->assertNotEmpty($updates);

		$ids = array();
		foreach ($updates as $update) {
			/* @var $update ContactResource */
			$this->assertInstanceOf('ContactResource', $update);
			$ids[] = $update->getId();
		}

		$this->assertContains($cr->getId(), $ids);
	}

	/**
	 * Change a Contact's email address.
	 */
	public function testUpdateEmailAddress() {
		$oldAddress = $this->makeEmailAddress();

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress($oldAddress);
		$cr->addList(1);
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->create();

		$newAddress = $this->makeEmailAddress();
		$cr->setEmailAddress($newAddress);
		$cr->update();

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($newAddress);
		$cr2->retrieve();

		$this->assertFalse(is_null($cr2->getId()));
		$this->assertEquals($cr->getId(), $cr2->getId());
	}

	/**
	 * Add a Contact to a list.
	 */
	public function testUpdateAddToList() {
		$address = $this->makeEmailAddress();

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->create();

		// TODO remove hardcoded '2' from this test
		$cr->addList(2);
		$cr->update();

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains($cr->generateIdString('lists', 2), $cr2->getContactLists());
	}

	/**
	 * Remove a Contact from a list.
	 */
	public function testUpdateRemoveFromList() {
		// TODO add an assertion to make sure the correct change is made locallay
		$address = $this->makeEmailAddress();

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->create();

		// TODO remove hardcoded '2' from this test
		$cr->addList(2);
		$cr->update();

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains($cr->generateIdString('lists', 2), $cr2->getContactLists());

		$cr2->removeList(2);
		$cr2->update();

		$cr3 = $this->makeResource('ContactResource');
		$cr3->setEmailAddress($address);
		$cr3->retrieve();

		$this->assertNotContains($cr->generateIdString('lists', 2), $cr3->getContactLists());
	}

	/**
	 * Permanently delete a Contact.
	 */
	public function testDeletePermanent() {
		$address = $this->makeEmailAddress();

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->create();

		$id = $cr->getId();
		$cr->delete(TRUE);

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertEmpty($cr2->getContactLists());
		$this->assertEquals(ContactResource::STATUS_DONOTMAIL, $cr2->getStatus());
	}

	/**
	 * Remove a Contact from all lists, but in a way that they can be
	 * resubscribed later.
	 */
	public function testDeleteTemporary() {
		$address = $this->makeEmailAddress();

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->create();

		$id = $cr->getId();
		$cr->delete();

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertEmpty($cr2->getContactLists());
		$this->assertEquals(ContactResource::STATUS_REMOVED, $cr2->getStatus());
	}
}

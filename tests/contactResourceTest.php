<?php

require_once 'lib/resource/contact.php';
require_once 'abstract.php';

/**
 * @todo need tests for onList()
 * @todo need to test retrieval of resources that don't exist
 * @todo need to test creation of resources that already exist
 * @todo need to rename Delete tests
 * @todo need to test return from completely deleted when ACTION_BY_CONTACT used
 *
 */
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

		// updatedsince is bucketed into 1 minute windows because DATE_ATOM
		// doesn't contain a seconds component.  In order to ensure that $time
		// represents the bucket before the the ContactResource gets created,
		// we need to wait one full minute to guarantee we're operating in a
		// new bucket. We can't simply subtract 1 minute from the current
		// time because Contact Resources from other tests could be returned.
		// In fact, we should probably have an additional 60 second delay before
		// store $time to guarantee complete isolation, but this seems good
		// enough for now.
		sleep(60);

		$cr = $this->makeResource('ContactResource');
		$cr->setEmailAddress('testRetrieveBulkSinceXByListId' . microtime(TRUE) . '@mailinator.com');
		$cr->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$cr->addList(1);
		$cr->create();

		// add artificial delay to make sure clock synchronization doesn't
		// cause the test to fail
		sleep(30);

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

		// updatedsince is bucketed into 1 minute windows because DATE_ATOM
		// doesn't contain a seconds component.  In order to ensure that $time
		// represents the bucket before the the ContactResource gets created,
		// we need to wait one full minute to guarantee we're operating in a
		// new bucket. We can't simply subtract 1 minute from the current
		// time because Contact Resources from other tests could be returned.
		// In fact, we should probably have an additional 60 second delay before
		// store $time to guarantee complete isolation, but this seems good
		// enough for now.
		sleep(60);

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
		$cr->addList(26);
		$cr->update();

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains($cr->generateIdString('lists', 26), $cr2->getContactLists());
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

		$cr2 = $this->makeResource('ContactResource');
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$cr2->addList(26);
		$cr2->update();

		$this->assertContains($cr->generateIdString('lists', 26), $cr2->getContactLists());

		$cr2->removeList(26);
		$cr2->update();

		$cr3 = $this->makeResource('ContactResource');
		$cr3->setEmailAddress($address);
		$cr3->retrieve();

		$this->assertNotContains($cr->generateIdString('lists', 26), $cr3->getContactLists());
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

	/**
	 * Confirms that the ACTION_BY_CONTACT flag can be used to opt a customer
	 * back into a list even if they've previously unsubscribed.
	 */
	public function testActionByContact() {
		$email = $this->makeEmailAddress('testActionByContact');

		// Create a contact
		$crCreate = $this->makeResource('ContactResource');
		$crCreate->setEmailAddress($email);
		$crCreate->setOptInSource(Resource::ACTION_BY_CUSTOMER);
		$crCreate->addList(1);
		$crCreate->create();

		$this->assertNotNull($crCreate->getId());

		// Mark the contact as do-not-email
		$crDelete = $this->makeResource('ContactResource');
		$crDelete->setId($crCreate->getId());
		$crDelete->retrieve();
		$crDelete->setOptInSource(Resource::ACTION_BY_CONTACT);
		$crDelete->delete(TRUE);

		// Confirm the contact is marked as do-no-email
		$crUpdate = $this->makeResource('ContactResource');
		$crUpdate->setId($crCreate->getId());
		$crUpdate->retrieve();
		$this->assertEquals(ContactResource::STATUS_DONOTMAIL, $crUpdate->getStatus());

		$crUpdate->addList(1);
		try {
			// By default, we shouldn't be able to take people off of the
			// do-not-mail list.
			$crUpdate->setOptInSource(Resource::ACTION_BY_CUSTOMER);
			$crUpdate->update();
			$this->fail('Update should have thrown an exception.');
		}
		catch (UnexpectedValueException $e) {
			// But, if we set the OptInSource to ACTION_BY_CONTACT, we can
			// override the default rules and remove a contact from the
			// do-not-mail list.
			$crUpdate->setOptInSource(Resource::ACTION_BY_CONTACT);
			$crUpdate->update();

			$crRetrieve = $this->makeResource('ContactResource');
			$crRetrieve->setId($crCreate->getId());
			$crRetrieve->retrieve();
			$this->assertEquals(ContactResource::STATUS_ACTIVE, $crRetrieve->getStatus());
		}
	}
}










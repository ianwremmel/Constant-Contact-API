<?php

require_once 'lib/contact_resource.php';
require_once 'config.php';

class ContactResourceTest extends PHPUnit_Framework_TestCase {

	protected function makeEmailAddress() {
		return 'test-' . time() . '@mailinator.com';
	}

	public function testCreate() {
		$cr = new ContactResource();
		$cr->setEmailAddress($this->makeEmailAddress());
		$cr->addList(1);
		$cr->create();

		$this->assertFalse(is_null($cr->getId()));
	}

	public function testRetrieveById() {
		$cr = new ContactResource();
		$cr->setId(1);

		$cr->retrieve();

		$this->assertEquals($cr->getEmailAddress(), USER_ONE_EMAIL);
	}

	public function testRetrieveByEmail() {
		// TODO create contact here so we don't need to rely on USER_ONE_EMAIL
		$cr = new ContactResource();
		$cr->setEmailAddress(USER_ONE_EMAIL);

		$cr->retrieve();

		$this->assertEquals($cr->getId(), 1);
	}

	public function testUpdateEmailAddress() {
		$oldAddress = $this->makeEmailAddress();

		$cr = new ContactResource();
		$cr->setEmailAddress($oldAddress);
		$cr->addList(1);
		$cr->create();

		$newAddress = $this->makeEmailAddress();
		$cr->setEmailAddress($newAddress);
		$cr->update();

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($newAddress);
		$cr2->retrieve();

		$this->assertFalse(is_null($cr2->getId()));
		$this->assertEquals($cr->getId(), $cr2->getId());
	}

	public function testUpdateAddToList() {
		$address = $this->makeEmailAddress();

		$cr = new ContactResource();
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->create();

		// TODO remove hardcoded '2' from this test
		$cr->addList(2);
		$cr->update();

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains(ContactResource::generateIdString('lists', 2), $cr2->getContactLists());
	}

	public function testUpdateRemoveFromList() {
		// TODO add an assertion to make sure the correct change is made locallay
		$address = $this->makeEmailAddress();

		$cr = new ContactResource();
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->create();

		// TODO remove hardcoded '2' from this test
		$cr->addList(2);
		$cr->update();

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains(ContactResource::generateIdString('lists', 2), $cr2->getContactLists());

		$cr2->removeList(2);
		$cr2->update();

		$cr3 = new ContactResource();
		$cr3->setEmailAddress($address);
		$cr3->retrieve();

		$this->assertNotContains(ContactResource::generateIdString('lists', 2), $cr3->getContactLists());
	}

	public function testDeletePermanent() {
		$address = $this->makeEmailAddress();

		$cr = new ContactResource();
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->create();

		$id = $cr->getId();
		$cr->delete(TRUE);

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertEmpty($cr2->getContactLists());
		$this->assertEquals($cr2->getStatus(), ContactResource::STATUS_DONOTMAIL);
	}

	public function testDeleteTemporary() {
		$address = $this->makeEmailAddress();

		$cr = new ContactResource();
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->create();

		$id = $cr->getId();
		$cr->delete();

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertEmpty($cr2->getContactLists());
		$this->assertEquals($cr2->getStatus(), ContactResource::STATUS_REMOVED);
	}
}

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

		$cr->addList('test list');
		$cr->update();

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains('test list', $cr2->getLists());
	}

	public function testUpdateRemoveFromList() {
		$address = $this->makeEmailAddress();

		$cr = new ContactResource();
		$cr->setEmailAddress($address);
		$cr->addList(1);
		$cr->create();

		$cr->addList('test list');
		$cr->update();

		$cr2 = new ContactResource();
		$cr2->setEmailAddress($address);
		$cr2->retrieve();

		$this->assertContains('test list', $cr2->getLists());

		$cr2->removeList('test list');
		$cr2->update();

		$cr3 = new ContactResource();
		$cr3->setEmailAddress($address);
		$cr3->retrieve();

		$this->assertNotContains('test list', $cr3->getLists());
	}

	public function testDelete() {
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

		$this->assertNull($cr2->getId());
	}
}

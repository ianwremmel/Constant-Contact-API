<?php

require_once 'lib/resource/contact_list.php';
require_once 'config.php';

class ContactListResourceTest extends PHPUnit_Framework_TestCase {
	public function testCreate() {
		$clr = new ContactListResource();
		$clr->setName('testCreate' . time());
		$clr->create();

		$this->assertNotNull($clr->getId());
	}

	public function testUpdateOptInDefault() {
		$clr = new ContactListResource();
		$clr->setName('testUpdateOptInDefault' . time());
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());
		$clr2->retrieve();

		$this->assertFalse($clr2->getOptInDefault());

		$clr->setOptInDefault(TRUE);
		$clr->update();
		$clr2->retrieve();

		$this->assertTrue($clr2->getOptInDefault());

		$clr2->setOptInDefault(FALSE);
		$clr2->update();
		$clr->retrieve();

		$this->assertFalse($clr->getOptInDefault());
	}

	public function testUpdateName() {
		$time = time();
		$clr = new ContactListResource();
		$clr->setName('testUpdateName' . $time);
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());
		$clr2->retrieve();

		$this->assertEquals($clr->getName(), $clr2->getName());

		$newName = 'testUpdateName' . $time . 'changed';
		$clr->setName($newName);
		$clr->update();

		$clr2->retrieve();
		$this->assertEquals($newName, $clr2->getName());
	}

	public function testUpdateSortOrder() {
		$clr = new ContactListResource();
		$clr->setName('testUpdateSortOrder' . time());
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());
		$clr2->retrieve();

		$this->assertEquals($clr->getSortOrder(), NULL);
		$this->assertEquals($clr2->getSortOrder(), -1);

		$sortOrder = 150;
		$clr->setSortOrder($sortOrder);
		$clr->update();

		$clr2->retrieve();
		$this->assertEquals($sortOrder, $clr2->getSortOrder());
	}

	public function testRetrieve() {
		$clr = new ContactListResource();
		$clr->setName('testRetrieve' . time());
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());
		$clr2->retrieve();

		$this->assertEquals($clr->getId(), $clr2->getId());
		$this->assertEquals($clr->getName(), $clr2->getName());
	}

	public function testDelete() {
		$clr = new ContactListResource();
		$clr->setName('testDelete' . time());
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());

		$this->setExpectedException('UnexpectedValueException', 'Response code 404 did not match 200.');
		$clr->delete();

		$clr2->retrieve();
	}
}

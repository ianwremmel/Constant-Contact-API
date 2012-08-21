<?php

require_once 'lib/resource/contact.php';
require_once 'lib/resource/contact_list.php';
require_once 'config.php';

class ContactListResourceTest extends PHPUnit_Framework_TestCase {
	/**
	 * Create a ContactList with the minimum set of required fields.
	 */
	public function testCreate() {
		$clr = new ContactListResource();
		$clr->setName('testCreate' . microtime(true));
		$clr->create();

		$this->assertNotNull($clr->getId());
	}

	/**
	 * Toggle the OptInDefault field with the update action.
	 */
	public function testUpdateOptInDefault() {
		$clr = new ContactListResource();
		$clr->setName('testUpdateOptInDefault' . microtime(true));
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

	/**
	 * Change the Name field with the update action.
	 */
	public function testUpdateName() {
		$time = microtime(true);
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

	/**
	 * Change the SortOrder field with the update action.
	 */
	public function testUpdateSortOrder() {
		$clr = new ContactListResource();
		$clr->setName('testUpdateSortOrder' . microtime(true));
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

	/**
	 * Retrieve a ContactList.
	 */
	public function testRetrieve() {
		$clr = new ContactListResource();
		$clr->setName('testRetrieve' . microtime(true));
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());
		$clr2->retrieve();

		$this->assertEquals($clr->getId(), $clr2->getId());
		$this->assertEquals($clr->getName(), $clr2->getName());
	}

	/**
	 * Retrieve a ContactList by list name.
	 */
	public function testRetrieveByName() {
		// generate the name
		$name = 'testRetrieveByName' . microtime(TRUE);

		// create the list
		$clr = new ContactListResource();
		$clr->setName($name);
		$clr->create();

		$this->assertNotNull($clr->getId());

		// retrieve a copy of the list
		$clr2 = new ContactListResource();
		$clr2->setName($name);
		$clr2->retrieve();

		$this->assertEquals($clr->getId(), $clr2->getId());
	}

	/**
	 * Retrieve all ContactLists.
	 */
	public function testRetrieveBulk() {
		$clr = new ContactListResource();
		$lists = $clr->retrieve();

		$this->assertTrue(is_array($lists));
		$this->assertNotEmpty($lists);

		foreach ($lists as $list) {
			$this->assertInstanceOf('ContactListResource', $list);
		}
	}

	/**
	 * Delete a ContactList.
	 */
	public function testDelete() {
		$clr = new ContactListResource();
		$clr->setName('testDelete' . microtime(true));
		$clr->create();

		$clr2 = new ContactListResource();
		$clr2->setId($clr->getId());

		$this->setExpectedException('UnexpectedValueException', 'Response code 404 did not match 200.');
		$clr->delete();

		$clr2->retrieve();
	}

	/**
	 * Create a ContactList, create Contacts as members of the list, and
	 * retrieve them using the ContactList's members() method.
	 */
	public function testMembers() {
		// Create a list
		$clr = new ContactListResource();
		$clr->setName('testMembers' . microtime(TRUE));
		$clr->create();

		// Add a contact to the list
		$cr = new ContactResource();
		$cr->setEmailAddress('testMembers' . microtime(TRUE) . '@mailinator.com');
		$cr->setOptInSource(ContactResource::ACTION_BY_CUSTOMER);
		$cr->addList($clr->getId());
		$cr->create();

		// Refresh the list
		$clr->retrieve();

		// Ensure that the list has a member
		$this->assertEquals($clr->getContactCount(), 1);

		// Get the list's members
		$members = $clr->members();

		$this->assertTrue(is_array($members));
		$this->assertNotEmpty($members);
		$this->assertCount(1, $members);

		// Ensure the list's members are contact resources
		foreach ($members as $member) {
			$this->assertInstanceOf('ContactResource', $member);
		}

		$member = $members[0];
		// Ensure the list member is the correct ContactResource.
		$this->assertEquals($cr->getId(), $member->getId());

		// Add a second contact to the list and repeat
		// TODO everything below here probably belongs in a separate test
		// Add a contact to the list
		$cr = new ContactResource();
		$cr->setEmailAddress('testMembers' . microtime(TRUE) . '@mailinator.com');
		$cr->setOptInSource(ContactResource::ACTION_BY_CUSTOMER);
		$cr->addList($clr->getId());
		$cr->create();

		// Refresh the list
		$clr->retrieve();

		// Ensure that the list has a member
		$this->assertEquals($clr->getContactCount(), 2);

		// Get the list's members
		$members = $clr->members();

		$this->assertTrue(is_array($members));
		$this->assertNotEmpty($members);
		$this->assertCount(2, $members);

		// Ensure the list's members are contact resources
		foreach ($members as $member) {
			$this->assertInstanceOf('ContactResource', $member);
		}
	}

	/**
	 * Create a ContactList, create Contacts as members of the list, and
	 * retrieve them using the ContactList's members() method with the $full
	 * parameter set to TRUE.
	 */
	public function testMembersFull() {
		// Create a list
		$clr = new ContactListResource();
		$clr->setName('testMembers' . microtime(TRUE));
		$clr->create();

		// Add a contact to the list
		$cr = new ContactResource();
		$cr->setEmailAddress('testMembers' . microtime(TRUE) . '@mailinator.com');
		$cr->setOptInSource(ContactResource::ACTION_BY_CUSTOMER);
		$cr->addList($clr->getId());
		$cr->create();

		// Refresh the list
		$clr->retrieve();

		// Ensure that the list has a member
		$this->assertEquals($clr->getContactCount(), 1);

		// Get the list's members
		$members = $clr->members(TRUE);

		$this->assertTrue(is_array($members));
		$this->assertNotEmpty($members);
		$this->assertCount(1, $members);

		// Ensure the list's members are contact resources
		foreach ($members as $member) {
			$this->assertInstanceOf('ContactResource', $member);
		}

		$member = $members[0];
		// Ensure the list member is the correct ContactResource.
		$this->assertEquals($cr->getId(), $member->getId());
	}
}

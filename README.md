Constant-Contact-API [![No Maintenance Intended](http://unmaintained.tech/badge.svg)](http://unmaintained.tech/)
================================================================================================================

Object-oriented, resource-based interface to the Constant Contact REST API written for PHP 5.

This library attempts to conform to ReSTful resource semantics by providing classes to represent the various Constant Contact resource types as well as CRUD operations for each.

Those resources implemented in lib/resource (activity, contact, contact_list) are adequately complete for most use cases however, this library does not implement all resources provided by the API.

Motivation
----------
Constant Contact's recommended PHP library appears to be severely influenced by PHP4. Initial attempts to use it failed and limited documentation combined with a blatant lack of error output made debugging difficult. Rather than spend the time to debug an outdated and seemingly broken library, it made more sense to figure out the Constant Contact API by writing a new library.

Getting Started
---------------

You'll need a Constant Contact username, password, and API key. All can be gotten for free from the Constant Contact website (although after sixty days or 100 list members, your ability to send email will become severely limited).

The tests generally provide clear examples on how to execute basic actions like creating Contacts or Contact Lists.

### Configuration
Somewhere in your code, you'll need to define CC_API_KEY, CC_API_USERNAME, and CC_API_PASSWORD. See Running the Tests for more information on how to get these values.

### Magic Methods
The majority of the property manipulation methods (e.g. getters and setters) are implemented via __call(). As such, the property names are not documented within the code; at this time, the easiest way to figure out all of the method names is to point a web browser at a Constant Contact resource and take a look at the XML.

The Resource can be accessed via HTTP Basic Auth by visiting the URI below.
- RESOURCETYPE will be one of 'lists', 'contacts', etc.
- IDENTIFIER will typically be numeric.

```
https://USERNAME%APIKEY:PASSWORD@api.constantcontact.com/ws/customers/USERNAME/RESOURCETYPE/IDENTIFIER
```

### Bulk Retrieval
The classes in the lib/resource directory represent the various resources provided by Constant Contact. Typically, an instance of a resource represents a single resource in Constant Contact, but if no identifier has been set on the local instance, then a call to retrieve() will return all of the instances of that type.

Running the Tests
-----------------

You should only run the tests against a test Constant Contact account. The tests create a lot of users so they could use up your quota fairly quickly. In addition, when you sign up for a test account, use it to send your self a few emails before doing much else. Once you add more than 100 contacts (active or otherwise), you lose the ability to send email without paying.

1. Copy tests/config.php.sample to tests/config.php and fill in the empty values.
	- CC_API_KEY must be requested from the Constant Contact dashboard.
	- CC_API_USERNAME and CC_API_PASSWORD are the username and password that you use to log into Constant Contact.
	- USER_ONE_EMAIL is the email address used to sign up for the Constant Contact account.  This requirement will be removed in the future.
2. Execute PHPUnit against the tests directory.

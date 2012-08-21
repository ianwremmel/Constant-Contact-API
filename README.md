Constant-Contact-API
====================

Object-oriented, resource-based interface to the Constant Contact REST API written for PHP 5.

This library attempts to conform to the ReSTful resource semantics by providing classes to represent the various Constant Contact resource types as well as CRUD operations for each.

Motivation
----------
Constant Contact recommends a library that is very much influenced by PHP4. The library has very little documentation and covers up most of the errors from Constant Contact with generic messaging (or simply a true/false indicator of success). In fact, when attempting to use the Drupal module based on it (and written by the same company), it didn't seem to work at all. Rather than spend the time to debug a seemingly broken and outdated library without understanding the Constant Contact API, it made more sense to learn the API by creating a new library designed for PHP5.

Getting Started
---------------

You'll need a Constant Contact username, password, and API key. All can be gotten for free from the Constant Contact website (although after sixty days or 100 list members, your ability to send email will become severely limited).

The tests generally provide clear example on how to execute basic actions like creating Contacts or Contact Lists.

### Configuration
Somewhere in your code, you'll need to define CC_API_KEY, CC_API_USERNAME, and CC_API_PASSWORD. See Running the Tests for more information on how to get these values.

### Magic Methods
The majority of the property manipulation methods (e.g. getters and setters) are implemented via __call(). As such, the property names are not documented within the code; at this time, the easiest way to figure out all of the method names is to point a web browser at a Constant Contact resource and take a look at the XML.

The Resource can be accessed via HTTP Basic Auth by visiting the URI below.
-RESOURCETYPE will be one of 'lists', 'contacts', etc
-IDENTIFIER will typically be numeric

https://USERNAME%APIKEY:PASSWORD\@api.constantcontact.com/ws/customers/USERNAME/RESOURCETYPE/IDENTIFIER

### Bulk Retrieval
The classes in the lib/resource directory represent the various resources provided by Constant Contact. Typically, an instance of a resource represents a single resource in Constant Contact, but if no identifier has been set on the local instance, then a call to retrieve() will return all of the instances of that type.

Running the Tests
-----------------

1. Copy tests/config.php.sample to tests/config.php and fill in the empty values.
	- CC_API_KEY must be requested from the Constant Contact dashboard.
	- CC_API_USERNAME and CC_API_PASSWORD are the username and password that you use to log into Constant Contact.
	- USER_ONE_EMAIL is the email address used to sign up for the Constant Contact account.  This requirement will be removed in the future.
2. Execute PHPUnit against the tests directory.

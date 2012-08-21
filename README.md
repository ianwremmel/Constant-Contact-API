Constant-Contact-API
====================

Object-oriented, resource-based interface to the Constant Contact REST API
written for PHP 5.

Motivation
----------
Constant Contact recommends a library that is very much influenced by PHP4.  
The library has very little documentation and covers up most of the errors 
from Constant Contact with generic messaging (or simply a true/false 
indicator of success).  In fact, when attempting to use the Drupal module
based on it (and written by the same company), it didn't seem to work at all.

This library attempts to conform to the ReSTful resource semantics by providing
classes to represent the various Constant Contact resource types as well as 
CRUD operations for each. 

Getting Started
---------------

You'll need a Constant Contact username, password, and API key. All can be
gotten for free from the Constant Contact website (although after sixty days or
100 list members, your ability to send email will become severely limited).

The tests generally provide clear example on how to execute basic actions like
creating Contacts or Contact Lists.

### Magic Methods
The majority of the property manipulation methods (e.g. getters and setters) 
are implemented via __call(). As such, the property names are not documented 
within the code; at this time, the easiest way to figure out all of the method
names is to point a web browser at a Constant Contact resource and take a look
at the XML.

The Resource can be accessed via HTTP Basic Auth by visiting the URI below.
-RESOURCETYPE will be one of 'lists', 'contacts', etc
-IDENTIFIER will typically be numeric

https://USERNAME%APIKEY:PASSWORD@api.constantcontact.com/ws/customers/USERNAME/RESOURCETYPE/IDENTIFIER

Running the Tests
-----------------

1. Copy tests/config.php.sample to tests/config.php and fill in the empty
   values.
	- CC_API_KEY must be requested from the Constant Contact dashboard.
	- CC_API_USERNAME and CC_API_PASSWORD are the username and password that you
		use to log into Constant Contact.
	- USER_ONE_EMAIL is the email address used to sign up for the Constant
		Contact account.  This requirement will be removed in the future.
2. Execute PHPUnit against the tests directory.

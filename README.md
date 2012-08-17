Constant-Contact-API
====================

Object-oriented, resource-based interface to the Constant Contact REST API written for PHP 5.

Running the Tests
=================

1. Copy tests/config.php.sample to tests/config.php and fill in CC_API_KEY, CC_API_USERNAME, CC_API_PASSWOR, and USER_ONE_EMAIL.
	- CC_API_KEY must be requested from the Constant Contact dashboard.
	- CC_API_USERNAME and CC_API_PASSWORD are the username and password that you use to log into Constant Contact.
	- USER_ONE_EMAIL is the email address used to sign up for the Constant Contact account.  This requirement will be removed in the future.
2. Execute PHPUnit against the tests directory.

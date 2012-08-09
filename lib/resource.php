<?php

require_once 'crud_interface.php';

abstract class Resource implements ICrud{

	protected $data = array();

	public function __call($method, $args) {
		$action = substr($method, 0, 3);
		$property = substr($method, 3);

		// Short-circuit the switch if nothing useful can come of it.  This code
		// might need to be removed depending on what other methods the method
		// is altered to handle in the future.
		if (!$property) {
			throw new BadMethodCallException("${method} does not exist");
		}

		switch ($action) {
			case 'get':
				return isset($this->data[$property]) ? $this->data[$property] : NULL;
				break;
			case 'set':
				$this->data[$property] = $args[0];
				break;
			default:
				throw new BadMethodCallException("${method} does not exist");
		}
	}

	public function __construct() {

	}
	public static function prettyPrintXml($xml) {
		$dom = @DOMDocument::loadXML($xml);
		if (!empty($dom)) {
			$dom->formatOutput = TRUE;
			return $dom->saveXML();
		}
	}

	public function create() {
		$ch = $this->twist();

		// bulk actions can use CURLOPT_POST since they are
		// application/x-www-form-urlencoded, but singular actions need
		// to use CURLOPT_CUSTOMREQUEST since they are
		// application/atom+xml.
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));

		$entry = new SimpleXMLElement('<entry></entry>');
		$entry->addAttribute('xmlns', 'http://www.w3.org/2005/Atom');

		$title = $entry->addChild('title', ' ');//, $this->objectType . ': ' . $this->getEmailAddress());
		$title->addAttribute('type', 'text');

		// $updated = $entry->addChild('updated', date(DATE_ATOM));
		$updated = $entry->addChild('updated', '2008-07-23T14:21:06.407Z');

		$entry->addChild('author', ' ');
		$entry->addChild('id', 'date: ,none');

		$entry->addChild('summary', $this->objectType)->addAttribute('type', 'text');


		$content = $entry->addChild('content');
		$content->addAttribute('type', 'application/vnd.ctct+xml');
		$object = $content->addChild($this->objectType);
		$object->addAttribute('xmlns', 'http://ws.constantcontact.com/ns/1.0/');


		if (!is_null($this->getId())) {
			$object->addAttribute('id', 'http://' . CC_API_URL . '/' . CC_API_USERNAME . '/' . $this->endpoint . '/' . $this->getId());
		}

		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				$children = $object->addChild($key);
				foreach ($value as $item) {
					// TODO can't have 'ContactList' below
					$child = $children->addChild('ContactList');
					$child->addAttribute('id', $item);
				}
			}
			else if (is_object($value)) {
			}
			else {
				$child = $object->addChild($key, $value);
			}
		}

		$postFields = $entry->asXML();
		print self::prettyPrintXml($postFields);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

		$this->execute($ch);
	}

	public function retrieve() {
		$ch = $this->twist();

		curl_setopt($ch, CURLOPT_HTTPGET, TRUE);

		$this->execute($ch);

		// if id is set, will get a single item. otherwise, will get all items
	}

	public function update() {
		$ch = $this->twist();

		// PUT via a custom request so that we don't need to use a file
		// resource
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_HTTPHEADER, 'ContentType: application/atom+xml');

		$this->execute($ch);
	}

	public function delete() {
		$ch = $this->twist();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, 'ContentType: text/html');

		$this->execute($ch);
	}


	/**
	 * Common code for setting up a cURL session
	 */
	protected function twist() {
		$url = 'https://' . CC_API_URL . '/' . CC_API_USERNAME . '/' . $this->endpoint;
		// Assumption: if the resource already has an ID, then we'll be
		// interacting with it explicitly and it always needs to be part of the
		// URL
		if (!is_null($this->getId())) {
			$url .= '/' . $this->getId();
		}

		// Initialize the cURL session
		$ch = curl_init($url);

		// Use Basic Auth
		// TODO switch to OAuth
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, CC_API_KEY . '%' . CC_API_USERNAME . ':' . CC_API_PASSWORD);

		// Set cURL to return the response instead of printing it
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		// Disable printing the HTTP response header since that's what
		// class.cc.php does.  Note: the API docs seem to indicate that the
		// response headers may carry useful information, so it may make sense
		// to reverse this option.
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		// curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);

		return $ch;
	}

	protected function execute($ch) {
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		print "response\n";
		print_r($response);
		print "info\n";
		print_r($info);
		print "error\n";
		print_r($error);
	}
}

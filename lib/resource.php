<?php

require_once 'crud_interface.php';

abstract class Resource implements ICrud{
	const ACTION_BY_CUSTOMER = 'ACTION_BY_CUSTOMER';
	const ACTION_BY_CONTACT = 'ACTION_BY_CONTACT';

	public static function prettyPrintXml($xml) {
		$dom = @DOMDocument::loadXML($xml);
		if (!empty($dom)) {
			$dom->formatOutput = TRUE;
			return $dom->saveXML();
		}
	}

	public static function generateIdString($endpoint, $id) {
		return 'http://' . CC_API_URL . '/' . CC_API_USERNAME . '/' . $endpoint . '/' . $id;
	}

	public static function extractIdFromString($idString) {
		$offset = strrpos($idString, '/') + 1;
		$id = substr($idString, $offset);
		return $id;
	}

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
		// exists so that all derived classes can call parent::__construct to
		// for future-proofing.
	}

	public function create() {
		$ch = $this->twist();

		// bulk actions can use CURLOPT_POST since they are
		// application/x-www-form-urlencoded, but singular actions need
		// to use CURLOPT_CUSTOMREQUEST since they are
		// application/atom+xml.
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));

		$xml = $this->__toXml();

		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		$response = $this->execute($ch, 201);

		$xml = new SimpleXMLElement($response);
		$id = self::extractIdFromString($xml->id);

		// $id is typically numeric, but certain special lists are identified by
		// strings.
		if (is_numeric($id)) {
			$id = intval($id);
		}
		$this->setId($id);
	}

	public function retrieve() {
		$ch = $this->twist();

		curl_setopt($ch, CURLOPT_HTTPGET, TRUE);

		$response = $this->execute($ch, 200);

		$xml = new SimpleXMLElement($response);
		if ($xml->getName() === 'feed') {
			// TODO ensure there is only one <entry>
			$xml = $xml->entry;
		}

		// Since we may have retrieved the resource with an email address, make
		// sure we assign the appropriate id
		$id = self::extractIdFromString($xml->id);
		// $id is typically numeric, but certain special lists are identified by
		// strings.
		if (is_numeric($id)) {
			$id = intval($id);
		}
		$this->setId($id);

		// turn xml into an array
		$xml = json_decode(json_encode($xml), true);


		foreach ($xml['content'][$this->objectType] as $key => $value) {
			// Skip the attributes element;
			if ($key === '@attributes') {
				continue;
			}
			if (is_array($value)) {
				// Empty arrays indicate null values
				if (empty($value)) {
					call_user_func(array($this, 'set' . $key), NULL);
				}
				else {
					foreach ($value as $item) {
						call_user_func(array($this, 'add' . $this->itemNodeNames[$key]), $item);
					}
				}
			}
			else {
				call_user_func(array($this, 'set' . $key), $value);
			}
		}

		// $objectType = $this->objectType;
		// $object = $xml->content->$objectType;
		// print_r($object);
		// // foreach ($object->Contact as $key => $value) {
			// // print 'key:' . $key;
			// // print PHP_EOL;
			// // print 'value:' . $value;
			// // print PHP_EOL;
			// // print PHP_EOL;
		// // }
		// print self::prettyPrintXml($object->asXML());

		// if id is set, will get a single item. otherwise, will get all items
	}

	public function update() {
		$ch = $this->twist();

		// PUT via a custom request so that we don't need to use a file
		// resource
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_HTTPHEADER, 'ContentType: application/atom+xml');

		// 204: No Content indicates success but no need to send a response
		// from the server.
		$this->execute($ch, 204);
	}

	public function delete() {
		$ch = $this->twist();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_HTTPHEADER, 'ContentType: text/html');

		// 204: No Content indicates success but no need to send a response
		// from the server.
		$this->execute($ch, 204);
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
			$id = $this->getId();
			if ($id{0} !== '?') {
				$url .= '/';
			}

			$url .= $id;
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

		curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);

		return $ch;
	}

	protected function execute($ch, $expectedCode = 200) {
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		// curl_close($ch);

		if ($info['http_code'] != $expectedCode) {
			print_r($response);
			print_r($info);
			print_r($error);

			throw new UnexpectedValueException("Response code ${info['http_code']} did not match ${expectedCode}.\nServer responded with message: ${error}\n");
		}

		return $response;
	}

	public function __toXml() {
		$entry = new SimpleXMLElement('<entry/>');
		$entry->addAttribute('xmlns', 'http://www.w3.org/2005/Atom');

		$title = $entry->addChild('title')->addAttribute('type', 'text');

		$updated = $entry->addChild('updated', date(DATE_ATOM));

		$author = $entry->addChild('author');

		$summary = $entry->addChild('summary', $this->objectType);
		$summary->addAttribute('type', 'text');

		$content = $entry->addChild('content');
		$content->addAttribute('type', 'application/vnd.ctct+xml'); //XXX

		$object = $content->addChild($this->objectType);
		$object->addAttribute('xmlns', 'http://ws.constantcontact.com/ns/1.0/');

		if (is_null($this->getId())) {
			$id = $entry->addChild('id', 'data:,none');
		}
		else {
			$idString = self::generateIdString($this->endpoint, $this->getId());

			$object->addAttribute('id', $idString);
			$id = $entry->addChild('id', $idString);
		}

		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				$children = $object->addChild($key);

				$itemNodeName = $this->itemNodeNames[$key];

				foreach ($value as $item) {
					$child = $children->addChild($itemNodeName); //XXX
					$child->addAttribute('id', $item);
				}
			}
			else if (is_object($value)) {
			}
			else {
				$child = $object->addChild($key, $value);
			}
		}

		return $entry->asXML();
	}
}

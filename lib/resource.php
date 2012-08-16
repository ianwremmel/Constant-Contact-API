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
			case 'add':
				$plurals = array_flip($this->itemNodeNames);
				if (!array_key_exists($property, $plurals)) {
					throw new BadMethodCallException("${property} is not a known list for this resource.");
				}
				$plural = $plurals[$property];

				// Make sure the value identifed by $plural exists and is an
				// array
				if (!array_key_exists($plural, $this->data) || !is_array($this->data[$plural])) {
					$this->data[$plural] = array();
				}

				// TODO do we need to make sure $args[0] has not already been added?
				$this->data[$plural][] = $args[0];
				break;
			case 'rem':
				$plurals = array_flip($this->itemNodeNames);
				if (!array_key_exists($property, $plurals)) {
					throw new BadMethodCallException("${property} is not a known list for this resource.");
				}
				$plural = $plurals[$property];

				// Make sure the value identifed by $plural exists and is an
				// array
				if (!array_key_exists($plural, $this->data) || !is_array($this->data[$plural])) {
					$this->data[$plural] = array();
				}

				$index = array_search($args[0], $this->data[$plural]);
				if ($index !== FALSE) {
					array_splice($this->data[$plural], $index, 1);
				}
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

		$xml = $this->__toXml();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		$response = $this->execute($ch, 201);

		$xml = new SimpleXMLElement($response);
		// TODO do we need to do additional processing on the XML object?
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
		// If we retrieved by a secondary identifier (e.g. email address), we
		// need to do some extra tweeks.
		if ($xml->getName() === 'feed') {
			// TODO ensure there is only one <entry>

			// First, the entry is a child of the main object rather than the
			// main object
			$xml = $xml->entry;

			// Then, make sure we use the primary id from here on out
			$id = self::extractIdFromString($xml->id);
			// $id is typically numeric, but certain special lists are
			// identified by strings.
			if (is_numeric($id)) {
				$id = intval($id);
			}
			$this->setId($id);
		}

		// Simple XML is is easy to manipulate, but not so easy to traverse, so
		// we'll use this little hack to get it into a form that's easier to
		// work with programmatically.
		$xmlArray = json_decode(json_encode($xml), TRUE);

		// Iterate over each field in the <content> object of the response
		foreach ($xmlArray['content'][$this->objectType] as $key => $value) {
			// We don't care about the node's attributes but everything else
			// is valuable.
			if ($key !== '@attributes') {
				if (is_array($value)) {
					// If value is empty, we need to unset something, but we're
					// we don't know for certain if that something is an array
					// or a scalar.
					if (empty($value) || (!self::is_assoc($value) && trim($value[0]) === '')) {
						// If $key is in $this->itemNodeNames, then we know it
						// is a field that we expect to be an array and we need
						// to set it as an empty array.
						if (in_array($key, $this->itemNodeNames)) {
							$this->data[$key] = array();
						}
						// But if we aren't expecting it to be an array, we can
						// unset it.
						else {
							unset($this->data[$key]);
						}
					}
					else {
						// We still might be in a single valued item
						if (count($value) === 1 && array_key_exists('@attributes', $value)) {
							call_user_func(array($this, 'set' . $key), $value['@attributes']['id']);
						}
						// but if not, we need to parse $value as an array
						else {
							// first, we need to remove any items that may already
							// exist for this node in the data array.
							call_user_func(array($this, 'set' . $key), array());

							$singular = $this->itemNodeNames[$key];

							// Due to the way the JSON hack detects true arrays (
							// e.g. numerically-indexed arrays), the only way to
							// figure out if the child contains a singular item is
							// to determine if the array is numerically indexed.
							// Assumption: all lists store URIs
							if (self::is_assoc($value[$singular])) {
								// single item
								$uri = $value[$singular]['@attributes']['id'];
								call_user_func(array($this, 'add'. $singular), $uri);
							}
							else {
								// item list
								foreach ($value[$singular] as $item) {
									$uri = $item['@attributes']['id'];
									call_user_func(array($this, 'add'. $singular), $uri);
								}
							}
						}
					}
				}
				else {
					call_user_func(array($this, 'set' . $key), $value);
				}
			}
		}
	}

	public function update() {
		$ch = $this->twist();

		// PUT via a custom request so that we don't need to use a file
		// resource
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

		$xml = $this->__toXml();

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		// 204: No Content indicates success but no need to send a response
		// from the server.
		$this->execute($ch, 204);
	}

	public function delete() {
		$ch = $this->twist();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

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

		curl_close($ch);

		if ($info['http_code'] != $expectedCode) {
			$e = new UnexpectedValueException("Response code ${info['http_code']} did not match ${expectedCode}.");
			$e->response = $response;
			$e->error = $error;
			$e->info = $info;

			throw $e;
		}

		return $response;
	}

	/**
	 * @todo clean up __toXml() to improve readability
	 */
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
					$child = $children->addChild($itemNodeName);
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

	static function is_assoc($array) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
}

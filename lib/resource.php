<?php

require_once 'crud_interface.php';

abstract class Resource implements ICrud {
	/**************************************************************************\
	 * CONSTANTS
	\**************************************************************************/
	const ACTION_BY_CUSTOMER = 'ACTION_BY_CUSTOMER';
	const ACTION_BY_CONTACT = 'ACTION_BY_CONTACT';

	const CC_API_URL = 'api.constantcontact.com/ws/customers';

	/**************************************************************************\
	 * STATIC METHODS
	\**************************************************************************/
	/**
	 * Retrieves the identifying component of a URI.
	 * @param string $idString a resource URI.
	 */
	public static function extractIdFromString($idString) {
		$offset = strrpos($idString, '/') + 1;
		$id = substr($idString, $offset);
		return $id;
	}

	/**
	 * Determines whether an array is associative based on a now-lost Stack
	 * Overflow post.
	 * @paramm array $array the array to check
	 * @return boolean whether or not the array is associative.
	 */
	static function is_assoc($array) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}

	/**************************************************************************\
	 * PUBLIC METHODS
	\**************************************************************************/
	/**
	 * Creates the magic methods get, set, add, and rem.
	 */
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

	/**
	 * Constructor. Accepts API credentials. if API credentils are not supplied,
	 * attempts to fall back to constants.
	 */
	public function __construct($username, $password, $apiKey) {
		$args = func_get_args();

		if (is_null($username)) {
			throw new InvalidArgumentException ('$username cannot be NULL');
		}

		if (is_null($password)) {
			throw new InvalidArgumentException ('$password cannot be NULL');
		}

		if (is_null($apiKey)) {
			throw new InvalidArgumentException ('$apiKey cannot be NULL');
		}

		$this->username = $username;
		$this->password = $password;
		$this->apiKey = $apiKey;
	}

	/**
	 * Parses a SimpleXMLElement into a local copy of a Constant Contact
	 * resource.  Does not directly interact with Constant Contact.
	 * @param SimpleXMLElement $xml the XML to parse.
	 * @todo createFromXml needs a name that won't be confused with the create()
	 * CRUD operation.
	 */
	public function createFromXml(SimpleXMLElement $xml) {
		// Then, make sure we use the primary id from here on out
		$id = self::extractIdFromString($xml->id);
		// $id is typically numeric, but certain special lists are
		// identified by strings.
		if (is_numeric($id)) {
			$id = intval($id);
		}
		$this->setId($id);

		// Simple XML is is easy to manipulate, but not so easy to traverse, so
		// we'll use this little hack to get it into a form that's easier to
		// work with programmatically.
		$xmlArray = json_decode(json_encode($xml), TRUE);

		// We can't generically predict the child of $xmlArray['content'], but
		// we can assume there will only be one child
		$childOfContent = array_pop($xmlArray['content']);

		// Iterate over each field in the <content> object of the response
		foreach ($childOfContent as $key => $value) {
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

	/**
	 * Converts $this into the atom-feed-embedded-custom-xml-format that the
	 * Constant Contact API requires.
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

	/**
	 * Generates a URI for a particular Constant Contact resource.
	 * @param string $endpoint The resource type (e.g. 'lists', 'contacts', etd)
	 * @param mixed $id a means by which to identify the resource.  Most of the
	 * time, this will be a numeric identifier, but in certain cases (e.g.
	 * specifying a contact by email address), may be a query string.
	 */
	public function generateIdString($endpoint, $id) {
		return 'http://' . self::CC_API_URL . '/' . $this->username . '/' . $endpoint . '/' . $id;
	}
	/**************************************************************************\
	 * PROPERTIES
	\**************************************************************************/
	/**
	 * API Username
	 * @var string
	 */
	protected $username = NULL;

	/**
	 * API Password
	 * @var string
	 */
	protected $password = NULL;

	/**
	 * API Password
	 * @var string
	 */
	protected $apiKey = NULL;

	/**
	 * Maps plural XML entity names to their singular child entity names.
	 * @var array
	 */
	protected $itemNodeNames = array();

	/**
	 * Stores all of the Resource's fields.
	 */
	protected $data = array();

	/**************************************************************************\
	 * CRUD METHODS
	\**************************************************************************/
	/**
	 * POSTs a resource into Constant Contact.
	 */
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

	/**
	 * GETs a resource from Constant Contact. If the 'id' field is set on $this,
	 * retrieve() will attempt to retrieve a single resource.  If not,
	 * retrieve() will operate in bulk mode and attempt to retrieve all
	 * resources (of the appropriate type).
	 * @param boolean $full Only used in bulk mode.  If TRUE, retrieve() will be
	 * called for each returned resource.  Note: this may be expensive.
	 */
	public function retrieve($full = FALSE) {
		// If we attempted to retrieve without an ID, then we should retrieve
		// the endpoint itself, which will typically list all of the items
		// available at that endpoint.
		if (is_null($this->getId())) {
			// treat any values set on this resource as query string parameters
			$query = array();
			foreach ($this->data as $key => $value) {
				$query[] = urlencode($key) . '=' . urlencode($value);
			}
			$query = '?' . (empty($query) ? NULL : implode('&', $query));

			$class = get_class($this);
			$resources = array();

			do {
				$ch = $this->twist($query);
				curl_setopt($ch, CURLOPT_HTTPGET, TRUE);

				$response = $this->execute($ch, 200);
				$xml = new SimpleXMLElement($response);

				foreach ($xml->entry as $entry) {
					$resource = new $class($this->username, $this->password, $this->apiKey); /* @var $resource Resource */
					$resource->createFromXml($entry);
					if ($full) {
						$resource->retrieve();
					}
					$resources[] = $resource;
				}

				$next = $this->findNextLink($xml);
				$query = $next;
			} while (!is_null($next));

			return $resources;
		}
		else {
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
				if (empty($xml)) {
					throw new RuntimeException('Could not retrieve resource with specified parameters.');
				}
			}

			$this->createFromXml($xml);
		}
	}

	/**
	 * PUTs an object into Constant Contact.
	 */
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

	/**
	 * DELETEs a Constant Contact resource.
	 */
	public function delete() {
		$ch = $this->twist();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		// 204: No Content indicates success but no need to send a response
		// from the server.
		$this->execute($ch, 204);
	}
	/**************************************************************************\
	 * HELPER METHODS
	\**************************************************************************/
	/**
	 * Common code for setting up a cURL session
	 * @param string $urlSuffix suffix to append to the URL after the id (if it exists) is appended.  Used by ContactListResource::members and ContactResource::events
	 */
	protected function twist($urlSuffix = NULL) {
		$url = 'https://' . self::CC_API_URL . '/' . $this->username . '/' . $this->endpoint;
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

		if (!is_null($urlSuffix)) {
			// TODO verify that $urlSuffix starts with either a slash or a
			// question mark
			$url .= $urlSuffix;
		}

		// Initialize the cURL session
		$ch = curl_init($url);

		// Use Basic Auth
		// TODO switch to OAuth
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . '%' . $this->username . ':' . $this->password);

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

	/**
	 * Executes a cURL session and checks the response code.
	 */
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
	 * Generic method for returning the set of objects reference by this
	 * resource.  This method should be wrapped by semantically meaningful
	 * methods in derived classes (e.g., ContactList::members()).
	 * @see ContactList::members()
	 * @param boolean $full If true, will call retrieve for each retrieved
	 * returned resource (note: this may be expensive).
	 */
	protected function objects($uriSuffix, $resourceClass, $resourceFile, $full = FALSE) {
		require_once($resourceFile);

		$querystring = $uriSuffix;
		$resources = array();

		do {
			$ch = $this->twist($querystring);
			$response = $this->execute($ch);

			$xml = new SimpleXMLElement($response);

			foreach ($xml->entry as $entry) {
				$resource = new $resourceClass($this->username, $this->password, $this->apiKey);
				$resource->createFromXml($entry);
				if ($full) {
					$resource->retrieve();
				}
				$resources[] = $resource;
			}
			$next = $this->findNextLink($xml);
			$querystring = $next;

		} while (!is_null($next));

		return $resources;
	}

	/**
	 * Finds the element that should (but doesn't seem to) match the xpath
	 * link[@rel=link]
	 * @return string|NULL if the link was found, the link is returned,
	 * otherwise a value of NULL indicates no link could be found.
	 */
	protected function findNextLink(SimpleXMLElement $xml) {
		// since xpath is being uncooperative, we'll convert to an array
		// to locate the link[@rel="next"] node.
		$xml = json_decode(json_encode($xml), TRUE);

		// Make sure we always have an array of links to iterate over, even if
		// there is only one value in the array.
		if (self::is_assoc($xml['link'])) {
			$links[] = $xml['link'];
		}
		else {
			$links = $xml['link'];
		}

		// now, find the link containing the next url
		foreach ($links as $link) {
			if (array_key_exists('rel', $link['@attributes']) && $link['@attributes']['rel'] === 'next') {
				$uri = $link['@attributes']['href'];
				return substr($uri, strpos($uri, '?'));
			}
		}

		// If we haven't returned yet, return NULL
		return NULL;
	}
}

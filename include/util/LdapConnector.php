<?php

class LdapConnector {

	/**
	 * The internally used LDAP connection identifier.
	 * This is useful to get the error message of the LDAP connection.
	 *
	 * @var resource The LDAP connection.
	 */
	protected $_connection;

	/**
	 * Indicates the encoding which should be used for DN and attribute
	 * string values.
	 *
	 * @var int
	 */
	protected $_protocolVersion;

	/**
	 * If PHP function "ldap_control_paged_results()" is available,
	 * this value indicates the size of each page fetched from LDAP server.
	 *
	 * @var int
	 */
	protected $_ldapSearchPageSize = 100;

	/**
	 * Proves whether the LDAP extension of PHP is enabled.
	 *
	 * @return bool
	 */
	public static function isLdapExtensionEnabled() {
		if (function_exists("ldap_connect"))
			return true;
		return false;
	}

	/**
	 * Authenticates a user against the LDAP server.
	 *
	 * @param string $host e.g. ldap://yourhost.com
	 * @param int $port e.g.: 389 for Global Directory
	 * @param string $userDN The entire DN of the user.
	 * @param string $password The password of the user.
	 * @param int $protocolVersion
	 *
	 * @return bool
	 */
	public static function authenticateUser($host, $port, $userDN, $password, $protocolVersion = 3) {
		$auth = new LdapConnector();
		try {
			if (!$auth->connect($host, $port, $protocolVersion))
				return false;
			if (!$auth->bind($userDN, $password))
				return false;
		} finally {
			$auth->close();
		}
		return true;
	}

	/**
	 * Open a connection to the given LDAP server.
	 *
	 * @param string $host The host can be a complete connection URL (ldap://myserver.internal:389/) or just the host's
	 *                     remote address (yourhost.com). If the second format is used, the $port must be given.
	 * @param int $port (default=0)
	 * @param int $protocolVersion Used to define encoding.
	 *                              See here for more info:
	 *     http://www.openldap.org/devel/cvsweb.cgi/~checkout~/doc/drafts/draft-ietf-ldapext-ldap-c-api-xx.txt [Page 4
	 *     End]
	 *
	 * @return bool
	 */
	public function connect($host, $port = 0, $protocolVersion = 2) {
		$this->_protocolVersion = $protocolVersion;

		if ($port === 0)
			$this->_connection = ldap_connect($host);
		else
			$this->_connection = ldap_connect($host, $port);

		if ($this->_connection)
			ldap_set_option($this->_connection, LDAP_OPT_PROTOCOL_VERSION, $protocolVersion);

		return !$this->_connection ? false : true;
	}

	/**
	 * Closes the ldap connection of this object.
	 *
	 * @return bool
	 *
	 * @todo ldap_unbind()...
	 */
	public function close() {
		if ($this->_connection && !ldap_close($this->_connection))
			return false;
		return true;
	}

	/**
	 * Tries to bind this object connection with the given user data.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function bind($username, $password) {
		$username = $this->prepareQueryString($username);
		$password = $this->prepareQueryString($password);
		if (!ldap_bind($this->_connection, $username, $password))
			throw new Exception("Can not bind with LDAP server");
		return true;
	}

	/**
	 * Gets the internal used connection resource.
	 *
	 * @return resource LDAP connection handle.
	 */
	public function getConnection() {
		return $this->_connection;
	}

	/**
	 * Returns the error string, if an error occured.
	 *
	 * @return string
	 */
	public function error() {
		return ldap_error($this->_connection);
	}

	/**
	 * Returns the error code, if an error occured.
	 *
	 * @return int
	 */
	public function errno() {
		return ldap_errno($this->_connection);
	}

	/**
	 * Prepares the encoding of a string before it is passed via LDAP protocol to server.
	 * This method have to be called on all DN and attribute string values before passing them to the server.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function prepareQueryString($str) {
		if ($this->_protocolVersion >= 3) {
			$str = $this->ensureUtf8Encoding($str);
		}
		else if ($this->_protocolVersion <= 2) {
			$str = $this->ensureUtf8Encoding($str);
		}
		return $str;
	}

	/**
	 * Prepares string data which were receive via response from LDAP server for usage.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function prepareResultString($str) {
		if ($this->_protocolVersion >= 3) {
			$str = $this->ensureUtf8Encoding($str);
		}
		else if ($this->_protocolVersion <= 2) {
			$str = $this->ensureUtf8Encoding($str);
		}
		return $str;
	}

	/**
	 * Reads a single entry via LDAP and returns it as an object with properties.
	 *
	 * @param string $base_dn
	 *          The base DN in which is to search.
	 * @param string $search_filter
	 *          The filter which is to use.
	 * @param array $return_attributes
	 *          The attributes of entries which should be fetched.
	 *
	 * @return stdClass object with property values defined by $return_attributes or FALSE
	 */
	public function objectRead($base_dn, $search_filter, $return_attributes) {
		$base_dn = $this->prepareQueryString($base_dn);
		$search_filter = $this->prepareQueryString($search_filter);

		$sr = ldap_read($this->_connection, $base_dn, $search_filter, $return_attributes);

		if ($sr) {
			$entries = ldap_get_entries($this->_connection, $sr);
			$entry = $entries[0];
			$u = $this->createObjectFromEntry($entry);
			return $u;
		}
		return false;
	}

	/**
	 * Searches for entries in the ldap.
	 *
	 * <b>Note:</b>
	 * Using PHP version < 5.4 will never return more than 1001 items.
	 * PHP 5.4 is required for large results.
	 *
	 * @param string $base_dn
	 *          The base DN in which is to search.
	 * @param string $search_filter
	 *          The filter which is to use.
	 * @param array $attributes
	 *          The attributes of entries which should be fetched.
	 *
	 * @param int $offset
	 * @param int $num
	 * @param int $ldapSizeLimit
	 *
	 * @return array of stdClass objects with property values defined by $return_attributes+"dn"
	 */
	public function objectSearch($base_dn, $search_filter, $attributes, $offset = 0, $num = -1, $ldapSizeLimit = 0) {
		$base_dn = $this->prepareQueryString($base_dn);
		$search_filter = $this->prepareQueryString($search_filter);

		$ret = array();
		$stop = false;
		$index = 0;
		$resultCount = 0;
		$pageCookie = "";
		if ($num > 0) {
			$this->_ldapSearchPageSize = $num;
		}
		do {
			if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
				ldap_control_paged_result($this->_connection, $this->_ldapSearchPageSize, true, $pageCookie);
			}

			// Start search in LDAP directory.
			$sr = ldap_search($this->_connection, $base_dn, $search_filter, $attributes, 0, $ldapSizeLimit);
			if (!$sr)
				break;

			// Get the found entries as array.
			$entries = ldap_get_entries($this->_connection, $sr);
			if (!$entries)
				break;

			$count = $entries["count"];
			for ($i = 0; $i < $count; ++$i, ++$index) {
				// Offset + Num handling.
				if ($index < $offset) {
					continue;
				}
				elseif ($num !== -1 && count($ret) >= $num) {
					$stop = true;
					break;
				}

				// A $entry (array) contains all attributes of a single dataset from LDAP.
				$entry = $entries[$i];

				// Create a new object which will hold the attributes.
				// And add the default attribute "dn".
				$o = self::createObjectFromEntry($entry);
				$ret[] = $o;
			}

			if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
				ldap_control_paged_result_response($this->_connection, $sr, $pageCookie);
			}
		} while ($pageCookie !== null && $pageCookie != "" && !$stop);
		return $ret;
	}

	public function objectSearchResultCount($base_dn, $search_filter) {
		$base_dn = $this->prepareQueryString($base_dn);
		$search_filter = $this->prepareQueryString($search_filter);

		$resultCount = 0;
		$pageCookie = "";
		do {
			if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
				ldap_control_paged_result($this->_connection, $this->_ldapSearchPageSize, true, $pageCookie);
			}

			// Start search in LDAP directory.
			$sr = ldap_search($this->_connection, $base_dn, $search_filter, array(), 0, 0);
			if (!$sr)
				break;

			// Get number of found results.
			$resultCount += ldap_count_entries($this->_connection, $sr);

			if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
				ldap_control_paged_result_response($this->_connection, $sr, $pageCookie);
			}
		} while ($pageCookie !== null && $pageCookie != "");
		return $resultCount;
	}

	/**
	 * Creates a stdClass object with a property for each attribute.
	 * For example:
	 * Entry ( "sn" => "Chuck Norris", "kick" => "Round house kick" )
	 * Will return the stdClass object with following properties:
	 * stdClass->sn
	 * stdClass->kick
	 *
	 * @return stdClass
	 */
	public function createObjectFromEntry(&$entry) {
		// Create a new user object which will hold the attributes.
		// And add the default attribute "dn".
		$u = new stdClass();
		$u->dn = $this->prepareResultString($entry["dn"]);

		// The number of attributes inside the $entry array.
		$att_count = $entry["count"];

		for ($j = 0; $j < $att_count; $j++) {
			$attr_name = $entry[$j];
			$attr_value = $entry[$attr_name];
			$attr_value_count = $entry[$attr_name]["count"];

			// Use single scalar object for the attr value.
			if ($attr_value_count == 1) {
				$attr_single_value = $this->prepareResultString($attr_value[0]);
				$u->$attr_name = $attr_single_value;
			}
			else {
				$attr_multi_value = array();
				for ($n = 0; $n < $attr_value_count; $n++) {
					$attr_multi_value[] = $this->prepareResultString($attr_value[$n]);
				}
				$u->$attr_name = $attr_multi_value;
			}
		}
		return $u;
	}

	public function ensureUtf8Encoding($str) {
		if (function_exists("mb_detect_encoding")) {
			if (mb_detect_encoding($str) == "UTF-8") {
				return $str;
			}
			else {
				return utf8_encode($str);
			}
		}
		return $str;
	}

	public function ensureUtf8Decoding($str) {
		if (function_exists("mb_detect_encoding")) {
			if (mb_detect_encoding($str) == "UTF-8") {
				return utf8_decode($str);
			}
			else {
				return $str;
			}
		}
		return $str;
	}
}

if (!function_exists("ldap_escape")) {
	function ldap_escape($str) {
		return $str;
	}
}
?>
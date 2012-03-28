<?php
/**
 * Exception class for all LDAP errors.
 *
 * @author Manuel Freiholz, insaneFactory.com
 */
class IF_Ldap_Exception extends Exception
{
	public function __construct($message="", $code=0, Exception $previous=null)
	{
		parent::__construct($message, $code, $previous);
	}
}

/**
 * This class provides basic abilities to open and communicate through a
 * connection with an LDAP server.
 *
 * The static method "isLdapExtensionEnabled()" should be invoked before
 * any other method of this class will be used.
 *
 * @author Manuel Freiholz, insaneFactory.com
 */
class IF_AbstractLdapConnector
{
	/**
	 * The internaly used LDAP connection identifier.
	 * This is useful to get the error message of the LDAP connection.
	 * @var link_identifier The LDAP connection.
	 */
	public $connection;

	/**
	 * Indicates the encoding which should be used for DN and attribute
	 * string values.
	 * @var string
	 */
	protected $protocolVersion;
	
	/**
	 * If PHP function "ldap_control_paged_results()" is available,
	 * this value indicates the size of each page fetched from LDAP server.
	 * @var int
	 */
	protected $ldapSearchPageSize = 100;

	/**
	 * Destructor.
	 * Closes open connections.
	 *
	 * @todo Make safety close...
	 */
	public function __destruct()
	{
		//$this->close();
	}

	/**
	 * Proves whether the LDAP extension of PHP is enabled.
	 *
	 * @return bool
	 */
	public static function isLdapExtensionEnabled()
	{
		if (function_exists("ldap_connect"))
			return true;
		return false;
	}

	/**
	 * Global static method which can be used to Authenticates an user against
	 * an LDAP server without further usage of the class.
	 *
	 * @param string $host
	 * @param int $port
	 * @param string $username The DN of the user.
	 * @param string $password The password to the specified user.
	 * @param int $protocolVersion
	 *
	 * @return bool true/false
	 */
	public static function authenticateUser($host, $port, $username, $password, $protocolVersion=3)
	{
		$auth = new IF_AbstractLdapConnector();

		if (!$auth->connect($host, $port, $protocolVersion))
			return false;

		if (!$auth->bind($username, $password))
			return false;

		$auth->close();
		return true;
	}

	/**
	 * Open a connection to the given LDAP server.
	 * The $host can be a URL "ldap://myserver.internal:389/"
	 * or just the $host "myserver.internal".
	 * If the second format is used, the $port must be given.
	 *
	 * @param string $host
	 * @param int $port (default=0)
	 * @param int $procotol_version (default=2)
	 *
	 * @return bool
	 */
	public function connect($host, $port=0, $protocol_version=2)
	{
		// Set encoding based on protocol version.
		// http://www.openldap.org/devel/cvsweb.cgi/~checkout~/doc/drafts/draft-ietf-ldapext-ldap-c-api-xx.txt [Page 4 End]
		$this->protocolVersion = $protocol_version;

		// Set host and port.
		if ($port == 0)
		{
			$this->connection = ldap_connect($host);
		}
		else
		{
			$this->connection = ldap_connect($host, $port);
		}

		// Set protocol version.
		if ($this->connection)
		{
			ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $protocol_version);
		}

		return !$this->connection ? false : true;
	}

	/**
	 * Closes the ldap connection of this object.
	 *
	 * @return bool
	 *
	 * @todo ldap_unbind()...
	 */
	public function close()
	{
		if ($this->connection)
		{
			if (!ldap_close($this->connection))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Trys to bind this object connection with the given user data.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 */
	public function bind($username, $password)
	{
		$username = $this->prepareQueryString($username);
		$password = $this->prepareQueryString($password);

		if (ldap_bind($this->connection, $username, $password))
		{
			return true;
		}
		return false;
	}

	/**
	 * Returns the error string, if an error occured.
	 *
	 * @return string
	 */
	public function error()
	{
		return ldap_error($this->connection);
	}

	/**
	 * Returns the error code, if an error occured.
	 *
	 * @return int
	 */
	public function errno()
	{
		return ldap_errno($this->connection);
	}

	/**
	 * Prepares the encoding of a string before it is passed via LDAP
	 * protocol to server. This method have to be called on all DN and attribute
	 * string values before passing them to the server.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected function prepareQueryString($str)
	{
		if ($this->protocolVersion >= 3)
		{
			$str = if_ensure_utf8_encoding($str);
		}
		else if ($this->protocolVersion <= 2)
		{
			$str = if_ensure_utf8_decoding($str);
		}
		return $str;
	}

	/**
	 * Prepares string data which were receive via response from LDAP server
	 * for usage.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected function prepareResultString($str)
	{
		if ($this->protocolVersion >= 3)
		{
			$str = if_ensure_utf8_encoding($str);
		}
		else if ($this->protocolVersion <= 2)
		{
			$str = if_ensure_utf8_encoding($str);
		}
		return $str;
	}

	/**
	 * Reads a single entry via LDAP and returns it as an object with properties.
	 *
	 * @param HANDLE $conn The ldap connection handle.
	 * @param string $base_dn The base DN in which is to search.
	 * @param string $search_filter The filter which is to use.
	 * @param array $return_attributes The attributes of entries which should be fetched.
	 *
	 * @return stdClass object with property values defined by $return_attributes or FALSE
	 */
	protected function objectRead($conn, $base_dn, $search_filter, $return_attributes)
	{
		$base_dn = $this->prepareQueryString($base_dn);
		$search_filter = $this->prepareQueryString($search_filter);

		$sr = ldap_read($conn, $base_dn, $search_filter, $return_attributes);

		if ($sr)
		{
			$entries = ldap_get_entries($conn, $sr);
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
	 * @param HANDLE $conn The ldap connection handle.
	 * @param string $base_dn The base DN in which is to search.
	 * @param string $search_filter The filter which is to use.
	 * @param array $return_attributes The attributes of entries which should be fetched.
	 * @param int $limit The maximum number of entries.
	 *
	 * @return array of stdClass objects with property values defined by $return_attributes+"dn"
	 */
	protected function objectSearch($conn, $base_dn, $search_filter, $return_attributes, $limit)
	{	
		$base_dn = $this->prepareQueryString($base_dn);
		$search_filter = $this->prepareQueryString($search_filter);

		$ret = array();
		$pageCookie = "";
		do {
			if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
				ldap_control_paged_result($conn, $this->ldapSearchPageSize, true, $pageCookie);
			}
			
			// Start search in LDAP directory.
			$sr = ldap_search($conn, $base_dn, $search_filter, $return_attributes, 0, $limit);
			if (!$sr)
				break;
			
			// Get the found entries as array.
			$entries = ldap_get_entries($conn,$sr);
			if (!$entries)
				break;

			$count = $entries["count"];
			for ($i = 0; $i < $count; ++$i) {
				// A $entry (array) contains all attributes of a single dataset from LDAP.
				$entry = $entries[$i];

				// Create a new object which will hold the attributes.
				// And add the default attribute "dn".
				$o = self::createObjectFromEntry($entry);
				$ret[] = $o;
			}
			
			if (function_exists("ldap_control_paged_result") && function_exists("ldap_control_paged_result_response")) {
				ldap_control_paged_result_response($conn, $sr, $pageCookie);
			}
		}
		while ($pageCookie !== null && $pageCookie != "");
		return $ret;
	}

	/**
	 * Creates a stdClass object with a property for each attribute.
	 * For example:
	 *   Entry ( "sn" => "Chuck Norris", "kick" => "Round house kick" )
	 * Will return the stdClass object with following properties:
	 *   stdClass->sn
	 *   stdClass->kick
	 *
	 * @return stdClass
	 */
	protected function createObjectFromEntry(&$entry)
	{
		// Create a new user object which will hold the attributes.
		// And add the default attribute "dn".
		$u = new stdClass;
		$u->dn = $this->prepareResultString($entry["dn"]);

		// The number of attributes inside the $entry array.
		$att_count = $entry["count"];

		for($j=0; $j<$att_count; $j++)
		{
			$attr_name = $entry[$j];
			$attr_value = $entry[$attr_name];
			$attr_value_count = $entry[$attr_name]["count"];

			// Use single scalar object for the attr value.
			if($attr_value_count == 1)
			{
				$attr_single_value = $this->prepareResultString($attr_value[0]);
				$u->$attr_name = $attr_single_value;
			}
			else
			{
				$attr_multi_value = array();
				for($n=0; $n<$attr_value_count; $n++)
				{
					$attr_multi_value[] = $this->prepareResultString($attr_value[$n]);
				}
				$u->$attr_name = $attr_multi_value;
			}
		}
		return $u;
	}
}
?>
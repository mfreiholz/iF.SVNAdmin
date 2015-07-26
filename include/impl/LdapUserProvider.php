<?php

class LdapUserProvider extends UserProvider {
	private $_connector = null;

	public function __construct($id, $config, SVNAdminEngine $engine) {
		parent::__construct($id, $config, $engine);
		$this->_flags[] = Provider::FLAG_REQUIRES_SYNC;
	}

	public function initialize() {
		return true;
	}

	public function getUsers($offset = 0, $num = -1) {
		$this->ensureOpen();

		$loginAttribute = strtolower($this->_config["attributes"][0]);
		$users = $this->_connector->objectSearch($this->_config["search_base_dn"], $this->_config["search_filter"], $this->_config["attributes"], $offset, (int)$num === -1 ? -1 : $num + 1);
		$usersCount = count($users);

		$list = new ItemList();
		$listItems = array();
		$begin = (int)$offset;
		$end = (int)$num === -1 ? $usersCount : (int)$offset + (int)$num;
		for ($i = $begin; $i < $end && $i < $usersCount; ++$i) {
			$obj = new User();
			$obj->initialize($users[$i]->dn, $users[$i]->$loginAttribute, $this->formatDisplayName($this->_config["display_name_format"], $users[$i]));
			$listItems[] = $obj;
		}

		$list->initialize($listItems, $usersCount > $end);
		return $list;
	}

	public function search($query, $offset = 0, $limit = -1) {
		$this->ensureOpen();

		$queryFilter = $this->_config["attributes"][0] . '=*' . ldap_escape($query) . '*';
		$searchFilter = '(&(' . $queryFilter . ')' . $this->_config["search_filter"] . ')';

		$loginAttribute = strtolower($this->_config["attributes"][0]);
		$entries = $this->_connector->objectSearch($this->_config["search_base_dn"], $searchFilter, $this->_config["attributes"], $offset, 50);
		$entriesCount = count($entries);

		$list = new ItemList();
		$listItems = array();
		$end = $entriesCount < $limit || $limit === -1 ? $entriesCount : $limit;
		for ($i = 0; $i < $end; ++$i) {
			$entry = $entries[$i];
			$u = new User();
			$u->initialize(/*$entry->dn*/
				$entry->$loginAttribute, $entry->$loginAttribute, $this->formatDisplayName($this->_config["display_name_format"], $entry));
			$listItems[] = $u;
		}
		$list->initialize($listItems, $entriesCount > $limit);
		return $list;
	}

	protected function ensureOpen() {
		if (!empty($this->_connector))
			return;
		// Connect to server.
		$this->_connector = new LdapConnector();
		if (!$this->_connector->connect($this->_config["host_url"], 0, $this->_config["protocol_version"]))
			throw new ProviderException('Can not connect to LDAP server.');
		if (!$this->_connector->bind($this->_config["bind_dn"], $this->_config["bind_password"]))
			throw new ProviderException('Can not bind with LDAP server.');
	}

	protected function formatDisplayName($format, $entry) {
		if (empty($format) || empty($entry))
			return null;
		$displayName = $format;
		$matches = array();
		$offset = 0;
		while (preg_match('/\%([A-Za-z0-9\-\_]+)/i', $displayName, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
			$attributeName = strtolower($matches[1][0]);
			if (!isset($entry->$attributeName)) {
				$offset = $matches[0][1] + strlen($matches[0][0]);
				continue;
			}
			$displayName = str_replace($matches[0][0], $entry->$attributeName, $displayName);
		}
		return $displayName;
	}

}
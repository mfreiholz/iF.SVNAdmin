<?php
class LdapGroupProvider extends GroupProvider {
  private $_config = null;
  private $_connector = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $conn = new LdapConnector();
    if (!$conn->connect($config["host_url"], 0, $config["protocol_version"])) {
      return false;
    }
    if (!$conn->bind($config["bind_dn"], $config["bind_password"])) {
      return false;
    }
    $this->_connector = $conn;
    $this->_config = $config;
    return true;
  }

  public function getGroups($offset = 0, $num = -1) {
    $loginAttribute = strtolower($this->_config["attributes"][0]);
    $entries = $this->_connector->objectSearch($this->_config["search_base_dn"], $this->_config["search_filter"], $this->_config["attributes"], $offset, $num + 1);
    $entriesCount = count($entries);

    $list = new ItemList();
    $listItems = array();
    $end = $entriesCount < $num ? $entriesCount : $num;
    for ($i = 0; $i < $end; ++$i) {
      $entry = $entries[$i];
      $o = new Group();
      $o->initialize(/*$entry->dn*/$entry->$loginAttribute, $entry->$loginAttribute, $this->formatDisplayName($this->_config["display_name_format"], $entry));
      $listItems[] = $o;
    }
    $list->initialize($listItems, $entriesCount > $num);
    return $list;
  }

  public function findGroup($id) {
    return new Group();
  }

  public function search($query, $offset = 0, $limit = -1) {
    $list = new ItemList();

    $queryFilter = $this->_config["attributes"][0] . '=*' . ldap_escape($query) . '*';
    $searchFilter = '(&(' . $queryFilter . ')' . $this->_config["search_filter"] . ')';

    $loginAttribute = strtolower($this->_config["attributes"][0]);
    $entries = $this->_connector->objectSearch($this->_config["search_base_dn"], $searchFilter, $this->_config["attributes"], $offset, 50);
    $entriesCount = count($entries);

    $listItems = array ();
    $end = $entriesCount < $limit || $limit === -1 ? $entriesCount : $limit;
    for ($i = 0; $i < $end; ++$i) {
      $entry = $entries[$i];
      $u = new Group();
      $u->initialize(/*$entry->dn*/$entry->$loginAttribute, $entry->$loginAttribute, $this->formatDisplayName($this->_config["display_name_format"], $entry));
      $listItems[] = $u;
    }
    $list->initialize($listItems, $entriesCount > $limit);
    return $list;
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
?>
<?php
class LdapUserProvider extends UserProvider {
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

  public function getUsers($offset = 0, $num = -1) {
    $list = new ItemList();

    $loginAttribute = strtolower($this->_config["attributes"][0]);
    $entries = $this->_connector->objectSearch($this->_config["search_base_dn"], $this->_config["search_filter"], $this->_config["attributes"], $offset, $num + 1);
    $entriesCount = count($entries);

    $listItems = array ();
    $end = $entriesCount < $num ? $entriesCount : $num;
    for ($i = 0; $i < $end; ++$i) {
      $entry = $entries[$i];
      $u = new User();
      $u->initialize($entry->dn, $entry->$loginAttribute, $this->formatDisplayName($this->_config["display_name_format"], $entry));
      $listItems[] = $u;
    }
    $list->initialize($listItems, $entriesCount > $num);
    return $list;
  }

  public function findUser($id) {
    return new User();
  }

  protected function formatDisplayName($format, $entry) {
    if (empty($format) || empty($entry))
      return null;
    $displayName = $format;
    $matches = array ();
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
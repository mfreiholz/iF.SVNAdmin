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
    $entries = $this->_connector->objectSearch($this->_config["search_base_dn"], $this->_config["search_filter"], $this->_config["attributes"], $offset, $num);

    $ret = array();
    foreach ($entries as &$entry) {
      $u = new Group();
      $u->initialize($entry->dn, $entry->$loginAttribute, $this->formatDisplayName($this->_config["display_name_format"], $entry));
      $ret[] = $u;
    }
    return $ret;
  }

  public function findGroup($id) {
    return new Group();
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
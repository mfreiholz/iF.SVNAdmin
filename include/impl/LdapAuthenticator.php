<?php
class LdapAuthenticator extends Authenticator {
  private $_connector = null;
  private $_config = null;

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

  public function authenticate($username, $password) {
    // Create filter. Example: sAMAccountName=ifmanuel
    $userNameFilter = $this->_config["attribute"] . "=" . $username;
    $finalFilter = "(&(" . $userNameFilter . ")" . $this->_config["search_filter"] . ")";

    // Search for a user, where the 'users_attributes' equals the $username.
    $found = $this->_connector->objectSearch($this->_config["search_base_dn"], $finalFilter, array(
        $this->_config["attribute"]
    ), 0, 1);

    if (!is_array($found) || count($found) <= 0)
      return false; // User not found.

    // The user has been found. Get the dn of the user and authenticate him/her now.
    return LdapConnector::authenticateUser($this->_config["host_url"], 0, $found[0]->dn, $password, $this->_config["protocol_version"]);
  }
}
?>
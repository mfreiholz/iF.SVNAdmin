<?php
class StaticAuthenticator extends Authenticator {
  private $_accounts = array();

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_accounts = $config["users"];
    return true;
  }

  public function authenticate($username, $password) {
    if (isset($this->_accounts[$username]) && $this->_accounts[$username] === $password) {
      return true;
    }
    return false;
  }

}
?>
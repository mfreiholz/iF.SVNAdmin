<?php
class Authenticator {

  public function initialize(SVNAdminEngine $engine, $config) {
    return true;
  }

  public function authenticate($username, $password) {
    return false;
  }

}
?>
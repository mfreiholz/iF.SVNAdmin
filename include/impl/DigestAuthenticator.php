<?php
class DigestAuthenticator extends Authenticator {
  private $_passwd = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_passwd = new Htdigest($config["file"], $config["realm"]);
    return $this->_passwd->init();
  }

  public function authenticate($username, $password) {
    if ($this->_passwd->authenticate($username, $password)) {
      return true;
    }
    return false;
  }
}
?>
<?php
class Authorizer {

  public function initialize(SVNAdminEngine $engine) {
    return true;
  }

  public function getRoles($offset, $num = -1) {
    return array ();
  }

  public function isAllowed($roleId, $module, $action) {
    return false;
  }

  public function isEditable() {
    return false;
  }

}
?>
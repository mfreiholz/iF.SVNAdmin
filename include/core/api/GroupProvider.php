<?php
class GroupProvider {

  public function initialize(SVNAdminEngine $engine, $config) {
  }

  public function getGroups($offset = 0, $num = -1) {
    return array();
  }

  public function findGroup($id) {
    return null;
  }

  public function isEditable() {
    return false;
  }

  public function create($name) {
    return null;
  }

  public function delete($id) {
    return false;
  }
}
?>
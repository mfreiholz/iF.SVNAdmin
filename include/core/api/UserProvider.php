<?php
class UserProvider {

  public function initialize(SVNAdminEngine $engine, $config) {
  }

  public function getUsers($offset = 0, $num = -1) {
    return new ItemList();
  }

  public function findUser($id) {
    return null;
  }

  public function isEditable() {
    return false;
  }

  public function create($name, $password) {
    return null;
  }

  public function delete($id) {
    return false;
  }

  public function changePassword($id, $password) {
    return false;
  }

  public function search($query, $offset = 0, $num = 10) {
    return new ItemList();
  }

}
?>
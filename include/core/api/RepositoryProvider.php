<?php
class RepositoryProvider {

  public function initialize(SVNAdminEngine $engine, $config) {
    return true;
  }

  public function getRepositories($offset, $num) {
    return array ();
  }

  public function findRepository($id) {
    return null;
  }

  public function isEditable() {
    return false;
  }

  public function create($name, $options = array ()) {
    return null;
  }

  public function delete($id) {
    return false;
  }

  public function getSvnAuthz($repositoryId) {
    return null;
  }

}
?>
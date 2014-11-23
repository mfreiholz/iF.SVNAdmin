<?php
class RepositoryProvider {

  /**
   * @param SVNAdminEngine $engine
   * @param array $config
   * @return bool
   */
  public function initialize(SVNAdminEngine $engine, $config) {
    return true;
  }

  /**
   * @param int $offset
   * @param int $num
   * @return array
   */
  public function getRepositories($offset, $num) {
    return array ();
  }

  /**
   * @param string $id
   * @return Repository
   */
  public function findRepository($id) {
    return null;
  }

  /**
   * @return bool
   */
  public function isEditable() {
    return false;
  }

  /**
   * @param string $name
   * @param array $options
   * @return Repository
   */
  public function create($name, $options = array ()) {
    return null;
  }

  /**
   * @param string $id
   * @return bool
   */
  public function delete($id) {
    return false;
  }

  /**
   * @param $id
   * @return SvnAuthzFile
   */
  public function getSvnAuthz($id) {
    return null;
  }

  /**
   * Gets an array with undefined variable information about the repository.
   * @param $id
   * @return array
   */
  public function getInfo($id) {
    return array();
  }

}
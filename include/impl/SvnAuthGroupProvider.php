<?php
class SvnAuthGroupProvider extends GroupProvider {
  private $_authfile = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_authfile = new SvnAuthFile();
    try {
      if (!$this->_authfile->open($config["file"])) {
        return false;
      }
    } catch (Exception $e) {
      return false;
    }
    return true;
  }

  public function getGroups($offset = 0, $num = -1) {
    $ret = array();
    $groups = $this->_authfile->groups();
    foreach ($groups as &$groupname) {
      $o = new Group();
      $o->initialize($groupname, $groupname);
      $ret[] = $o;
    }
    return $ret;
  }

  public function isEditable() {
    return true;
  }

  public function create($name) {
    if (empty($name)) {
      return null;
    }
    if (!$this->_authfile->createGroup($name)) {
      return null;
    }
    $this->_authfile->save();
    $o = new Group();
    $o->initialize($name, $name);
    return $o;
  }

  public function delete($id) {
    if (empty($id)) {
      return false;
    }
    if (!$this->_authfile->deleteGroup($id)) {
      return false;
    }
    $this->_authfile->save();
    return true;
  }
}
?>
<?php
class SvnAuthGroupProvider extends GroupProvider {
  private $_authfile = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_authfile = $engine->getSvnAuthzFile($engine->getConfig()["common"]["svn_authz_file"]);
    return !empty($this->_authfile);
  }

  public function getGroups($offset = 0, $num = -1) {
    $groups = $this->_authfile->getGroups();
    $groupsCount = count($groups);

    $list = new ItemList();
    $listItems = array ();
    $begin = (int) $offset;
    $end = (int) $num === -1 ? $groupsCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $groupsCount; ++$i) {
      $o = new Group();
      $o->initialize($groups[$i]->name, $groups[$i]->name);
      $listItems[] = $o;
    }
    $list->initialize($listItems, $groupsCount > $end);
    return $list;
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
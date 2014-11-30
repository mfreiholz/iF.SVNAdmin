<?php
class SvnAuthGroupProvider extends GroupProvider {
  private $_authz = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_authz = $engine->getSvnAuthzFile($config["svn_authz_file"]);
    return !empty($this->_authz);
  }

  public function getGroups($offset = 0, $num = -1) {
    $groups = $this->_authz->getGroups();
    $groupsCount = count($groups);

    $list = new ItemList();
    $listItems = array ();
    $begin = (int) $offset;
    $end = (int) $num === -1 ? $groupsCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $groupsCount; ++$i) {
      $listItems[] = SvnAuthzHelper::createGroupObject($groups[$i]);
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
    $authzGroup = SvnAuthzFileGroup::create($name);
    if ($this->_authz->addGroup($authzGroup) !== SvnAuthzFile::NO_ERROR) {
      return null;
    }
    if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($this->_authz)) {
      return null;
    }
    return SvnAuthzHelper::createGroupObject($authzGroup);
  }

  public function delete($id) {
    if (empty($id)) {
      return false;
    }
    $authzGroup = SvnAuthzFileGroup::create($id);
    if ($this->_authz->removeGroup($authzGroup) !== SvnAuthzFile::NO_ERROR) {
      return false;
    }
    if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($this->_authz)) {
      return false;
    }
    return true;
  }

}
?>
<?php
class SvnAuthUserGroupAssociater extends UserGroupAssociater {
  private $_authzfile = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $authzFilePath = $config["authzfile"];
    $this->_authzfile = $engine->getSvnAuthzFile($authzFilePath);
    if (empty($this->_authzfile)) {
      error_log("Can not load SvnAuthzFile (path=" . $authzFilePath . ")");
      return false;
    }
    return true;
  }

  public function getUsersOfGroup($groupId, $offset = 0, $num = -1) {
    $list = new ItemList();

    $users = $this->_authzfile->usersOfGroup($groupId);
    $usersCount = count($users);

    $listItems = array ();
    $begin = $offset;
    $end = (int) $num === -1 ? $usersCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $usersCount; ++$i) {
      $username = $users[$i];
      $obj = new User();
      $obj->initialize($username, $username);
      $listItems[] = $obj;
    }
    $list->initialize($listItems, $usersCount > $end);
    return $list;
  }

  public function getGroupsOfUser($userId, $offset = 0, $num = -1) {
    $list = new ItemList();

    $memberObj = $this->_authzfile->createMemberObject($userId);
    $groups = $this->_authzfile->getGroupsOfMember($memberObj);
    $groupsCount = count($groups);

    $listItems = array ();
    $begin = $offset;
    $end = (int) $num === -1 ? $groupsCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $groupsCount; ++$i) {
      $obj = new Group();
      $obj->initialize($groups[$i]->name, $groups[$i]->name);
      $listItems[] = $obj;
    }
    $list->initialize($listItems, $groupsCount > $end);
    return $list;
  }

  public function isEditable() {
    return true;
  }

  public function assign($userId, $groupId) {
    if ($this->_authzfile->addUserToGroup($groupId, $userId)) {
      return $this->_authzfile->save();
    }
    return false;
  }

  public function unassign($userId, $groupId) {
    $memberObj = $this->_authzfile->createMemberObject($userId);
    $groupObj = SvnAuthzFileGroup::create($groupId);
    if ($this->_authzfile->removeMember($groupObj, $memberObj) !== SvnAuthzFile::NO_ERROR) {
      return false;
    }
    if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($this->_authzfile)) {
      return false;
    }
    return true;
  }

}
?>
<?php
class SvnAuthUserGroupAssociater extends UserGroupAssociater {
  private $_authfile = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    if (isset($config["file"]) && !empty($config["file"])) {
      $this->_authfile = new SvnAuthFile();
      if (!$this->_authfile->open($config["file"])) {
        return false;
      }
    }
    if (!$this->_authfile && isset($config["file_id"]) && !empty($config["file_id"])) {
      $this->_authfile = $engine->getAuthFileById($config["file_id"]);
      if (!$this->_authfile) {
        return false;
      }
    }
    return true;
  }

  public function getUsersOfGroup($groupId, $offset = 0, $num = -1) {
    $list = new ItemList();

    $users = $this->_authfile->usersOfGroup($groupId);
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

    $groups = $this->_authfile->groupsOfUser($userId);
    $groupsCount = count($groups);

    $listItems = array ();
    $begin = $offset;
    $end = (int) $num === -1 ? $groupsCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $groupsCount; ++$i) {
      $groupname = $groups[$i];
      $obj = new Group();
      $obj->initialize($groupname, $groupname);
      $listItems[] = $obj;
    }
    $list->initialize($listItems, $groupsCount > $end);
    return $list;
  }

  public function isEditable() {
    return true;
  }

  public function assign($userId, $groupId) {
    if ($this->_authfile->addUserToGroup($groupId, $userId)) {
      return $this->_authfile->save();
    }
    return false;
  }

  public function unassign($userId, $groupId) {
    if ($this->_authfile->removeUserFromGroup($userId, $groupId)) {
      return $this->_authfile->save();
    }
    return false;
  }
}
?>
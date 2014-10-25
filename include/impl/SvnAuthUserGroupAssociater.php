<?php
class SvnAuthUserGroupAssociater extends UserGroupAssociater {
  private $_authzfile = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    if (!isset($config["authzfile"])) {
      error_log("Missing required parameter 'authzfile'");
      return false;
    }

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

    $groups = $this->_authzfile->groupsOfUser($userId);
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
    if ($this->_authzfile->addUserToGroup($groupId, $userId)) {
      return $this->_authzfile->save();
    }
    return false;
  }

  public function unassign($userId, $groupId) {
    if ($this->_authzfile->removeUserFromGroup($userId, $groupId)) {
      return $this->_authzfile->save();
    }
    return false;
  }

}
?>
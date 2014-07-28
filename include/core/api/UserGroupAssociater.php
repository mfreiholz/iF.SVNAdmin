<?php
class UserGroupAssociater {

  public function initialize(SVNAdminEngine $engine, $config) {
  }

  public function getUsersOfGroup($groupId, $offset = 0, $num = -1) {
    return new ItemList();
  }

  public function getGroupsOfUser($userId, $offset = 0, $num = -1) {
    return new ItemList();
  }

  public function assign($userId, $groupId) {
    return false;
  }

  public function unassign($userId, $groupId) {
    return false;
  }

}
?>
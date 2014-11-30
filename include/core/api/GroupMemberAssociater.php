<?php
class GroupMemberAssociater {

  public function initialize(SVNAdminEngine $engine, $config) {
  }

  public function getMembersOfGroup($groupId, $offset = 0, $num = -1) {
    return new ItemList();
  }

  public function getGroupsOfMember($memberId, $offset = 0, $num = -1) {
    return new ItemList();
  }

  public function isEditable() {
    return false;
  }

  public function assign($groupId, $memberId) {
    return false;
  }

  public function unassign($groupId, $memberId) {
    return false;
  }

}
?>
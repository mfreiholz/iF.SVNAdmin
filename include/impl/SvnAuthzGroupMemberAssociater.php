<?php
class SvnAuthzGroupMemberAssociater extends GroupMemberAssociater {
  private $_authz = null;

  public function initialize(SVNAdminEngine $engine, $config) {
    $authzFilePath = $config["svn_authz_file"];
    $this->_authz = $engine->getSvnAuthzFile($authzFilePath);
    if (empty($this->_authz)) {
      error_log("Can not load SvnAuthzFile (path=" . $authzFilePath . ")");
      return false;
    }
    return true;
  }

  public function getMembersOfGroup($groupId, $offset = 0, $num = -1) {
    $list = new ItemList();
    if (empty($groupId)) {
      error_log("Can not get members of group with empty group-id");
      return $list;
    }

    $authzGroup = SvnAuthzFileGroup::create($groupId);
    $authzMembers = $this->_authz->getMembersOfGroup($authzGroup);
    $authzMembersCount = count($authzMembers);

    $items = array();
    $begin = $offset;
    $end = (int) $num === -1 ? $authzMembersCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $authzMembersCount; ++$i) {
      $items[] = $this->createGroupMemberFromAuthzFileMember($authzMembers[$i]);
    }

    $list->initialize($items, $authzMembersCount > $end);
    return $list;
  }

  public function getGroupsOfMember($memberId, $offset = 0, $num = -1) {
    $list = new ItemList();
    if (empty($memberId)) {
      error_log("Can not get groups of member with empty member-id");
      return $list;
    }

    $authzMember = $this->_authz->createMemberObject($memberId);
    $authzGroups = $this->_authz->getGroupsOfMember($authzMember);
    $authzGroupsCount = count($authzGroups);

    $items = array ();
    $begin = $offset;
    $end = (int) $num === -1 ? $authzGroupsCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $authzGroupsCount; ++$i) {
      $items[] = $this->createGroupFromAuthzFileGroup($authzGroups[$i]);
    }

    $list->initialize($items, $authzGroupsCount > $end);
    return $list;
  }

  public function isEditable() {
    return true;
  }

  public function assign($groupId, $memberId) {
    $authzGroup = SvnAuthzFileGroup::create($groupId);
    $authzMember = $this->_authz->createMemberObject($memberId);
    if ($this->_authz->addMember($authzGroup, $authzMember) !== SvnAuthzFile::NO_ERROR) {
      return false;
    }
    if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($this->_authz)) {
      return false;
    }
    return true;
  }

  public function unassign($groupId, $memberId) {
    $authzGroup = SvnAuthzFileGroup::create($groupId);
    $authzMember = $this->_authz->createMemberObject($memberId);
    if ($this->_authz->removeMember($authzGroup, $authzMember) !== SvnAuthzFile::NO_ERROR) {
      return false;
    }
    if (!SVNAdminEngine::getInstance()->commitSvnAuthzFile($this->_authz)) {
      return false;
    }
    return true;
  }

  protected function createGroupMemberFromAuthzFileMember(SvnAuthzFileMember $authzMember) {
    $memberString = $authzMember->asMemberString();
    $prefix = substr($memberString, 0, 1);
    $gmId = $memberString;
    $gmName = $memberString;
    $gmType = null;
    if ($prefix === "@") {
      $gmName = substr($gmName, 1);
      $gmType = GroupMember::TYPE_GROUP;
    } else if ($prefix === "&") {
      $gmName = substr($gmName, 1);
      $gmType = GroupMember::TYPE_UNKNOWN;
    } else {
      $gmType = GroupMember::TYPE_USER;
    }
    return GroupMember::create($gmId, $gmName, "", $gmType);
  }

  protected function createGroupFromAuthzFileGroup(SvnAuthzFileGroup $authzGroup) {
    return Group::create($authzGroup->asMemberString(), $authzGroup->name, $authzGroup->name);
  }

}

<?php

class GroupMemberAssociater {

	protected $_id = null;
	protected $_config = null;
	protected $_engine = null;

	public function __construct($id, array $config, SVNAdminEngine $engine) {
		$this->_id = $id;
		$this->_config = $engine;
		$this->_engine = $config;
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
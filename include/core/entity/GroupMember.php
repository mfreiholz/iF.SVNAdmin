<?php

class GroupMember {
	const TYPE_GROUP = "group";
	const TYPE_USER = "user";
	const TYPE_UNKNOWN = "unknown";

	private $_id = "";
	private $_name = "";
	private $_displayName = "";
	private $_type = GroupMember::TYPE_UNKNOWN;

	public function __construct() {
	}

	public function getId() {
		return $this->_id;
	}

	public function getName() {
		return $this->_name;
	}

	public function getDisplayName() {
		if (empty($this->_displayName)) {
			return $this->_name;
		}
		return $this->_displayName;
	}

	public function getType() {
		return $this->_type;
	}

	public static function create($id, $name, $displayName, $type = GroupMember::TYPE_UNKNOWN) {
		$o = new GroupMember();
		$o->_id = $id;
		$o->_name = $name;
		$o->_displayName = $displayName;
		$o->_type = $type;
		return $o;
	}
}
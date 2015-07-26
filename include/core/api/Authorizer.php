<?php

class Authorizer {

	protected $_id = null;
	protected $_config = null;
	protected $_engine = null;

	public function __construct($id, array $config, SVNAdminEngine $engine) {
		$this->_id = $id;
		$this->_config = $engine;
		$this->_engine = $config;
	}

	public function getRoles($offset, $num = -1) {
		return array();
	}

	public function isAllowed($roleId, $module, $action) {
		return false;
	}

	public function isEditable() {
		return false;
	}

}
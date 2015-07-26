<?php

class Authenticator {

	protected $_id = null;
	protected $_config = null;
	protected $_engine = null;

	public function __construct($id, array $config, SVNAdminEngine $engine) {
		$this->_id = $id;
		$this->_config = $engine;
		$this->_engine = $config;
	}

	public function authenticate($username, $password) {
		return false;
	}

}
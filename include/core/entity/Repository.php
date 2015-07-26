<?php

class Repository {
	private $_id = null;
	private $_name = null;
	private $_displayName = null;
	private $_authzFilePath = null;

	public function __construct() {
	}

	public function initialize($id, $name, $displayName = null) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_displayName = $displayName;
	}

	public function getId() {
		return $this->_id;
	}

	public function getName() {
		return $this->_name;
	}

	public function getDisplayName() {
		if (!empty($this->_displayName))
			return $this->_displayName;
		return $this->_name;
	}

	public function setAuthzFilePath($path) {
		$this->_authzFilePath = $path;
	}

	public function getAuthzFilePath() {
		return $this->_authzFilePath;
	}

}
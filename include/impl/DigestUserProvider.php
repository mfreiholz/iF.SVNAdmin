<?php

class DigestUserProvider extends SearchableUserProvider {
	private $_passwd = null;

	public function __construct($id, $config, SVNAdminEngine $engine) {
		parent::__construct($id, $config, $engine);
		$this->_flags[] = Provider::FLAG_EDITABLE;

		$this->_passwd = new Htdigest($config["file"], $config["realm"]);
		if (!file_exists($config["file"]) && !touch($config["file"]))
			throw new ProviderException("Can not load or create user file (path=" . $config["file"] . ")");
		if (!$this->_passwd->init())
			throw new ProviderException("Can not load PASSWD file (path=" . $config["file"] . "; error=" . $this->_passwd->error() . ")");
	}

	public function initialize() {
		return true;
	}

	public function getUsers($offset = 0, $num = -1) {
		$users = $this->_passwd->getUserList();
		$usersCount = count($users);

		$list = new ItemList();
		$listItems = array();
		$begin = (int)$offset;
		$end = (int)$num === -1 ? $usersCount : (int)$offset + (int)$num;
		for ($i = $begin; $i < $end && $i < $usersCount; ++$i) {
			$username = $users[$i];
			$obj = new User();
			$obj->initialize($username, $username);
			$listItems[] = $obj;
		}

		$list->initialize($listItems, $usersCount > $end);
		return $list;
	}

	public function create($name, $password) {
		if (!$this->_passwd->createUser($name, $password))
			throw new ProviderException("Can not create user (message=" . $this->_passwd->error() . "; name=" . $name . ")");
		if (!$this->_passwd->writeToFile())
			throw new ProviderException("Can not write file (message=" . $this->_passwd->error() . ")");
		$o = new User();
		$o->initialize($name, $name);
		return $o;
	}

	public function delete($id) {
		if (empty($id)) {
			return false;
		}
		if (!$this->_passwd->deleteUser($id)) {
			return false;
		}
		if (!$this->_passwd->writeToFile()) {
			return null;
		}
		return true;
	}

	public function changePassword($id, $password) {
		if (empty($id) || empty($password)) {
			return false;
		}
		if (!$this->_passwd->changePassword($id, $password)) {
			return false;
		}
		if (!$this->_passwd->writeToFile()) {
			return null;
		}
		return true;
	}

}
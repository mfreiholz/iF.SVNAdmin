<?php

class RoleProvider {

	public function getRoles($offset = 0, $num = 10) {
		return array();
	}

	public function findRole($id) {
		return null;
	}

	public function isEditable() {
		return false;
	}

	public function create($name) {
		return null;
	}

	public function delete($id) {
		return false;
	}

}
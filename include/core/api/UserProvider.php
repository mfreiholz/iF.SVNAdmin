<?php

abstract class UserProvider extends Provider {

	public static function getWildcardUser() {
		$o = new User();
		$o->initialize("*", "*", "Everyone (*)");
		return $o;
	}

	/**
	 * @param int $offset
	 * @param int $num
	 *
	 * @return ItemList
	 */
	public abstract function getUsers($offset = 0, $num = -1);

	/**
	 * Gets a user by it's ID.
	 * UserProvider provides a very basic implementation for this method. It should be overridden by the sub class for
	 * best performance.
	 *
	 * @param $id
	 *
	 * @return User
	 */
	public function getUserById($id) {
		foreach ($this->getUsers(0, -1)->getItems() as &$u) {
			if ($u->getId() === $id)
				return $id;
		}
		return null;
	}

	/**
	 * @param $query
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array<User>
	 */
	public abstract function search($query, $offset = 0, $limit = -1);

	/**
	 * @param $name
	 * @param $password
	 *
	 * @throws Exception
	 */
	public function create($name, $password) {
		throw new NotYetImplementedException();
	}

	/**
	 * @param $id
	 *
	 * @throws Exception
	 */
	public function delete($id) {
		throw new NotYetImplementedException();
	}

	/**
	 * @param $id
	 * @param $password
	 *
	 * @throws Exception
	 */
	public function changePassword($id, $password) {
		throw new NotYetImplementedException();
	}

}
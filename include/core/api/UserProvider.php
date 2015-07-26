<?php

/**
 * Class UserProvider
 *
 * Base API to manage users.
 */
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
	 * UserProvider has a very basic implementation for this method.
	 * It should be overridden by the sub class for best performance.
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
	 * Creates a new user.
	 *
	 * @param $name
	 * @param $password
	 *
	 * @return User
	 *
	 * @throws Exception
	 */
	public function create($name, $password) {
		throw new NotYetImplementedException();
	}

	/**
	 * Deletes a User by it's ID.
	 *
	 * @param $id
	 *
	 * @return User
	 *
	 * @throws Exception
	 */
	public function delete($id) {
		throw new NotYetImplementedException();
	}

	/**
	 * Changes the password of a User.
	 *
	 * @param $id
	 * @param $password
	 *
	 * @return User
	 *
	 * @throws Exception
	 */
	public function changePassword($id, $password) {
		throw new NotYetImplementedException();
	}

}
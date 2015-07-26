<?php

/**
 * Class GroupProvider
 *
 * Base API to manage groups.
 */
abstract class GroupProvider extends Provider {

	/**
	 * @param int $offset
	 * @param int $num
	 *
	 * @return ItemList
	 *
	 * @throws Exception
	 */
	public abstract function getGroups($offset = 0, $num = -1);

	/**
	 * @param $query
	 * @param int $offset
	 * @param int $num
	 *
	 * @return ItemList
	 */
	public function search($query, $offset = 0, $num = -1) {
		$list = new ItemList();
		$foundEntities = array();
		foreach ($this->getGroups()->getItems() as &$entity) {
			if (stripos($entity->getId(), $query) !== false) {
				$foundEntities[] = $entity;
			}
			else if (stripos($entity->getName(), $query) !== false) {
				$foundEntities[] = $entity;
			}
			else if (stripos($entity->getDisplayName(), $query) !== false) {
				$foundEntities[] = $entity;
			}
		}
		$list->initialize($foundEntities, false);
		return $list;
	}

	/**
	 * Creates a new Group.
	 *
	 * @param $name
	 *
	 * @return Group The created group.
	 *
	 * @throws Exception
	 */
	public function create($name) {
		throw new NotYetImplementedException();
	}

	/**
	 * Deletes a Group by it's ID.
	 *
	 * @param $id
	 *
	 * @return Group The deleted group.
	 *
	 * @throws Exception
	 */
	public function delete($id) {
		throw new NotYetImplementedException();
	}

}
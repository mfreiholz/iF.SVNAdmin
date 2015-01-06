<?php
abstract class GroupProvider extends Provider {

  public function __construct($id) {
    parent::__construct($id);
  }

  /**
   * @param int $offset
   * @param int $num
   * @return ItemList
   */
  public abstract function getGroups($offset = 0, $num = -1);

  /**
   * @param $id
   * @return Group
   */
  public abstract function findGroup($id);

  public function search($query, $offset = 0, $num = -1) {
    $list = new ItemList();
    $foundEntities = array();
    foreach ($this->getGroups()->getItems() as &$entity) {
      if (stripos($entity->getId(), $query) !== false) {
        $foundEntities[] = $entity;
      } else if (stripos($entity->getName(), $query) !== false) {
        $foundEntities[] = $entity;
      } else if (stripos($entity->getDisplayName(), $query) !== false) {
        $foundEntities[] = $entity;
      }
    }
    $list->initialize($foundEntities, false);
    return $list;
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
?>
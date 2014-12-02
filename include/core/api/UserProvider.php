<?php
abstract class UserProvider extends Provider {

  public function __construct($id) {
    parent::__construct($id);
  }

  /**
   * @param int $offset
   * @param int $num
   * @return ItemList
   */
  public abstract function getUsers($offset = 0, $num = -1);

  /**
   * @param $id
   * @return User or NULL
   */
  public abstract function findUser($id);

  public function search($query, $offset = 0, $num = -1) {
    $list = new ItemList();
    $foundUsers = array();
    foreach ($this->getUsers()->getItems() as &$user) {
      if (stripos($user->getId(), $query) !== false) {
        $foundUsers[] = $user;
      } else if (stripos($user->getName(), $query) !== false) {
        $foundUsers[] = $user;
      } else if (stripos($user->getDisplayName(), $query) !== false) {
        $foundUsers[] = $user;
      }
    }
    $list->initialize($foundUsers, false);
    return $list;
  }

  public function isEditable() {
    return false;
  }

  public function create($name, $password) {
    return null;
  }

  public function delete($id) {
    return false;
  }

  public function changePassword($id, $password) {
    return false;
  }

}
?>
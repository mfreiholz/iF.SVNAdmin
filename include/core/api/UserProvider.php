<?php
abstract class UserProvider extends Provider {

  public function __construct($id) {
    parent::__construct($id);
  }

  public static function getWildcardUser() {
    $o = new User();
    $o->initialize("*", "*", "Everyone (*)");
    return $o;
  }

  /**
   * @param int $offset
   * @param int $num
   * @return ItemList
   */
  public abstract function getUsers($offset = 0, $num = -1);

  /**
   * @param $query
   * @param int $offset
   * @param int $limit
   * @return mixed
   */
  public abstract function search($query, $offset = 0, $limit = -1);

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
<?php
abstract class SearchableUserProvider extends UserProvider {

  public function search($query, $offset = 0, $limit = -1) {
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

}
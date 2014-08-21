<?php
class ItemList {
  private $_items = array ();
  private $_hasMore = false;

  public function __construct() {
  }

  public function initialize($items, $hasMore = false) {
    $this->_items = $items;
    $this->_hasMore = $hasMore;
  }

  public function getItems() {
    return $this->_items;
  }

  public function hasMore() {
    return $this->_hasMore;
  }

}
?>
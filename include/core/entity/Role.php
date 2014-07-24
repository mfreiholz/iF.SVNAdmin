<?php
class Role {
  private $id = null;

  public function __construct() {
  }

  public function initialize($id) {
    $this->id = $id;
  }

  public function getId() {
    return $this->id;
  }

}
?>
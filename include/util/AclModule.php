<?php
class AclModule {
  private $id = null;

  public function __construct($id) {
    $this->id = $id;
  }

  public function getId() {
    return $this->id;
  }

}
?>
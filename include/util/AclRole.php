<?php
class AclRole {
  private $id = null;
  private $description = null;

  public function __construct($id, $description = null) {
    $this->id = $id;
    $this->description = $description;
  }

  public function getId() {
    return $this->id;
  }

  public function getDescription() {
    return $this->description;
  }

}
?>
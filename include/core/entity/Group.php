<?php
class Group {
  private $_id = null;
  private $_name = null;
  private $_displayName = null;

  public function __construct() {
  }

  public function initialize($id, $name, $displayName = null) {
    $this->_id = $id;
    $this->_name = $name;
    $this->_displayName = $displayName;
  }

  public function getId() {
    return $this->_id;
  }

  public function getName() {
    return $this->_name;
  }

  public function getDisplayName() {
    if (!empty($this->_displayName)) {
      return $this->_displayName;
    }
    if (!empty($this->_name)) {
      return $this->_name;
    }
    return $this->_id;
  }
}
?>
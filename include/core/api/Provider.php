<?php
abstract class Provider {
  const FLAG_EDITABLE = "editable";
  const FLAG_REQUIRES_SYNC = "reqsync";

  protected $_id = null;
  protected $_flags = array();

  public function __construct($id = null) {
    $this->_id = $id;
  }

  public function getId() {
    return $this->_id;
  }

  public function getFlags() {
    return $this->_flags;
  }

  public function hasFlag($f) {
    return array_search($f, $this->_flags) !== false;
  }

  public abstract function initialize(SVNAdminEngine $engine, $config);

}
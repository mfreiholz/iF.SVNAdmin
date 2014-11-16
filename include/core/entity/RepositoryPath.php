<?php
class RepositoryPath {
  private $_path = null;

  public function __construct() {
  }

  public function initialize($path) {
    $this->_path = $path;
  }

  public function getPath() {
    return $this->_path;
  }

}
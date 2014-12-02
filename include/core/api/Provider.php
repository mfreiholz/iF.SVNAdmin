<?php
abstract class Provider {
  protected $_id = null;

  public function __construct($id = null) {
    $this->_id = $id;
  }

  /**
   * @param SVNAdminEngine $engine
   * @param $config
   * @return bool
   */
  public abstract function initialize(SVNAdminEngine $engine, $config);

  public function getId() {
    return $this->_id;
  }

}
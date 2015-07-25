<?php

class ProviderException extends Exception {
	public function __construct($message = "", $code = 0, $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}

abstract class Provider {
	const FLAG_EDITABLE = "editable";
	const FLAG_REQUIRES_SYNC = "reqsync";

	/*
	 * @var string
	 */
	protected $_id = null;

	/*
	 * @var array
	 */
	protected $_flags = array();

	/*
	 * @var array
	 */
	protected $_config = null;

	/*
	 * @var SVNAdminEngine
	 */
	protected $_engine = null;

	public function __construct($id, $config, SVNAdminEngine $engine) {
		$this->_id = $id;
		$this->_config = $config;
		$this->_engine = $engine;
	}

	public function getId() {
		return $this->_id;
	}

	public function getConfig() {
		return $this->_config;
	}

	public function getFlags() {
		return $this->_flags;
	}

	public function hasFlag($f) {
		return array_search($f, $this->_flags) !== false;
	}

	public abstract function initialize();

}
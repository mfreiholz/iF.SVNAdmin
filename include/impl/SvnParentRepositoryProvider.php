<?php
class SvnParentRepositoryProvider extends RepositoryProvider {
  private $_engine = null;
  private $_config = null;
  private $_directoryPath = "";
  private $_editable = false;

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_engine = $engine;
    $this->_config = $config;
    $this->_directoryPath = $config["path"];
    $this->_editable = true;
    return true;
  }

  public function getRepositories($offset, $num) {
    $ret = array ();
    $svn = new SvnBase();
    $paths = $svn->listRepositories($this->_directoryPath);
    foreach ($paths as $dirName) {
      $id = base64_encode($this->_directoryPath . "/" . $dirName);
      $name = $dirName;
      $o = new Repository();
      $o->initialize($id, $name);
      $ret[] = $o;
    }
    return $ret;
  }

  public function findRepository($id) {
    $path = base64_decode($id);
    if (!file_exists($path)) {
      return null;
    }
    $name = substr($path, strrpos($path, "/"));
    $o = new Repository();
    $o->initialize($id, $name);
    return $o;
  }

  public function isEditable() {
    return $this->_editable;
  }

  public function create($name, $options = array ("type" => "fsfs")) {
    $bin = $this->_engine->getSvnAdmin();
    if (!$bin) {
      return null;
    }
    if (!file_exists($this->_directoryPath)) {
      return null;
    }
    $path = $this->_directoryPath . "/" . $name;
    $type = isset($options["type"]) ? $options["type"] : "fsfs";
    if (!$bin->create($path, $type)) {
      return null;
    }
    $id = base64_encode($path);
    $o = new Repository();
    $o->initialize($id, $name);
    return $o;
  }

  public function delete($id) {
    $path = base64_decode($id);
    if (empty($path) || !file_exists($path) || !$this->_engine->getSvnAdmin()->isRepository($path)) {
      return false;
    }
    return $this->deleteDirectoryRecursive($path);
  }

  /**
   * Deletes an entire directory recursively.
   * Note: GLOB_MARK = Adds a ending slash to directory paths.
   *
   * @param string $path
   * @return boolean
   */
  protected function deleteDirectoryRecursive($path) {
    $files = glob($path . "/*");
    foreach ($files as $f) {
      if (is_dir($f)) {
        $this->deleteDirectoryRecursive($f);
      } else {
        chmod($f, 0777);
        unlink($f);
      }
    }
    if (is_dir($path)) {
      rmdir($path);
    }
    return true;
  }

}
?>
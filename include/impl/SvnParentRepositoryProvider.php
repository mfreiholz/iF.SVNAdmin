<?php
/**
 * Manages repositories inside a directory (flat).
 * Allows to list, create and delete repositories.
 *
 * Configurable
 * ============
 * `path`
 *   Absolute path to the directory.
 * `authzfile` (Optional)
 *   If given, all repositories will use this AuthzFile for authorization.
 *   Otherwise each repository will use it's own AuthzFile located in it's `conf/` directory.
 *
 */
class SvnParentRepositoryProvider extends RepositoryProvider {
  private $_engine = null;
  private $_config = null;
  private $_editable = false;
  private $_directoryPath = "";
  private $_authzFilePath = "";

  public function initialize(SVNAdminEngine $engine, $config) {
    $this->_engine = $engine;
    $this->_config = $config;
    $this->_editable = true;
    $this->_directoryPath = Elws::normalizeAbsolutePath($config["path"]);
    $this->_authzFilePath = Elws::normalizeAbsolutePath($config["svn_authz_file"]);
    return true;
  }

  public function getRepositories($offset, $num) {
    $svn = new SvnBase();
    $repos = $svn->listRepositories($this->_directoryPath);
    $reposCount = count($repos);

    $list = new ItemList();
    $listItems = array ();
    $begin = (int) $offset;
    $end = (int) $num === -1 ? $reposCount : (int) $offset + (int) $num;
    for ($i = $begin; $i < $end && $i < $reposCount; ++$i) {
      $listItems[] = $this->createRepositoryObject($this->_directoryPath . DIRECTORY_SEPARATOR . $repos[$i]);
    }
    $list->initialize($listItems, $reposCount > $end);
    return $list;
  }

  public function findRepository($id) {
    $path = base64_decode($id);
    if (!file_exists($path)) {
      return null;
    }
    return $this->createRepositoryObject($path);
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
    $path = $this->_directoryPath . DIRECTORY_SEPARATOR . $name;
    $type = isset($options["type"]) ? $options["type"] : "fsfs";
    if (!$bin->create($path, $type)) {
      return null;
    }
    return $this->createRepositoryObject($path);
  }

  public function delete($id) {
    $path = base64_decode($id);
    if (empty($path) || !file_exists($path) || !$this->_engine->getSvnAdmin()->isRepository($path)) {
      return false;
    }
    return $this->deleteDirectoryRecursive($path);
  }

  public function getSvnAuthz($repositoryId) {
    return SVNAdminEngine::getInstance()->getSvnAuthzFile();
  }

  /**
   * Creates an initializes an repository object by it's absolute path.
   *
   * @param string $path
   * @return Repository
   */
  protected function createRepositoryObject($path) {
    $path = Elws::normalizeAbsolutePath($path);
    $authzFilePath = Elws::normalizeAbsolutePath($this->getRepositoryAuthzFilePath($path));

    $repo = new Repository();
    $repo->initialize(base64_encode($path), basename($path));
    $repo->setAuthzFilePath($authzFilePath);
    return $repo;
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

  /**
   * Gets the path to the SvnAuthFile of the given repository.
   * If a global AuthzFilePath is given, it will be used for all repositories, otherwise
   * it falls back to the repository specific one located in "conf" folder.
   *
   * @param string $repositoryPath
   * @return string
   */
  protected function getRepositoryAuthzFilePath($repositoryPath) {
    if (empty($repositoryPath)) {
      return "";
    }
    if (!empty($this->_authzFilePath)) {
      return $this->_authzFilePath;
    }
    return $repositoryPath . DIRECTORY_SEPARATOR . "conf" . DIRECTORY_SEPARATOR . "authz";
  }

}
?>
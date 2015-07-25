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
 */
class SvnParentRepositoryProvider extends RepositoryProvider {
	private $_directoryPath = "";
	private $_authzFilePath = "";

	public function __construct($id, $config, $engine) {
		parent::__construct($id, $config, $engine);
		$this->_flags[] = Provider::FLAG_EDITABLE;
		$this->_directoryPath = Elws::normalizeAbsolutePath($config["path"]);
		$this->_authzFilePath = Elws::normalizeAbsolutePath($config["svn_authz_file"]);
	}

	public function initialize() {
		return true;
	}

	public function getRepositories($offset, $num) {
		$repos = $this->_engine->getSvn()->listRepositories($this->_directoryPath);
		$reposCount = count($repos);
		sort($repos);

		$list = new ItemList();
		$listItems = array();
		$begin = (int)$offset;
		$end = (int)$num === -1 ? $reposCount : (int)$offset + (int)$num;
		for ($i = $begin; $i < $end && $i < $reposCount; ++$i) {
			$listItems[] = $this->createRepositoryObject($repos[$i]);
		}
		$list->initialize($listItems, $reposCount > $end);
		return $list;
	}

	public function findRepository($id) {
		$path = $this->_directoryPath . DIRECTORY_SEPARATOR . $id;
		if (!file_exists($path)) {
			throw new ProviderException("Can not find repository (id=" . $id . "; path=" . $path . ")");
		}
		return $this->createRepositoryObject($id);
	}

	public function create($name, $options = array("type" => "fsfs")) {
		if (!file_exists($this->_directoryPath) && !mkdir($this->_directoryPath, 0777, true)) {
			throw new ProviderException("Can't create or access repository parent folder (path=" . $this->_directoryPath . ")");
		}
		$path = $this->_directoryPath . DIRECTORY_SEPARATOR . $name;
		$type = isset($options["type"]) ? $options["type"] : "fsfs";
		$this->_engine->getSvnAdmin()->svnCreate($path, $type);
		return $this->createRepositoryObject($name);
	}

	public function delete($id) {
		$path = $this->_directoryPath . DIRECTORY_SEPARATOR . $id;
		if (empty($path) || !file_exists($path) || !$this->_engine->getSvn()->isRepository($path)) {
			return false;
		}
		return $this->deleteDirectoryRecursive($path);
	}

	public function getSvnAuthz($repositoryId) {
		return SVNAdminEngine::getInstance()->getSvnAuthzFile();
	}

	public function getInfo($id) {
		$path = $this->_directoryPath . DIRECTORY_SEPARATOR . $id;
		if (!SVNAdminEngine::getInstance()->getSvn()->isRepository($path)) {
			return array();
		}
		$entry = SVNAdminEngine::getInstance()->getSvn()->svnInfo($path);
		if (empty($entry)) {
			return array();
		}
		return array(
			"kind" => $entry->kind,
			"name" => $entry->name,
			"revision" => $entry->revision,
			"author" => $entry->author,
			"date" => $entry->date
		);
	}

	/**
	 * Creates and initializes an repository object by it's name.
	 *
	 * @param string $name
	 *
	 * @return Repository
	 */
	protected function createRepositoryObject($name) {
		$path = Elws::normalizeAbsolutePath($this->_directoryPath . DIRECTORY_SEPARATOR . $name);
		$authzFilePath = Elws::normalizeAbsolutePath($this->getRepositoryAuthzFilePath($path));

		$repo = new Repository();
		$repo->initialize($name, $name, $name);
		$repo->setAuthzFilePath($authzFilePath);
		return $repo;
	}

	/**
	 * Deletes an entire directory recursively.
	 * Note: GLOB_MARK = Adds a ending slash to directory paths.
	 *
	 * @param string $dir
	 *
	 * @return boolean
	 */
	protected function deleteDirectoryRecursive($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir")
						$this->deleteDirectoryRecursive($dir . "/" . $object);
					else
						unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
		return true;
	}

	/**
	 * Gets the path to the SvnAuthFile of the given repository.
	 * If a global AuthzFilePath is given, it will be used for all repositories, otherwise
	 * it falls back to the repository specific one located in "conf" folder.
	 *
	 * @param string $repositoryPath
	 *
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
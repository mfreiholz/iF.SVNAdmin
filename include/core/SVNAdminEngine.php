<?php

class SVNAdminEngine {
	const REPOSITORY_PROVIDER = "repository";
	const USER_PROVIDER = "user";
	const GROUP_PROVIDER = "group";
	const GROUPMEMBER_PROVIDER = "groupmember";

	private static $_instance = null;
	private static $_svn = null;
	private static $_svnadmin = null;

	private $_config = null;
	private $_classPaths = array();
	private $_authenticators = array();

	/**
	 * Caches all created provider instances.
	 * Maps the Provider ID to it's object.
	 *
	 * @var array<string, Provider>
	 */
	private $_providers = array();

	/**
	 * Cache of all loaded SvnAuthzFiles.
	 * Use this global list to omit multiple file handle on same file.
	 * Key=Custom ID or path to file; Value=SvnAuthFile Object
	 *
	 * @var array<string, SvnAuthFile>
	 */
	private $_authzFiles = array();

	private function __construct($config) {
		$this->_config = $config;

		// Setup dynamic class loading.
		$this->_classPaths = array(SVNADMIN_BASE_DIR . "/include/core/api", SVNADMIN_BASE_DIR . "/include/core/entity", SVNADMIN_BASE_DIR . "/include/impl", SVNADMIN_BASE_DIR . "/include/util");
		spl_autoload_register(__NAMESPACE__ . "\\SVNAdminEngine::classLoader");
	}

	private function classLoader($className) {
		foreach ($this->_classPaths as $path) {
			$fp = $path . "/" . $className . ".php";
			if (file_exists($fp)) {
				include_once($fp);
				break;
			}
		}
	}

	public static function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new SVNAdminEngine((include SVNADMIN_BASE_DIR . "/config/main.php"));
		}
		return self::$_instance;
	}

	public function getConfig() {
		return $this->_config;
	}

	public function getSvnAuthzFile($path = "") {
		if (empty($path)) {
			$path = $this->_config["common"]["svn_authz_file"];
		}
		$path = Elws::normalizeAbsolutePath($path);

		// Get object from cache.
		if (isset($this->_authzFiles[$path])) {
			return $this->_authzFiles[$path];
		}

		// Create file, if it doesn't exists.
		if (!file_exists($path) && !touch($path)) {
			throw new Exception("Can not create non existing authz file (path=" . $path . ")");
		}

		// Create file object and add to cache.
		$obj = new SvnAuthzFile();
		if ($obj->loadFromFile($path) !== SvnAuthzFile::NO_ERROR) {
			unset($obj);
			return null;
		}
		$this->_authzFiles[$path] = $obj;
		return $obj;
	}

	public function commitSvnAuthzFile(SvnAuthzFile $authz) {
		if (empty($authz)) {
			return false;
		}
		// Copy to backup directory before overwriting it.
		$srcFilePath = $authz->getFilePath();
		$dstDirPath = SVNADMIN_DATA_DIR . DIRECTORY_SEPARATOR . "authz-backup" . DIRECTORY_SEPARATOR . md5($srcFilePath);
		if (!is_dir($dstDirPath) && !mkdir($dstDirPath, 0777, true)) {
			error_log("Can not create backup directory (path=" . $dstDirPath . ")");
		}
		else {
			$dt = new DateTime("now", new DateTimeZone("UTC"));
			$dstFileName = $dt->format("Y-m-d H-i-s") . substr((string)microtime(), 1, 8) . ".authz";
			$dstFilePath = $dstDirPath . DIRECTORY_SEPARATOR . $dstFileName;
			if (!copy($srcFilePath, $dstFilePath)) {
				error_log("Can not copy authz file to backup directory (path=" . $dstFilePath . ")");
			}
			// Delete old backups.
			$backupCount = (int)$this->_config["common"]["svn_authz_file_backup_count"];
			if ($backupCount > 0) {
				$entries = Elws::listDirectory($dstDirPath);
				rsort($entries, SORT_STRING);
				while (count($entries) > $backupCount) {
					$fileName = array_pop($entries);
					$filePath = realpath($dstDirPath . DIRECTORY_SEPARATOR . $fileName);
					if (!unlink($filePath)) {
						error_log("Can not delete old backup file (path=" . $filePath . ")");
					}
				}
			}
		}
		// Save changes.
		return $authz->writeToFile() === SvnAuthzFile::NO_ERROR;
	}

	public function getAuthenticators() {
		if (empty($this->_authenticators)) {
			foreach ($this->_config["authenticators"] as &$authConfig) {
				$id = $authConfig["id"];
				$className = $authConfig["class_name"];
				$obj = new $className();
				if ($obj->initialize($this, $authConfig)) {
					$this->_authenticators[$id] = $obj;
				}
			}
		}
		return $this->_authenticators;
	}

	/**
	 * Gets a list with all Provider objects of a specific type.
	 * This function does not call "initialize()" on any of the providers.
	 *
	 * @param $type e.g.: "user", "group", "repository", ...
	 *
	 * @return array
	 */
	public function getKnownProviders($type) {
		$l = array();
		$configs = $this->_config["providers"][$type];
		foreach ($configs as &$config) {
			// Check cache.
			$p = null;
			if (isset($this->_providers[$type][$config["id"]])) {
				$p = $this->_providers[$type][$config["id"]];
			}
			else {
				$p = new $config["class_name"]($config["id"]);
				$p->initialize($this, $config);
				$this->_providers[$type][$p->getId()] = $p;
			}
			$l[] = $p;

			// Create entity class.
			//$c = new stdClass();
			//$c->id = $p->getId();
			//$c->name = isset($config["name"]) ? $config["name"] : $p->getId();
			//$c->editable = $p->hasFlag(Provider::FLAG_EDITABLE);
			//$l[] = $c;
		}
		return $l;
	}

	/**
	 * Gets a specific provider.
	 *
	 * @param $type string
	 * @param $id string
	 *
	 * @return Provider
	 * @throws Exception
	 */
	public function getProvider($type, $id) {
		// Check cache.
		if (isset($this->_providers[$type][$id])) {
			return $this->_providers[$type][$id];
		}

		$conf = null;
		if (isset($this->_config["providers"][$type])) {
			foreach ($this->_config["providers"][$type] as &$c) {
				if ($c["id"] === $id) {
					$conf = $c;
					break;
				}
			}
		}
		if (!$conf)
			throw new Exception("Can not find configuration for Provider (type=" . $type . "; id=" . $id . ")");

		$p = new $conf["class_name"]($conf["id"]);
		$p->initialize($this, $conf);
		$this->_providers[$type][$p->getId()] = $p;
		return $p;
	}

	public function getGroupMemberAssociater($forProviderId) {
		$type = SVNAdminEngine::GROUPMEMBER_PROVIDER;
		// Search the associator.
		$foundId = null;
		foreach ($this->_config["providers"][$type] as $id => $conf) {
			foreach ($conf["for_provider"] as $pid) {
				if ($pid === $forProviderId) {
					$foundId = $id;
					break;
				}
			}
			if ($foundId !== null) {
				break;
			}
		}
		// Load the found associator.
		if ($foundId === null) {
			return null;
		}
		return $this->getProvider(SVNAdminEngine::GROUPMEMBER_PROVIDER, $foundId);
	}

	public function startMultiProviderSearch($type, array $providerIds, $query) {
		if (empty($type)) {
			return null;
		}
		$providers = array();
		if (empty($providerIds)) {
			foreach ($this->getKnownProviders($type) as $info) {
				$prov = $this->getProvider($type, $info->id);
				if (!empty($prov)) {
					$providers[] = $prov;
				}
			}
		}
		else {
			foreach ($providerIds as $id) {
				$prov = $this->getProvider($type, $id);
				if (!empty($prov)) {
					$providers[] = $prov;
				}
			}
		}
		if (empty($providers)) {
			error_log("No valid providers for search.");
			return null;
		}

		$list = new ItemList();
		if ($type === SVNAdminEngine::USER_PROVIDER && (empty($query) || $query === "*")) {
			$list->appendItem(UserProvider::getWildcardUser());
		}
		else {
			foreach ($providers as &$prov) {
				$searchResultList = $prov->search($query);
				foreach ($searchResultList->getItems() as &$item) {
					$item->providerid = $prov->getId();
				}
				$list->append($searchResultList);
			}
		}
		return $list;
	}

	/*public function getAssociaterForGroups($providerId) {
	  $type = SVNAdminEngine::USERGROUP_PROVIDER;
	  // Search the Associator.
	  $foundId = null;
	  foreach ($this->_config["providers"][$type] as $id => $conf) {
		foreach ($conf["for_groups"] as $userProviderId) {
		  if ($providerId === $userProviderId) {
			$foundId = $id;
			break;
		  }
		}
		if ($foundId !== null) {
		  break;
		}
	  }
	  // Load the found associator.
	  if ($foundId === null) {
		return null;
	  }
	  return $this->getProvider(SVNAdminEngine::USERGROUP_PROVIDER, $foundId);
	}*/

	/**
	 * @return SvnClient
	 */
	public function getSvn() {
		if (!static::$_svn) {
			static::$_svn = new SvnClient($this->_config["common"]["svn_binary_path"]);
			$configDir = $this->_config["common"]["svn_config_directory"];
			if (!empty($configDir)) {
				static::$_svn->setConfigDirectory($configDir);
			}
		}
		return static::$_svn;
	}

	/**
	 * @return SvnAdmin
	 */
	public function getSvnAdmin() {
		if (!static::$_svnadmin) {
			static::$_svnadmin = new SvnAdmin($this->_config["common"]["svnadmin_binary_path"]);
			$configDir = $this->_config["common"]["svn_config_directory"];
			if (!empty($configDir)) {
				static::$_svnadmin->setConfigDirectory($configDir);
			}
		}
		return static::$_svnadmin;
	}

}

?>

<?php
class SVNAdminEngine {
  const USER_PROVIDER = "user";
  const GROUP_PROVIDER = "group";
  const USERGROUP_PROVIDER = "usergroup";
  const REPOSITORY_PROVIDER = "repository";

  private static $_instance = null;
  private static $_svn = null;
  private static $_svnadmin = null;
  private $_config = null;
  private $_classPaths = array ();

  /**
   * Cache of all loaded SvnAuthFiles.
   * We use this global list to ommit multiple file handle on same file.
   * Key=ID; Value=SvnAuthFile Object
   *
   * @var array<string, SvnAuthFile>
   */
  private $_authfiles = array ();

  /**
   *
   * @var array<string, Authenticator>
   */
  private $_authenticators = array ();

  /**
   *
   * @var array
   */
  private $_providers = array ();

  private function __construct($config) {
    // Init configuration.
    $this->_config = $config;

    // Setup class loading.
    $this->_classPath = array (
        SVNADMIN_BASE_DIR . "/include/core/api",
        SVNADMIN_BASE_DIR . "/include/core/entity",
        SVNADMIN_BASE_DIR . "/include/impl",
        SVNADMIN_BASE_DIR . "/include/util"
    );
    spl_autoload_register(__NAMESPACE__ . "\SVNAdminEngine::classLoader");
  }

  private function classLoader($className) {
    foreach ($this->_classPath as $path) {
      $fp = $path . "/" . $className . ".php";
      if (file_exists($fp)) {
        include_once ($fp);
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

  public function getSvnAuthzFile($path) {
    $path = Elws::normalizeAbsolutePath($path);

    // Get it from cache.
    if (isset($this->_authfiles[$path])) {
      return $this->_authfiles[$path];
    }

    // Create file object and add to cache.
    $obj = new SvnAuthzFile();
    if ($obj->loadFromFile($path) !== SvnAuthzFile::NO_ERROR) {
      unset($obj);
      return null;
    }
    $this->_authfiles[$path] = $obj;
    return $obj;
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

  public function getKnownProviders($type) {
    $ret = array ();
    $configs = $this->_config["providers"][$type];
    foreach ($configs as $id => &$config) {
      $c = new stdClass();
      $c->id = $id;
      $ret[] = $c;
    }
    return $ret;
  }

  public function getProvider($type, $id) {
    $ret = null;
    if (!isset($this->_providers[$type][$id])) {
      if (isset($this->_config["providers"][$type][$id])) {
        $config = $this->_config["providers"][$type][$id];
        $className = $config["class_name"];
        $obj = new $className();
        if ($obj->initialize($this, $config)) {
          $this->_providers[$type][$id] = $obj;
          $ret = $obj;
        }
      }
    } else {
      $ret = $this->_providers[$type][$id];
    }
    return $ret;
  }

  public function getAssociaterForUsers($providerId) {
    $type = SVNAdminEngine::USERGROUP_PROVIDER;
    // Search the Associator.
    $foundId = null;
    foreach ($this->_config["providers"][$type] as $id => $conf) {
      foreach ($conf["for_users"] as $userProviderId) {
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
  }

  public function getAssociaterForGroups($providerId) {
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
  }

  public function getSvn() {
    if (!static::$_svn) {
      static::$_svn = new SvnClient($this->_config["common"]["svn_binary_path"]);
    }
    return static::$_svn;
  }

  public function getSvnAdmin() {
    if (!static::$_svnadmin) {
      static::$_svnadmin = new SvnAdmin($this->_config["common"]["svnadmin_binary_path"]);
    }
    return static::$_svnadmin;
  }

}
?>
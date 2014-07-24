<?php
/**
 * Provides functionality of the "svnadmin.exe" executable by using the
 * executable and parsing the output.
 *
 * @author Manuel Freiholz, insaneFactory
 */
class SvnAdmin extends SvnBase {
  /**
   * Path to the "svnadmin.exe" binary.
   *
   * @var string
   */
  private $_m_svnadmin = NULL;

  /**
   *
   * @param string $svn_admin_binary
   *          Absolute path to "svnadmin" executable.
   */
  public function __construct($svn_admin_binary) {
    parent::__construct();
    $this->_trust_server_cert = false;
    $this->_non_interactive = false;
    $this->_m_svnadmin = $svn_admin_binary;
  }

  /**
   * Creates a new empty repository.
   *
   * @param string $path
   *          Absolute path to the new repository
   * @param string $type
   *          Repository type: fsfs=file system(default); bdb=berkley db (not recommended)
   * @return bool
   */
  public function create($path, $type = "fsfs") {
    if (empty($path) || file_exists($path)) {
      return false;
    }

    // Validate repository name.
    $pattern = '/^([a-z0-9\_\-.]+)$/i';
    $repo_name = basename($path);

    if (!preg_match($pattern, $repo_name)) {
      return false;
      // throw new IF_SVNException('Invalid repository name: ' . $repo_name . ' (Allowed pattern: ' . $pattern . ')');
    }

    $args = array ();
    if (!empty($this->_config_directory)) {
      $args["--config-dir"] = escapeshellarg($this->_config_directory);
    }

    if (!empty($type)) {
      $args["--fs-type"] = escapeshellarg($type);
    }

    $cmd = self::create_svn_command($this->_m_svnadmin, "create", self::encode_local_path($path), $args, false);

    $output = null;
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    // if ($exitCode != 0) {
    // throw new Exception('Command=' . $cmd . '; Return=' . $exitCode . '; Output=' . $output . ';');
    // }
    return $exitCode === 0;
  }

  /**
   * Deletes the repository at the given path.
   *
   * @param string $path
   *          Path to the repository.
   * @return bool
   */
  public function delete($path) {
    $files = glob($path . "/*"/*, GLOB_MARK*/); // GLOB_MARK = Adds a ending slash to directory paths.
    foreach ($files as $f) {
      if (is_dir($f)) {
        self::delete($f);
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
   * Dump the contents of the given file-system
   *
   * @param string $path
   *          path to the repository.
   * @param string $file
   *          [optional]	If NULL the binary output of the dump
   *          comannd is directed to STDOUT (browser).
   *          Otherwise... not implemented.
   * @return bool
   */
  public function dump($path, $file = NULL) {
    if (empty($path)) {
      return false; // throw new IF_SVNException('Empty path parameter for dump() command.');
    }

    $args = array ();

    if (!empty($this->_config_directory)) {
      $args['--config-dir'] = escapeshellarg($this->_config_directory);
    }

    if ($file != NULL) {
      $args[] = '> ' . escapeshellarg($file);
    }

    $cmd = self::create_svn_command($this->_m_svnadmin, 'dump', self::encode_local_path($path), $args, false);

    if ($file != NULL) {
      // Not supported....
    } else {
      passthru($cmd);
    }
    return true;
  }

}
?>
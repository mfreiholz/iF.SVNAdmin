<?php
/**
 */
class SvnAdmin extends SvnBase {
  protected $_executable = null;

  public function __construct($executable) {
    parent::__construct();
    $this->_executable = $executable;
  }

  public function svnCreate($path, $type = "fsfs") {
    // Execute command.
    $localPath = $this->prepareRepositoryPath($path);
    $command = $this->prepareCommand($this->_executable, "create", $localPath, array("--fs-type" => $type));
    if ($this->executeCommand($command, $stdout, $stderr, $exitCode) !== SvnBase::NO_ERROR) {
      return SvnBase::ERROR_COMMAND;
    }
    return SvnBase::NO_ERROR;
  }

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
  /*public function dump($path, $file = NULL) {
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
  }*/

}
?>
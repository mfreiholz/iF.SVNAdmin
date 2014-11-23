<?php
/**
 */
class SvnBase {
  const NO_ERROR = 0;
  const ERROR_UNKNOWN = 1;

  protected $_isWindowsServer = false;
  protected $_configDirectory = "";
  protected $_username = "";
  protected $_password = "";

  public function __construct() {
    // Find out whether the system is based on Microsoft Windows.
    $os = PHP_OS;
    $os = strtoupper($os);
    if (stripos($os, "WIN") !== FALSE) {
      $this->_isWindowsServer = true;
    }
  }

  public function setConfigDirectory($path) {
    $this->_configDirectory = $path;
  }

  public function setUsername($username) {
    $this->_username = $username;
  }

  public function setPassword($password) {
    $this->_password = $password;
  }

  public function isRepository($path) {
    if (empty($path)) {
      return false;
    } else if (!is_dir($path)) {
      return false;
    } else if (!is_dir($path . DIRECTORY_SEPARATOR . "conf")) {
      return false;
    } else if (!is_dir($path . DIRECTORY_SEPARATOR . "db")) {
      return false;
    } else if (!is_dir($path . DIRECTORY_SEPARATOR . "hooks")) {
      return false;
    }
    return true;
  }

  public function listRepositories($basePath, array &$paths) {
    if (!is_dir($basePath)) {
      error_log("Invalid directory path (path=" . $basePath .")");
      return SvnBase::ERROR_UNKNOWN;
    }
    $dh = opendir($basePath);
    while (($fileName = readdir($dh)) !== false) {
      if ($fileName === "." || $fileName === "..") {
        continue;
      }
      $absolutePath = realpath($basePath . DIRECTORY_SEPARATOR . $fileName);
      if (!self::isRepository($absolutePath)) {
        continue;
      }
      $paths[] = $fileName;
    }
    closedir($dh);
    return SvnBase::NO_ERROR;
  }

  protected function encodeString($str, $dest = "UTF-8") {
    if (function_exists("mb_detect_encoding") && function_exists("mb_convert_encoding")) {
      $str = mb_convert_encoding($str, $dest, mb_detect_encoding($str));
    }
    return $str;
  }

  /**
   * Prepares the given URI/path for command line usage.
   * If $uri is a basic local path, this function converts it to an correct URI.
   *
   * - Replaces local directory separators (e.g. \) with normal slash (/).
   * - Encodes the $uri with UTF-8 charset. TODO Is it really required.
   * - Windows only: Prepends one slash for local drives and two slashes for network drive mappings.
   * - Prepends "file://", if no other protocol has been defined.
   *
   * @param string $uri
   * @return string
   */
  public function prepareRepositoryURI($uri) {
    // Replace \ against /
    $uri = str_replace(DIRECTORY_SEPARATOR, "/", $uri);

    // Encode to UTF-8.
    $uri = $this->encodeString($uri);

    // Use per cent encoding for url path.
    // Skip encoding of 'svn+ssh://' part.
    $parts = explode("/", $uri);
    $partsCount = count($parts);
    for ($i = 0; $i < $partsCount; $i++) {
      if ($i != 0 || $parts[$i] != 'svn+ssh:') {
        $parts[$i] = rawurlencode($parts[$i]);
      }
    }
    $uri = implode("/", $parts);
    $uri = str_replace("%3A", ":", $uri); // Subversion bug?

    // Quick fix for Windows share names.
    if ($this->_isWindowsServer) {
      // If the $uri now starts with "//", it points to a network share.
      // We must replace the first two "//" with "\\".
      if (substr($uri, 0, 2) == "//") {
        $uri = '\\' . substr($uri, 2);
      }
      if (substr($uri, 0, 10) == "file://///") {
        $uri = "file:///\\\\" . substr($uri, 10);
      }
    }

    // Automatic prepend the "file://" prefix (if nothing else is given).
    if (preg_match('/^[a-z0-9+]+:\/\//i', $uri) == 0) {
      if (strpos($uri, "/") === 0)
        $uri = "file://" . $uri;
      else
        $uri = "file:///" . $uri;
    }
    return $uri;
  }

  /**
   * Prepares a local repository path for command line usage.
   *
   * - Replaces local directory separators (e.g. \) with normal slash (/).
   * - Encodes the $uri with UTF-8 charset. TODO Is it really required.
   * - Windows only: Prepends one slash for local drives and two slashes for network drive mappings.
   */
  public function prepareRepositoryPath($path) {
    $path = str_replace(DIRECTORY_SEPARATOR, "/", $path);
    $path = $this->encodeString($path);

    // Quick fix for Windows share names.
    if ($this->_isWindowsServer) {
      // If the $uri now starts with "//", it points to a network share.
      // We must replace the first two "//" with "\\".
      if (substr($path, 0, 2) == "//") {
        $path = '\\\\' . substr($path, 2);
      }
    }
    return $path;
  }

  /**
   * Creates the commandline command which can be used for execution.
   * This function also escapes the shell arguments given by <code>$args</code>.
   *
   * Example of a command:
   * "<executable>" <command> <args> "<repository path>"
   * "/usr/bin/svn" info --xml --non-interactive --trust-server-cert "/opt/svn/repo"
   *
   * @param string $executable Absolute path to the binary.
   * @param string $command The binaries command.
   * @param string $pathOrUri The absolute local path or local/remote URL.
   * @param bool $asXml Indicates whether the response of the command should be in XML format.
   * @return string
   */
  public function prepareCommand($executable, $command, $pathOrUri, $asXml = false) {
    $cmd = '"' . $executable . '"';
    $cmd.= ' ' . $command;

    // Default arguments.
    $cmd.= ' --non-interactive';
    $cmd.= ' --trust-server-cert';
    $cmd.= ' --no-auth-cache';

    // Optional arguments.
    if ($asXml) {
      $cmd.= ' --xml';
    }
    if (!empty($this->_configDirectory)) {
      $cmd.= ' --config-dir ' . escapeshellarg($this->_configDirectory);
    }
    if (!empty($this->_username)) {
      $cmd.= ' --username ' . escapeshellarg($this->_username);
    }
    if (!empty($this->_password)) {
      $cmd.= ' --password ' . escapeshellarg($this->_password);
    }

    $cmd.= ' "' . $pathOrUri . '"'; // TODO Use escapeshellarg()??
    return $cmd;
  }

  /**
   * @param string $command Command to be executed.
   * @param string $stdout Will contain the process output.
   * @param string $stderr Will contain the process error output.
   * @param int $exitCode Will contain the process return code.
   * @return int
   */
  public function executeCommand($command, &$stdout, &$stderr, &$exitCode) {
    $descriptorspec = array(
        0 => array("pipe", "r"), // STDIN
        1 => array("pipe", "w"), // STDOUT
        2 => array("pipe", "w")  // STDERR
    );
    $process = proc_open('"' . $command . '"', $descriptorspec, $pipes);
    if (!is_resource($process)) {
      return SvnBase::ERROR_UNKNOWN;
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = (int) proc_close($process);
    if ($exitCode !== 0) {
      return SvnBase::ERROR_UNKNOWN;
    }
    return SvnBase::NO_ERROR;
  }
}
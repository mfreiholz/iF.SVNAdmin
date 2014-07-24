<?php
class IF_SVNList {
  public $path = "";
  public $entries = array ();
  public $curEntry = null;

}
class IF_SVNListEntry {
  public $rev = 1;
  public $author = "";
  public $date = "";
  public $size = 0;
  public $name = "";
  public $isdir = false;

}

/**
 * Provides functionality of the "svn.exe" executable by using the
 * executable and parsing the output.
 *
 * @author Manuel Freiholz, insaneFactory
 */
class SvnClient extends SvnBase {
  protected $_svnExe = null;
  protected $_curList = null;
  protected $_curTag = "";

  /**
   * @param string $svn_exe
   *          Absolute path to the "svn.exe" binary.
   */
  public function __construct($svn_exe) {
    parent::__construct();
    $this->_trust_server_cert = true;
    $this->_non_interactive = true;
    $this->_svnExe = $svn_exe;
  }

  /**
   * Creates a new directory in repository.
   *
   * @param array $path
   *          Absolute path to the new directory (multiple)
   * @param bool $parents
   *          Indiciates whether parents should be created, too.
   * @param string $commitMessage
   * @return bool
   */
  public function svn_mkdir(array $paths, $parents = true, $commitMessage = "Created folder.") {
    if (empty($paths)) {
      return false;
    }

    $args = array ();
    if (!empty($this->_config_directory)) {
      $args["--config-dir"] = escapeshellarg($this->_config_directory);
    }

    if ($parents) {
      $args["--parents"] = "";
    }

    if (!empty($commitMessage)) {
      $args["--message"] = escapeshellarg($commitMessage);
    }

    if (true) {
      $args["--quiet"] = "";
    }

    for ($i = 0; $i < count($paths); ++$i) {
      $paths[$i] = $this->encode_url_path($paths[$i]);
    }

    $command = self::create_svn_command($this->_svnExe, "mkdir", implode(' ', $paths), $args, false);

    $output = null;
    $return_var = 0;
    exec($command, $output, $return_var);
    return $return_var === 0;
  }

  /**
   * Gets a list of directory entries in the specified repository path.
   *
   * @param string $path
   *          Absolute path to the directory which should be listed.
   * @param IF_SVNList $outList
   *          (optional)
   * @return IF_SVNList
   */
  public function svn_list($path, &$outList = null) {
    if (empty($path)) {
      return null;
    }

    $args = array ();
    if (!empty($this->config_directory)) {
      $args["--config-dir"] = rawurlencode($this->config_directory);
    }

    $command = self::create_svn_command($this->_svnExe, "ls", self::encode_url_path($path), $args, true);

    $proc_descr = array (
        0 => array (
            "pipe",
            "r"
        ), // STDIN
        1 => array (
            "pipe",
            "w"
        ), // STDOUT
        2 => array (
            "pipe",
            "w"
        ) // STDERR
        );

    $resource = proc_open($command, $proc_descr, $pipes);
    if (!is_resource($resource)) {
      return null;
    }

    // Create XML-Parser.
    $xml_parser = xml_parser_create("UTF-8");
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
    xml_set_element_handler($xml_parser, array (
        $this,
        "xml_svn_list_start_element"
    ), array (
        $this,
        "xml_svn_list_end_element"
    ));
    xml_set_character_data_handler($xml_parser, array (
        $this,
        "xml_svn_list_character_data"
    ));

    // Read the XML stream now.
    $data_handle = $pipes[1];
    while (!feof($data_handle)) {
      $line = fgets($data_handle);
      if (!xml_parse($xml_parser, $line, feof($data_handle))) {
        return null;
        //throw new IF_SVNOutputParseException("XML parse error: (code=" . xml_get_error_code($xml_parser) . ") " . xml_error_string(xml_get_error_code($xml_parser)));
      }
    }

    $error_handle = $pipes[2];
    $error_message = "";
    while (!feof($error_handle)) {
      $error_message .= fgets($error_handle);
    }

    if (!empty($error_message)) {
      $this->error_string .= " (" . $this->_svnExe . " error=" . $error_message . ")";
      print("<pre>");
      print($this->error_string);
      print("\n\n");
      print($command);
      print("</pre>");
    }

    // Free resources.
    xml_parser_free($xml_parser);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($resource);

    if ($outList != null)
      $outList = $this->_curList;

    return $this->_curList;
  }

  // ///
  //
  // XML Handler Functions
  //
  // ///
  function xml_svn_list_start_element($xml_parser, $tagname, $attrs) {
    // echo "xml_svn_list_start_element($xml_parser, $tagname, $attrs)\n";
    switch ($tagname) {
      case "LIST":
        $this->_curList = new IF_SVNList();
        if (count($attrs))
          foreach ($attrs as $aName => $aVal)
            switch ($aName) {
              case "PATH":
                $this->_curList->path = self::encode_string($aVal);
                break;
            }
        break;

      case "ENTRY":
        $this->_curList->curEntry = new IF_SVNListEntry();
        if (count($attrs)) {
          foreach ($attrs as $aName => $aVal) {
            switch ($aName) {
              case "KIND":
                if ($aVal == "dir")
                  $this->_curList->curEntry->isdir = true;
                else
                  $this->_curList->curEntry->isdir = false;
                break;
            }
          }
        }
        break;

      case "COMMIT":
        if (count($attrs)) {
          foreach ($attrs as $aName => $aVal) {
            switch ($aName) {
              case "REVISION":
                $this->_curList->curEntry->rev = $aVal;
                break;
            }
          }
        }
        break;
    }
    $this->_curTag = $tagname;
  }

  function xml_svn_list_character_data($xml_parser, $tagdata) {
    switch ($this->_curTag) {
      case "NAME":
        if ($tagdata === false || $tagdata === "")
          return;
        $this->_curList->curEntry->name .= self::encode_string(trim($tagdata));
        break;

      case "AUTHOR":
        if ($tagdata === false || $tagdata === "")
          return;
        $this->_curList->curEntry->author .= trim($tagdata);
        break;

      case "DATE":
        if ($tagdata === false || $tagdata === "")
          return;
        $this->_curList->curEntry->date .= trim($tagdata);
        break;
    }
  }

  function xml_svn_list_end_element($xml_parser, $tagname) {
    switch ($tagname) {
      // Add the created entry to the list as child.
      case "ENTRY":
        $this->_curList->entries[] = $this->_curList->curEntry;
        break;

      case "LISTS":
        unset($this->_curList->curEntry);
        break;
    }
  }

}
//     if ($return_var != 0) {
//       throw new IF_SVNCommandExecutionException('Command=' . $command . '; Return=' . $return_var . '; Output=' . $output . ';');
//     }
?>
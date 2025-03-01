<?php
class IF_SVNList
{
	public $path = "";
	public $entries = array();
	public $curEntry = null;
}

class IF_SVNListEntry
{
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
class IF_SVNClientC extends IF_SVNBaseC
{
	protected $svnExe = null;
	protected $curList = null;
	protected $curTag = "";

	/**
	 * Constructor.
	 *
	 * @param string $svn_exe Absolute path to the "svn.exe" binary.
	 * @throws IF_SVNException
	 */
	public function __construct($svn_exe)
	{
		parent::__construct();
		$this->trust_server_cert = true;
		$this->non_interactive = true;
		$this->svnExe = $svn_exe;

		// if (!file_exists($svn_exe))
		// 	throw new IF_SVNException('Path to "svn.exe" does not exist: '.$this->svnExe);
		// if (!is_executable($svn_exe))
		// 	throw new IF_SVNException('Permission denied! Can not execute "svn" executable: '.$this->svnExe);
	}

	/**
	 * Creates a new directory in repository.
	 *
	 * @param array $path Absolute path to the new directory (multiple)
	 * @param bool $parents Indiciates whether parents should be created, too.
	 * @param string $commitMessage
	 * @throws IF_SVNException
	 * @throws IF_SVNCommandExecutionException
	 */
	public function svn_mkdir(array $paths, $parents=true, $commitMessage="Created folder.")
	{
		if (empty($paths))
		{
			throw new IF_SVNException('Empty path for svn_mkdir() command.');
		}

		$args = array();
		if (!empty($this->config_directory))
		{
			 $args["--config-dir"] = escapeshellarg($this->config_directory);
		}

		if ($parents)
		{
			$args["--parents"] = "";
		}

		if (!empty($commitMessage))
		{
			$args["--message"] = escapeshellarg($commitMessage);
		}

		if (true)
		{
			$args["--quiet"] = "";
		}
		
		for ($i = 0; $i < count($paths); ++$i) {
			$paths[$i] = $this->encode_url_path($paths[$i]);
		}

		$command = self::create_svn_command($this->svnExe, "mkdir", implode(' ', $paths), $args, false);

		$output = null;
		$return_var = 0;
		exec($command, $output, $return_var);

		if ($return_var != 0)
		{
			throw new IF_SVNCommandExecutionException('Command='.$command.'; Return='.$return_var.'; Output='.$output.';');
		}
	}


	/**
	 * Gets a list of directory entries in the specified repository path.
	 *
	 * @param string $path Absolute path to the directory which should be listed.
	 * @param IF_SVNList $outList (optional)
	 * @return IF_SVNList
	 *
	 * @throws IF_SVNException
	 * @throws IF_SVNCommandExecutionException
	 * @throws IF_SVNOutputParseException
	 */
	public function svn_list($path, &$outList = null)
	{
		if (empty($path))
		{
			throw new IF_SVNException('Empty path-parameter for svn_list() command.');
		}

		$args = array();
		if (!empty($this->config_directory))
		{
			$args["--config-dir"] = rawurlencode($this->config_directory);
		}

		$command = self::create_svn_command($this->svnExe, "ls", self::encode_url_path($path), $args, true);

		$proc_descr = array(
				0 => array("pipe", "r"), // STDIN
				1 => array("pipe", "w"), // STDOUT
				2 => array("pipe", "w")  // STDERR
			);

		$resource = proc_open($command, $proc_descr, $pipes);
		if (!is_resource($resource))
		{
			throw new IF_SVNCommandExecutionException('Invalid resource. Command='.$command.';');
		}

		// Create XML-Parser.
		$xml_parser = xml_parser_create("UTF-8");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($xml_parser, array($this, "xml_svn_list_start_element"), array($this, "xml_svn_list_end_element"));
		xml_set_character_data_handler($xml_parser, array($this, "xml_svn_list_character_data"));

		// Read the XML stream now.
		$data_handle = $pipes[1];
		while (!feof($data_handle))
		{
			$line = fgets($data_handle);
			if (!xml_parse($xml_parser, $line, feof($data_handle)))
			{
				// Error.
				throw new IF_SVNOutputParseException("XML parse error: (code=".xml_get_error_code($xml_parser).") ".xml_error_string(xml_get_error_code($xml_parser)));
			}
		}

		$error_handle = $pipes[2];
		$error_message = "";
		while (!feof($error_handle))
		{
			$error_message.= fgets($error_handle);
		}

		if (!empty($error_message))
		{
			$this->error_string.= " (".$this->svnExe." error=".$error_message.")";
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
			$outList = $this->curList;

		return $this->curList;
	}

  /////
  //
  // XML Handler Functions
  //
  /////

  function xml_svn_list_start_element($xml_parser, $tagname, $attrs)
  {
  	//echo "xml_svn_list_start_element($xml_parser, $tagname, $attrs)\n";
    switch($tagname)
    {
      case "LIST":
      	$this->curList = new IF_SVNList();
        if (count($attrs))
          foreach ($attrs as $aName => $aVal)
            switch($aName)
            {
              case "PATH":
                $this->curList->path = self::encode_string($aVal);
                break;
            }
        break;

      case "ENTRY":
        $this->curList->curEntry = new IF_SVNListEntry;
        if (count($attrs))
        {
          foreach ($attrs as $aName => $aVal)
          {
            switch($aName)
            {
              case "KIND":
                if ($aVal == "dir")
                  $this->curList->curEntry->isdir = true;
                else
                  $this->curList->curEntry->isdir = false;
                break;
            }
          }
        }
        break;

      case "COMMIT":
      	if (count($attrs))
      	{
      		foreach ($attrs as $aName => $aVal)
      		{
      			switch ($aName)
      			{
      				case "REVISION":
      					$this->curList->curEntry->rev = $aVal;
      					break;
      			}
      		}
      	}
      	break;
    }
    $this->curTag = $tagname;
  }

  function xml_svn_list_character_data($xml_parser, $tagdata)
  {
  	switch ($this->curTag)
  	{
  		case "NAME";
  		  if ($tagdata === false || $tagdata === "")
  		    return;
  		  $this->curList->curEntry->name.= self::encode_string(trim($tagdata));
  		  break;

  		case "AUTHOR":
  			if ($tagdata === false || $tagdata === "")
          return;
  			$this->curList->curEntry->author.= trim($tagdata);
  			break;

  		case "DATE":
  			if ($tagdata === false || $tagdata === "")
          return;
  			$this->curList->curEntry->date.= trim($tagdata);
  			break;
  	}
  }

  function xml_svn_list_end_element($xml_parser, $tagname)
  {
    switch ($tagname)
    {
      // Add the created entry to the list as child.
      case "ENTRY":
        $this->curList->entries[] = $this->curList->curEntry;
        break;

      case "LISTS":
      	unset($this->curList->curEntry);
      	break;
    }
  }
}
?>
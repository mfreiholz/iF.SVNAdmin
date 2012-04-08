<?php
/**
 * Basic excption which is used by SVN classes.
 *
 * @author Manuel Freiholz, insaneFactory.com
 */
class IF_SVNException extends Exception {}
class IF_SVNCommandExecutionException extends IF_SVNException {}
class IF_SVNOutputParseException extends IF_SVNException {}


/**
 * Base class for subversion commandline utils.
 *
 * The class supports some global defines as configuration:
 *
 * - IF_SVNBaseC_ConfigDir: Path to the subversion configuration directory.
 *   Has been introduced for the bug, if the server can't acces the configuration
 *   of subversion. (Fixes error: "svnadmin: Can't open file '/root/.subversion/servers': Permission denied")
 *
 * @author Manuel Freiholz (Gainwar)
 * @copyright insaneFactory.com
 */
class IF_SVNBaseC
{
	/* Error return codes. */
	static $ALL_OK = 0;
	static $ERR_UNKNOWN = 1;
	static $ERR_PERMISSION = 2;
	static $ERR_PATH = 4;
	static $ERR_EXECUTION = 8;
	static $ERR_PARAMETER_VALIDATION = 16;

	/*******************************************************************
	 * Basic command line parameters for all commands.
	 ******************************************************************/

	/**
	 * Indicates whether the remote server certificate should be trusted.
	 * @var bool
	 */
	protected $trust_server_cert = false;

	/**
	 * Indicates whether interactive prompts should be disabled/enabled.
	 * @var bool
	 */
	protected $non_interactive = false;

	/**
	 * The username which should be used for each command.
	 * @var string
	 */
  	protected $username = "";

  	/**
  	 * The password which should be used for each command.
  	 * @var string
  	 */
  	protected $password = "";

  	/**
  	 * The optional ".subversion" directory of svn-client which should be used.
  	 * @var string
  	 */
	protected $config_directory = "";

	/*******************************************************************
	 * Additional information.
	 ******************************************************************/

	/**
	 * Indicates whether the current server system is based on MS Windows.
	 * @var bool
	 */
	protected $is_windows_server = false;

	/**
	 * @var string
	 * @deprecated
	 */
	protected $error_string = "";

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		// Find out whether the system is based on MS Windows.
		$soft = $_SERVER["SERVER_SOFTWARE"];
		$soft = strtoupper($soft);

		if (strpos($soft, "WIN") !== FALSE)
		{
			$this->is_windows_server = true;
		}

		// Handle define: IF_SVNBaseC_ConfigDir
		if (defined("IF_SVNBaseC_ConfigDir"))
		{
			$this->config_directory = IF_SVNBaseC_ConfigDir;
		}
	}

	/**
	 * Checks whether the folder at the given location is a repository.
	 *
	 * @param string $path Absolute path to a repository directory.
	 * @return bool
	 */
	public function isRepository($path)
	{
    	// TODO: Built in complete check.
		return is_dir($path);
	}

	/**
	 * Gets a list with all available repositories in the given base path.
	 *
	 * @param $basePath The SVNParentPath of the repositories.
	 * @return array<string> List of absolute paths to repositories.
	 * @throws IF_SVNException
	 */
	public function listRepositories($basePath)
	{
		if (!file_exists($basePath))
		{
			throw new IF_SVNException('The repository parent path (SVNParentPath) does not exists: '.$basePath);
		}

		$ret = array();

		$hd = opendir( $basePath );
		while (($file = readdir($hd)) !== false)
		{
			if ($file == "." || $file == "..")
			{
				continue;
			}

			$absolute_path = $basePath."/".$file;
			if (self::isRepository($absolute_path))
			{
				$ret[] = $file;
			}
		}
		closedir($hd);

		return $ret;
	}

	/**
	 * Encodes the given string <code>$s</code> to <code>$dest_enc</code> (default UTF-8).
	 *
	 * @param string $s
	 * @param string $dest_enc Example: UTF-8, ISO-88....
	 * @return string
	 */
	function encode_string($str, $dest_enc = "UTF-8")
	{
		if (function_exists("mb_detect_encoding") && function_exists("mb_convert_encoding"))
		{
			$str = mb_convert_encoding($str, $dest_enc, mb_detect_encoding($str));
		}
		return $str;
	}

  /**
   * Prepares a path (URI) for command line usage. Does the following steps.
   *
   * <ul>
   *   <li>Replace backslash with slash (\ => /)</li>
   *   <li>Encode the input string <code>$uri</code> with UTF-8</li>
   *   <li><i>(Windows only)</i> Add one leading slash and two leading backslashes for network drive mappings.</li>
   *   <li>Prepend a "file://", if no other protocol is given.</li>
   * </ul>
   *
   * This function does <b>NOT</b> add " at the start+end of the given <code>$uri</code>,
   * because spaces are per cent encoded as %20.
   *
   * @param string $uri
   * @return string
   */
  function encode_url_path($uri)
  {
  	// Replace \ against /
  	$uri = str_replace(DIRECTORY_SEPARATOR, "/", $uri);

  	// Encode to UTF-8.
  	$uri = self::encode_string($uri, "UTF-8");

  	// Use per cent encoding for url path.
  	// Skip encoding of 'svn+ssh://' part.
  	$parts = explode("/", $uri);
  	$partsCount = count($parts);
  	for ($i=0; $i<$partsCount; $i++)
  	{
  		if ($i != 0 || $parts[$i] != 'svn+ssh:')
  		{
  			$parts[$i] = rawurlencode($parts[$i]);
  		}
  	}
  	$uri = implode("/", $parts);
  	$uri = str_replace("%3A", ":", $uri); // Subversion bug?

  	// Quick fix for Windows share names.
  	if ($this->is_windows_server)
  	{
  		// If the $uri now starts with "//", it points to a network share.
  		// We must replace the first two "//" with "\\".
  		if (substr($uri, 0, 2) == "//")
  		{
  			$uri = '\\'.substr($uri, 2);
  		}

  		if (substr($uri, 0, 10) == "file://///")
  		{
  			$uri = "file:///\\\\".substr($uri, 10);
  		}
  	}

		// Automatic prepend the "file://" prefix (if nothing else is given).
  	if (preg_match('/^[a-z0-9+]+:\/\//i', $uri) == 0)
  	{
  		if (strpos($uri, "/") === 0)
  	    $uri = "file://".$uri;
  		else
  		  $uri = "file:///".$uri;
  	}

  	return $uri;
  }

  /**
   * Prepares a path (URI) for command line usage. Does the following steps.
   *
   * <ul>
   *   <li>Replace backslash with slash (\ => /)</li>
   *   <li>Encode the input string <code>$uri</code> with UTF-8</li>
   *   <li><i>(Windows only)</i> Add one leading slash and two leading backslashes for network drive mappings.</li>
   *   <li>Add leading and trailing slashes.</li>
   * </ul>
   *
   * @param unknown_type $local_path
   */
  function encode_local_path($local_path)
  {
  	$local_path = str_replace(DIRECTORY_SEPARATOR, "/", $local_path);
  	$local_path = self::encode_string($local_path);

    // Quick fix for Windows share names.
  	if ($this->is_windows_server)
  	{
  		// If the $uri now starts with "//", it points to a network share.
  		// We must replace the first two "//" with "\\".
  		if (substr($local_path, 0, 2) == "//")
  		{
  			$local_path = '\\\\'.substr($local_path, 2);
  		}
  	}

  	// Add leading and trailing quotes.
  	$local_path = '"'.$local_path.'"';

  	return $local_path;
  }

  /**
   * Creates the commandline command which can be used for execution.
   * This function also escapes the shell arguments given by <code>$args</code>.
   *
   * <p>
   *   <b>Note:</b> The <code>$repo_path</code> parameter should be UTF-8 encoded.
   *   (see <code>encode_path(...)</code>)
   * </p>
   *
   * @param string $exe The absolute file path to the *.exe file. (svn.exe or svnadmin.exe)
   * @param string $command
   * @param string $repo_path
   * @param array $args
   * @param bool $asXml
   * @return string
   */
  function create_svn_command($exe, $command, $repo_path, $args=null, $asXml=true)
  {
  	$cmd = "\"".$exe."\" ".$command;

	if ($asXml === true)
  	  $cmd.= " --xml";

  	if ($this->non_interactive)
  	  $cmd.= " --non-interactive";

  	if ($this->trust_server_cert)
  	  $cmd.= " --trust-server-cert";

  	if (!empty($this->username))
  	  $cmd.= " --username ".$this->username;

  	if (!empty($this->password))
  	  $cmd.= " --password ".$this->password;

  	// Handle custom args.
  	if (!empty($args))
  	{
  		foreach ($args as $key => &$val)
  		{
  			$cmd.= " ".$key;
  			if (!empty($val))
  					$cmd.= " ".$val;        // old line: $cmd.= " ".escapeshellarg($val);
  		}
  	}

    $cmd.= " ".$repo_path;
  	return ''.$cmd.'';
  }
}
?>
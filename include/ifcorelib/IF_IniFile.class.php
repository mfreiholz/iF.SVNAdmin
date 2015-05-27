<?php
/**
 * No longer  use this class!
 * Use IF_Config instead.
 *
 * @author Manuel Freiholz
 * @deprecated Use IF_Config instead.
 */
class IF_IniFile
{
  /**
   * The configurationd data which has benn loaded.
   * @var array
   */
  private $settings;

  /**
   * Path to the file from which the configuration has been loaded.
   * @var string
   */
  private $filepath;

  /**
   * Constructor.
   * Initializes the member variables.
   */
  public function __construct()
  {
    $this->settings = NULL;
    $this->filepath = NULL;
  }

  /**
   * Loads a configuration in INI format from file.
   * @param string $file
   * @throws Exception
   */
  public function loadFromFile($file)
  {
    $this->settings = if_parse_ini_file($file);
    if ($this->settings === false)
      throw new Exception("Can't load settings from file: $file");

    $this->filepath = $file;
    return true;
  }

  /**
   * Saves the current configuration to file.
   * @param string $file If NULL, the same file from which the configuration
   * has been loaded will be used.
   * @return bool
   * @throws Exception
   */
  public function saveToFile($file=null)
  {
    if ($file == null)
      $file = $this->filepath;
    if ($file == null)
      throw new Exception("Can't save configuration. No file given.");

    if (if_write_ini_file($file, $this->settings) === false)
      throw new Exception("Can't save configuration. Check the permission.");

    return true;
  }

  /**
   * Searches for the specified value.
   * @param string $block
   * @param string $key
   * @return string or NULL
   */
  public function getValue($block, $key, $default=null)
  {
    if (isset($this->settings[$block][$key]))
      return $this->settings[$block][$key];
    else
      return $default;
  }

  /**
   * Get the configuration value as boolean.
   * @param string $block
   * @param string $key
   */
  public function getValueAsBoolean($block, $key, $default=false)
  {
	$v = $this->getValue($block, $key, $default);

	if ($v === 1
		|| $v === "1"
		|| strcasecmp($v, "true") === 0
		|| strcasecmp($v, "yes") === 0
		|| strcasecmp($v, "on") === 0
		|| $v === true) {
		return true;
	}
	
	if ($v === 0
		|| $v === "0"
		|| strcasecmp($v, "false") === 0
		|| strcasecmp($v, "no") === 0
		|| strcasecmp($v, "off") === 0
		|| $v === false) {
		return false;
	}

	return $default;
  }

  /**
   * Searches all existing blocks in the configuration
   * and returns them in an array.
   * @return array All blocks of the configuration.
   */
  public function getBlocks()
  {
    $blocks = array();
    foreach ($this->settings as $b => &$noval)
      $blocks[] = $b;
    return $blocks;
  }

  /**
   * Searches for all existing keys in the block and returns a list
   * of the keys.
   * @param string $block
   * @return array
   * @throws Exception If the block doesn't exist.
   */
  public function getKeysOfBlock($block)
  {
    if (isset($this->settings[$block]))
    {
      $keysArray = &$this->settings[$block];
      if (is_array($keysArray))
      {
        // Return all keys of the block.
        return array_keys($keysArray);
      }
      else
      {
        // Empty block.
        return array();
      }
    }
    else
    {
      // The block doesn't exist.
      throw new Exception("Undefined block: $block");
    }
  }

  /**
   * Sets a configuration value.
   * @param string $block
   * @param string $key
   * @param string $value
   */
  public function setValue($block, $key, $value)
  {
    if (!isset($this->settings[$block]))
      $this->settings[$block] = array();
    $this->settings[$block][$key] = $value;
  }

  /**
   * Removes a value from configuration.
   * @param string $block
   * @param string $key
   * @param string $value
   * @return bool Always returns TRUE.
   */
  public function removeValue($block, $key, $value)
  {
    if (!isset($this->settings[$block]))
      return true;

    if (!isset($this->settings[$block][$key]))
      return true;

    unset($this->settings[$block][$key]);
    return true;
  }
}
?>
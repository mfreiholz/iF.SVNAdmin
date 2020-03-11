<?php

class IF_SyntaxErrorException extends Exception
{
}

/**
 * This class provides parsing and writing functionality for INI files.
 * Same as the IF_IniFile class but an improved version.
 *
 * This class does not remove comments or other customized stuff in
 * configuration (*.ini) files on writing changes to disc.
 *
 * @todo Write back empty sections.
 *
 * @author Manuel Freiholz, insaneFactory.com
 *
 */
class IF_Config
{
  /**
   * Path to config file.
   * @var string
   *
   */
  private $configFilePath = null;

  /**
   * Holds the configuration keys from file.
   * e.g.: $items["section_name"]["key"] = "value";
   * @var array()
   *
   */
  private $items = array();

  /**
   * Constructor.
   *
   * @param string $configFilePath Path to config file.
   */
  public function __construct($configFilePath)
  {
    $this->configFilePath = $configFilePath;
    $this->parse();
  }

  /**
   * Parses the file in INI format.
   *
   * @throws IF_SyntaxErrorException
   *
   */
  private function parse()
  {
    if (!file_exists($this->configFilePath) || !is_file($this->configFilePath)) {
      throw new Exception('Config file does not exist. ' . $this->configFilePath);
    }

    $fh = fopen($this->configFilePath, 'r');
    // lock the file . LOCK_SH means share to lock
    if (!flock($fh, LOCK_SH)) {
      throw new Exception('Can not lock (shared) file. ' . $this->configFilePath);
    }

    $last_section_name = NULL;
    // not the file ned
    while (!feof($fh)) {
      $line = fgets($fh);
      $line = trim($line); // remove whitespace

      // skip empty lines
      if (empty($line)) {
        continue;
      }

      // skip comments
      if (strpos($line, '#') === 0
        || strpos($line, ';') === 0) {
        continue;
      }

      // deal with section header
      if (substr($line, 0, 1) == '[') {
        // [groups] or [testrepo:/]，the section_name value will be "groups" or "testrepo:/"
        //				$section_name = substr($line, 1, strlen($line)-2);
        //				$this->items[$section_name] = array();
        //				$last_section_name = $section_name;

        // add description to the secion_name line. implement here

        // after implement, section node like this:
        // [testrepo:/] # desc: The test repository
        // then:
        //       section_name = "testrepo:/"
        //       section_description = "The test repository"
        $splits = explode('#', $line, 2);
        $section_string = trim($splits[0]);
        $section_comment = trim($splits[1]);
        $section_name = substr($section_string, 1, strlen($section_string) - 2);
        $section_description = trim(substr($section_comment, 5, strlen($section_comment) - 2));
        $this->items[$section_name] = array();
        // do not add description to [groups] section
        if ($section_name !== 'groups') {
          $this->items[$section_name]['#section_desc'] = $section_description;
        }
        $last_section_name = $section_name;
        if_log_debug('section_name:' . $section_name . ',   section_comment:' . $section_comment);
        continue;
      }
      // "key=value" pairs of last section header
      // get key value pairs
      else {
        // split the line withe '='
        $splits = explode('=', $line, 2);
        $key = trim($splits[0]); // 'user' or '@group'
        $val = NULL; //get user or group permission. DO NOT Add comments after the permission line

        if (count($splits) > 1) {
          $val = trim($splits[1]);
        }
        $this->items[$last_section_name][$key] = $val;

      }
    }

    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
  }

  /**
   * Saves the internal hold configuration to disk.
   * How? First, read the configuration file and save them
   * into a buffer, but replace the changed configuration values.
   * Then write it back to file.
   *
   * @param string Path to the file where the config should be saved.
   *
   * @return bool
   */
  public function save($path = null)
  {
    if (!is_array($this->items)) {
      return false;
    }

    if ($path == null) {
      $path = $this->configFilePath;
    }

    if (!file_exists($path)) {
      // try to create the file
      if (!touch($path)) {
        throw new Exception('File does not exist and can not create it. ' . $path);
      }
    }

    $fh = fopen($path, 'w');
    flock($fh, LOCK_EX);

    if_log_debug('Write $items object to config file');
    // iterate all sections
    foreach ($this->items as $section_name => $section_data) {

      // implement, write section_description information to the config file
      fwrite($fh, "\n[" . $section_name . "]");
      if (empty($section_data['#section_desc'])) {
        fwrite($fh, "\n");
      } else {
        fwrite($fh, " # desc: " . $section_data['#section_desc'] . "\n");
      }

      // after add the key '#section_desc', the '$section_data' always is Array.
      if (is_array($section_data)) {
        // iterate key/value pairs of section
        foreach ($section_data as $key => $val) {
          if ($key !== '#section_desc') {
            fwrite($fh, $key . '=' . $val . "\n");
          }
        }
      }
    }

    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
  }

  /**
   * Gets the path to the used config file.
   *
   * @return string
   */
  public function getConfigPath()
  {
    return $this->configFilePath;
  }

  /**
   * Gets a specified value from config file.
   *
   * @param string $section
   * @param string $key
   * @param string $defaultValue (default=null)
   *
   * @return string (value specified by $defaultValue)
   */
  public function getValue($section, $key, $defaultValue = null)
  {
    if (isset($this->items[$section]) && isset($this->items[$section][$key])) {
      return $this->items[$section][$key];
    }
    return $defaultValue;
  }

  /**
   * Gets all existing sections from config file.
   *
   * @return array<string>
   */
  public function getSections()
  {
    $ret = array();
    foreach ($this->items as $section => &$noval) {
      $ret[] = $section;
    }
    return $ret;
  }

  /**
   * Gets all existing keys from a specific section.
   *
   * @param string $section
   *
   * @return array<string>
   */
  public function getSectionKeys($section)
  {
    if (isset($this->items[$section])) {
      if (is_array($this->items[$section])) {
        return array_keys($this->items[$section]);
      } else {
        // Empty section.
        return array();
      }
    } else {
      // Unknown section.
      return array();
    }
  }

  /**
   * Sets a specific value to config.
   * set key-value in the config file.
   * @param string $section
   * @param string $key
   * @param string $value
   */
  public function setValue($section, $key, $value)
  {
    if (!isset($this->items[$section])) {
      $this->items[$section] = array();
    }

    if (!empty($key)) {
      $this->items[$section][$key] = $value;
    }
  }

  /**
   * Removes a specific value from config.
   *
   * @param string $section
   * @param string $key
   */
  public function removeValue($section, $key)
  {
    if (!isset($this->items[$section])) {
      return true;
    }

    if (empty($key)) {
      unset($this->items[$section]);
    } else {
      if (!isset($this->items[$section][$key])) {
        return true;
      }
      unset($this->items[$section][$key]);
    }
    return true;
  }

  /**
   * Gets to know whether a specific section exists.
   *
   * @param string $section
   *
   * @return bool
   */
  public function getSectionExists($section)
  {
    return (isset($this->items[$section]));
  }

  /**
   *
   * Enter description here ...
   * @param unknown_type $section
   * @param unknown_type $key
   */
  public function getValueExists($section, $key)
  {
    if (isset($this->items[$section])) {
      if (isset($this->items[$section][$key])) {
        return true;
      }
    }
    return false;
  }

  /*
   * Get Section Description,
   * @param unknow_type $section
   */
  public function getSectionDescription($section)
  {
    if (isset($this->items[$section])) {
      if (isset($this->items[$section]['#section_desc'])) {
        return $this->items[$section]['#section_desc'];
      }
    }
    return null;
  }
}

?>
<?php
/**
 */
class IniFileSection {
  public $comment = "";
  public $name = "";

}

/**
 */
class IniFileItem {
  public $comment = "";
  public $key = "";
  public $value = "";

}

/**
 */
class IniFile {
  /*
   * Error types
   */
  const NO_ERROR = 0;
  const FILE_ERROR = 1;
  const PARSE_ERROR = 2;

  /*
   * Attributes
   */
  private $_errorString = "";
  private $_filePath = null;
  private $_items = array ();
  private $_lastComment = "";

  public function loadFromFile($path) {
    $this->reset();
    $this->_filePath = $path;
    if (!file_exists($this->_filePath) || !is_file($this->_filePath)) {
      $this->_errorString = "File doesn't exists (path=" . $this->_filePath . ")";
      return IniFile::FILE_ERROR;
    }
    $fh = fopen($this->_filePath, "r");
    if (!flock($fh, LOCK_SH)) {
      $this->_errorString = "Can not lock file (mode=LOCK_SH; path=" . $this->_filePath . ")";
      return IniFile::FILE_ERROR;
    }
    $ret = $this->parseStream($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ret;
  }

  public function loadFromString($str) {
    $this->reset();
    $stream = fopen("php://memory", "r+");
    fwrite($stream, $str);
    rewind($stream);
    $ret = $this->parseStream($stream);
    fclose($stream);
    return $ret;
  }

  public function asString() {
    $stream = fopen("php://memory", "r+");
    $this->writeStream($stream);
    rewind($stream);
    $str = stream_get_contents($stream);
    fclose($stream);
    return $str;
  }

  private function reset() {
    $this->_errorString = "";
  }

  private function parseStream($stream) {
    $comment = "";
    while (!feof($stream)) {
      $line = fgets($stream);
      $line = trim($line);

      // Empty line.
      if (empty($line)) {
        $comment .= PHP_EOL;
        continue;
      }

      // Comment text.
      if (strpos($line, "#") === 0 || strpos($line, ";") === 0) {
        if (!empty(trim($comment))) {
          $comment .= PHP_EOL;
        }
        $comment .= $line;
        continue;
      }

      // Section.
      if (substr($line, 0, 1) === "[") {
        $section = new IniFileSection();
        $section->comment = $comment;
        $section->name = substr($line, 1, strlen($line) - 2);
        $this->_items[] = $section;
        $comment = "";
        continue;
      }

      // Key and value.
      $parts = explode("=", $line, 2);
      if (count($parts) >= 1) {
        $item = new IniFileItem();
        $item->comment = $comment;
        $item->key = trim($parts[0]);
        if (count($parts) >= 2) {
          $item->value = trim($parts[1]);
        }
        $this->_items[] = $item;
        $comment = "";
        continue;
      }
    }
    $this->_lastComment = $comment;
    return IniFile::NO_ERROR;
  }

  private function writeStream($stream) {
    foreach ($this->_items as &$obj) {
      if ($obj instanceof IniFileSection) {
        fwrite($stream, $obj->comment);
        if (!empty(trim($obj->comment))) {
          fwrite($stream, PHP_EOL);
        }
        fwrite($stream, "[" . $obj->name . "]");
        fwrite($stream, PHP_EOL);
      } else if ($obj instanceof IniFileItem) {
        fwrite($stream, $obj->comment);
        if (!empty(trim($obj->comment))) {
          fwrite($stream, PHP_EOL);
        }
        fwrite($stream, $obj->key . "=" . $obj->value);
        fwrite($stream, PHP_EOL);
      }
    }
    if (!empty($this->_lastComment)) {
      fwrite($stream, $this->_lastComment);
    }
  }

  /**
   * Saves the internal hold configuration to disk.
   * How? First, read the configuration file and save them
   * into a buffer, but replace the changed configuration values.
   * Then write it back to file.
   *
   * @param
   *          string Path to the file where the config should be saved.
   *
   * @return bool
   */
  public function save($path = null) {
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

    // iterate all sections
    foreach ($this->items as $section_name => $section_data) {
      fwrite($fh, "\n[" . $section_name . "]\n");

      if (is_array($section_data)) {
        // iterate key/value pairs of section
        foreach ($section_data as $key => $val) {
          fwrite($fh, $key . '=' . $val . "\n");
        }
      }
    }

    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
  }

  /**
   * Gets a specified value from config file.
   *
   * @param string $section
   * @param string $key
   * @param string $defaultValue
   *          (default=null)
   *
   * @return string (value specified by $defaultValue)
   */
  public function getValue($section, $key, $defaultValue = null) {
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
  public function getSections() {
    $ret = array ();
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
  public function getSectionKeys($section) {
    if (isset($this->items[$section])) {
      if (is_array($this->items[$section])) {
        return array_keys($this->items[$section]);
      } else {
        // Empty section.
        return array ();
      }
    } else {
      // Unknown section.
      return array ();
    }
  }

  /**
   * Sets a specific value to config.
   *
   * @param string $section
   * @param string $key
   * @param string $value
   */
  public function setValue($section, $key, $value) {
    if (!isset($this->items[$section])) {
      $this->items[$section] = array ();
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
  public function removeValue($section, $key) {
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
  public function getSectionExists($section) {
    return (isset($this->items[$section]));
  }

  /**
   *
   * Enter description here ...
   *
   * @param unknown_type $section
   * @param unknown_type $key
   */
  public function getValueExists($section, $key) {
    if (isset($this->items[$section])) {
      if (isset($this->items[$section][$key])) {
        return true;
      }
    }
    return false;
  }

}

// TEST
header("Content-type: text/plain");
$ini = new IniFile();
// $ini->loadFromFile("D:/Development/Source/iF.SVNAdmin/data/config.tpl.ini");
$ini->loadFromString(file_get_contents("D:/Development/Source/iF.SVNAdmin/data/config.tpl.ini"));
print($ini->asString());
// print_r($ini);
?>
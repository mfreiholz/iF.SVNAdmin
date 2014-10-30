<?php
/**
 */
class IniFileSection {
  public $comment = "";
  public $name = "";
  public $items = array ();

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
  private $_sections = array ();
  private $_lastComment = "";

  public function getSections() {
    $names = array ();
    foreach ($this->_sections as &$obj) {
      $names[] = $obj->name;
    }
    return $names;
  }

  public function getValue($block, $key, $defaultValue = null) {
    $val = $defaultValue;
    $found = false;
    foreach ($this->_sections as &$section) {
      if ($section->name === $block) {
        foreach ($section->items as &$item) {
          if ($item->key === $key) {
            $val = $defaultValue;
            $found = true;
            break;
          }
        }
      }
      if ($found) {
        break;
      }
    }
    return $val;
  }
  
  public function setValue($block, $key, $val) {
  }
  
  public function removeValue($block, $key) {
  }
  
  public function removeSection($block) {
  }

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
  
  public function writeToFile($path) {
    if (empty($path)) {
      $path = $this->_filePath;
    }
    if (!file_exists($path)) {
      if (!touch($path)) {
        $this->_errorString = "Can not create file. (path=" . $path . ")";
        return IniFile::FILE_ERROR;
      }
    }
    $fh = fopen($path, "r+");
    if (!flock($fh, LOCK_EX)) {
      fclose($fh);
      $this->_errorString = "Can not aquire lock on file (path=" . $path . ")";
      return IniFile::FILE_ERROR;
    }
    $ret = $this->writeStream($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return IniFile::NO_ERROR;
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
    $lastSection = null;
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
        $this->_sections[] = $section;
        $comment = "";
        $lastSection = $section;
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
        if ($lastSection !== null) {
          $lastSection->items[] = $item;
        }
        $comment = "";
        continue;
      }
    }
    $this->_lastComment = $comment;
    return IniFile::NO_ERROR;
  }

  private function writeStream($stream) {
    // Sections.
    foreach ($this->_sections as &$section) {      
      fwrite($stream, $section->comment);
      if (!empty(trim($section->comment))) {
        fwrite($stream, PHP_EOL);
      }
      fwrite($stream, "[" . $section->name . "]");
      fwrite($stream, PHP_EOL);
      
      // Items.
      foreach ($section->items as &$item) {
        fwrite($stream, $item->comment);
        if (!empty(trim($item->comment))) {
          fwrite($stream, PHP_EOL);
        }
        fwrite($stream, $item->key . "=" . $item->value);
        fwrite($stream, PHP_EOL);
      }
    }
    if (!empty($this->_lastComment)) {
      fwrite($stream, $this->_lastComment);
    }
  }
}
/**

header("Content-type: text/plain");
$ini = new IniFile();
$ini->loadFromFile("C:/Sources/iF.SVNAdmin/data/config.tpl.ini");
//$ini->loadFromString(file_get_contents("C:/Sources/iF.SVNAdmin/data/config.tpl.ini"));
//$ini->writeToFile("C:/Temp/test.ini");
//print($ini->asString());
//print_r($ini);

/**/
?>
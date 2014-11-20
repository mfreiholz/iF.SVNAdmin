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
  const NO_ERROR = 0;
  const FILE_ERROR = 1;
  const PARSE_ERROR = 2;
  const ALREADY_EXISTS = 3;
  const NOT_FOUND = 4;

  private $_errorString = "";
  private $_filePath = null;
  private $_sections = array ();
  private $_lastComment = "";
  
  /*
   * Methods to access the IniFileSection and IniFileItem objects
   * for advanced INI file processing.
   */
  
  /**
   * @return array<IniFileSection>
   */
  public function getSections() {
    return $this->_sections;
  }

  /**
   * @param $block string
   * @return IniFileSection
   */
  public function getSection($block) {
    foreach ($this->_sections as &$section) {
      if ($section->name === $block) {
        return $section;
      }
    }
    return null;
  }

  /**
   * @param $block
   * @param string $comment
   * @return int IniFile::NO_ERROR, IniFile::ALREADY_EXISTS
   */
  public function addSection($block, $comment = "") {
    $obj = $this->getSection($block);
    if (!empty($obj)) {
      return IniFile::ALREADY_EXISTS;
    }
    $obj = new IniFileSection();
    $obj->comment = $comment;
    $obj->name = $block;
    $this->_sections[] = $obj;
    return IniFile::NO_ERROR;
  }

  /**
   * @param $block
   * @return int IniFile::NO_ERROR, IniFile::NOT_FOUND
   */
  public function removeSection($block) {
    for ($i = 0; $i < count($this->_sections); ++$i) {
      if ($this->_sections[$i]->name === $block) {
        unset($this->_sections[$i]);
        $this->_sections = array_values($this->_sections);
        return IniFile::NO_ERROR;
      }
    }
    return IniFile::NOT_FOUND;
  }
  
  /*
   * Methods to directly access values.
   * Enough for most use cases.
   */

  public function getSectionNames() {
    $names = array ();
    foreach ($this->_sections as &$obj) {
      $names[] = $obj->name;
    }
    return $names;
  }
  
  public function getSectionKeys($block) {
    $keys = array ();
    foreach ($this->_sections as &$section) {
      if ($section->name === $block) {
        foreach ($section->items as &$item) {
          $keys[] = $item->key;
        }
        break;
      }
    }
    return $keys;
  }

  public function getValue($block, $key, $defaultValue = null) {
    $val = $defaultValue;
    $found = false;
    foreach ($this->_sections as &$section) {
      if ($section->name === $block) {
        foreach ($section->items as &$item) {
          if ($item->key === $key) {
            $val = $item->value;
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
    $sectionObj = null;
    $itemObj = null;
    foreach ($this->_sections as &$section) {
      if ($section->name === $block) {
        $sectionObj = $section;
        foreach ($section->items as &$item) {
          if ($item->key === $key) {
            $itemObj = $item;
            break;
          }
        }
        break;
      }
    }
    if (!$sectionObj) {
      $sectionObj = new IniFileSection();
      $sectionObj->name = $block;
      $this->_sections[] = $sectionObj;
    }
    if (!$itemObj) {
      $itemObj = new IniFileItem();
      $itemObj->key = $key;
      $sectionObj->items[] = $itemObj;
    }
    $itemObj->value = $val;
  }
  
  public function removeValue($block, $key) {
    foreach ($this->_sections as &$section) {
      if ($section->name === $block) {
        for ($i = 0; $i < count($section->items); ++$i) {
          if ($section->items[$i]->key === $key) {
            unset($section->items[$i]);
            $section->items = array_values($section->items);
            return true;
          }
        }
      }
    }
    return false;
  }
  
  /*
   * Methods to load and save content.
   */

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
    $fh = fopen($path, "w");
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
    $stream = fopen("php://memory", "w+");
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
      $com = trim($section->comment);
      fwrite($stream, $section->comment);
      if (!empty($com)) {
        fwrite($stream, PHP_EOL);
      }
      fwrite($stream, "[" . $section->name . "]");
      fwrite($stream, PHP_EOL);

      // Items.
      foreach ($section->items as &$item) {
        $com = trim($item->comment);
        fwrite($stream, $item->comment);
        if (!empty($com)) {
          fwrite($stream, PHP_EOL);
        }
        fwrite($stream, $item->key . "=" . $item->value);
        fwrite($stream, PHP_EOL);
      }
    }

    $com = trim($this->_lastComment);
    if (!empty($com)) {
      fwrite($stream, $this->_lastComment);
    }
  }
}
/**

header("Content-type: text/plain");

// Load file.
$ini = new IniFile();
$ini->loadFromFile("C:/Sources/iF.SVNAdmin/data/config.tpl.ini");
//$ini->loadFromString(file_get_contents("C:/Sources/iF.SVNAdmin/data/config.tpl.ini"));

// Retrieve basic information.
//print_r($ini->getSectionNames());
//print_r($ini->getSectionKeys("Ldap"));
//print_r($ini->getValue("Ldap", "BindDN"));

// Set and change values.
//$ini->setValue("testblock", "testkey", "testval");
//$ini->setValue("testblock", "testkey", "testval2");
//print_r($ini);

// Remove value.
//$ini->removeValue("Common", "FirstStart");
//print_r($ini);

// Remove section.
//$ini->removeSection("Translation");
//print_r($ini);

// Save to disk.
//$ini->writeToFile("C:/Temp/after.ini");

/**/
?>
<?php
/**
 * ifphplib
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */
class IF_Locale
{
  public $locale;
  public $name;
  public $author;

  public function getLocale()
  {
    return $this->locale;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getAuthor()
  {
    return $this->author;
  }
}

/**
 * Some important notes:
 * - The translation files should have UTF-8 encoding.
 *
 * How is the file structure?
 * <TR-Directory>/<Locale>/<module>.txt
 * <TR-Directory>/de_DE/mysection.txt
 */
class IF_Translator
{
  private $commentSign = "#";
  private $trDirectory = "translations/";
  private $currentLocale = "de_DE";
  private $fileExtension = "txt";
  private $indexFile = "index.cfg";
  private $translations = array();
  private static $instance = NULL;

  // Using cookies.
  //private $useCookies = true;
  //private $cookieName = "iftr_lang";

  /**
   * Creates a new instance.
   */
  public function __construct()
  {
  }

  /**
   * Possibility to handle the class as a singelton class.
   * @return IF_Translator
   */
  public static function getInstance()
  {
    if (IF_Translator::$instance == NULL)
    {
      IF_Translator::$instance = new IF_Translator();
    }
    return IF_Translator::$instance;
  }

  /**
   * Gets the current locale of the user.
   * @return <type>
   */
  public function getLocale()
  {
    /*if ( $this->useCookies && isset($_COOKIE[$this->cookieName]))
    {
      if (!empty($_COOKIE[$this->cookieName]))
        $this->currentLocale = $_COOKIE[$this->cookieName];
    }*/
    return $this->currentLocale;
  }

  /**
   * Gets all existing(configured) locales.
   * @return array<IF_Locale> list with locales
   */
  public function getAvailableLocales()
  {
    // Open the index.cfg file and check which locales are available.
    $ret = array();
    $f = $this->trDirectory.$this->indexFile;
    if (file_exists($f))
    {
      $data = if_parse_ini_file($f);
      $dataLen = count($data);
      foreach ($data as $section=>&$kv)
      {
        $o = new IF_Locale();
        $o->locale = $section;
        $o->name = $kv["name"];
        $o->author = $kv["author"];
        array_push($ret, $o);
      }
    }
    return $ret;
  }

  /**
   * Sets the directory where the translation files takes place.
   * @param <type> $path
   */
  public function setTranslationDirectory($path)
  {
    // Make sure the path ends with a slash.
    $len = strlen($path);
    $lastSign = substr($path, $len-2, 1);
    if ($lastSign != "/" || $lastSign != "\\")
    {
      $path.="/";
    }
    $this->trDirectory = $path;
  }

  /**
   * Loads the translation file given by its module name.
   * Example:
   *   Module: mysection
   *   Current locale: de_DE
   *   Loaded file: <Translation_directory>/mysection.de_DE.txt
   * @param string $moduleName
   * @return bool
   */
  public function loadModule($moduleName)
  {
    $fileName = $moduleName.".".$this->fileExtension;
    return self::loadTranslationFromFile($fileName);
  }

  /**
   * Loads the translation from file. The file must take place in the
   * defined translation directory.
   */
  public function loadTranslationFromFile($fileName)
  {
    $path = $this->trDirectory.self::getLocale()."/".$fileName;
    if (file_exists($path) && is_readable($path))
    {
      // Read the translation file.
      $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line)
      {
        // Skip comments.
        if (substr($line, 0, 1) == $this->commentSign)
          continue;

        self::parseTranslationFileLine($line);
      }
      return true;
    }
    return false;
  }

  /**
   * Trys to translate the given string and will return the translated
   * value. If there is no translation, the key string will be
   * returned.
   * @param string $s The translation
   * @param array<string> $args The arguments for the translation string.
   */
  public function tr($s, $args=null)
  {
    if (isset($this->translations[$s]))
    {
      return self::resolveArguments($this->translations[$s], $args);
    }
    return self::resolveArguments($s, $args);
  }

  /**
   * Sets the current locale, which should be used for translations.
   * This function also sets the cookie for the user, if the property "useCookies"
   * is setted to TRUE.
   * Example: de_DE, en_GB, en_US
   * @param <type> $locale
   */
  public function setCurrentLocale($locale)
  {
    $this->currentLocale = $locale;
  }

  /*****************************************************************************
   * Helper functions
   ****************************************************************************/

  function resolveArguments($str, $args=null)
  {
    if ($args == null)
      return $str;

    $argsCount = count($args);
    for ($i=0; $i<$argsCount; $i++)
    {
      $str = str_replace("%".$i, $args[$i], $str);
    }
    return $str;
  }

  /**
   * Gets the path of the current translation file.
   * @return string
   */
  protected function getCurrentTranslationFile()
  {
    if (!empty($this->trDirectory))
      return $this->trDirectory.$this->currentLocale.".txt";
    return NULL;
  }

  /**
   * Parses the given translation file line and pushes it into the
   * data array of this class.
   * @param <type> $line
   */
  protected function parseTranslationFileLine($line)
  {
    // Find the comma which separates the two strings.
    // The comma can be identified by checking whether the next
    // character to the comma is a ".
    $len = strlen($line);
    if ($len <= 0 || $line == '\n' || $line == '\n\r')
      return;

    $sepPos = 0;
    do
    {
      // pos of comma.
      $sepPos = strpos($line, ",", $sepPos);

      // is the next char of the comma the searched " sign?
      $character = NULL;
      $i=0;
      do
      {
        $i++;
        $character = substr($line, $sepPos+$i, 1);
      }
      while ($character == " ");

      //if ($sepPos+$i >= $len)
      //  return; // Abort.. invalid translation line.
      
      if ($character != "\"")
      {
        // search the next comma.
        $sepPos += $i;
        continue;
      }

      //echo $line."(comma-position: ".$sepPos."; character: ".$character.")<br>";

      // Get key from line.
      $key = substr($line, 0, $sepPos);
      $key = substr($key, strpos($key, "\"")+1, strrpos($key, "\"")-1);
      //echo "key: $key<br>";

      // Get value from line.
      $value = substr($line, $sepPos);
      $value = substr($value, strpos($value,"\"")+1, strrpos($value, "\"")-2);
      //echo "val: $value<br>";

      $this->translations[$key] = $value;
      return;
    }
    while (true);
  }
}
?>
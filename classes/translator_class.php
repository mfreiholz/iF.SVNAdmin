<?php
/**
 * iF.SVNAdmin
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
// translates key strings into the wished language.
//
// idea:
// load all translation string of the given language-id into a memcached server.
// this would provide a much faster access to the strings.
//
// idea:
// load the file into a local assoc array and access the strings by the
// key string. $arr["key-string"]
//
class IFTranslator
{
  private $m_languageDirectory = "./lang";
  private $m_langid = NULL;
  private $m_section = NULL;
  
  // holds the translated strings.
  private $m_data = array();
  
  // private ctor.
  // only callable from static public functions.
  public function __construct( $langid = "de", $section = "default" )
  {
    $this->m_langid = $langid;
    $this->m_section = $section;
    
    self::load();
  }
  
  // translates the given key-string <i>$key</i> into the target language.
  public function tr( $key )
  {
    $val = $this->m_data[$key];
    if( empty($val) )
    {
      $val = $key;
    }
    return $val;
  }
  
  // loads the language strings.
  private function load()
  {
    $filename = $this->m_langid . "_" . $this->m_section . ".php";
    $langfile = $this->m_languageDirectory . "/" . $filename;
    
    // loads the data into the "m_data" array.
    include_once($langfile);
  }
}
?>
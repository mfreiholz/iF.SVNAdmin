<?php
/**
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

/**
 * Provides functionality to handle the contents of a ".history.db" file.
 *
 * @author Zhaohui Mei. hellogitlab.com
 */
class IF_History
{
  // Holds the history file data as an array
  private $m_data = array();

  // Holds the History database file
  private $m_dbfile = NULL;

  // Holds the error number, if a error occured.
  private $m_errno = 0;

  //////////////////////////////////////////////////////////////////////////////
// Create table:
// CREATE TABLE History(ID INTEGER PRIMARY KEY AUTOINCREMENT, USERNAME CHAR(25) NOT NULL, ACTION CHAR(50) NOT NULL, DATE CHAR(50) NOT NULL, DESCRIPTION CHAR(250) NOT NULL);
// Insert data:
// INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRIPTION) VALUES (null, 'meizhaohui', 'Repo view', '2020-03-16_233845', '描述信息' );
// INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRIPTION) VALUES (null, 'tester', 'User view', '2020-03-16_233845', '描述信息' );
  /**
   * Creates a new instance of this class and assigns the given
   * file as ".history.db" file to it.
   *
   * @param string $db_file
   */
  public function __construct($db_file)
  {
    $this->m_dbfile = $db_file;
  }

  /**
   * Loads the file content and does some init operations.
   *
   * @return void
   */
  public function init()
  {
    $db = self::parseUserFile($this->m_dbfile);
    global $appEngine;$appEngine->addMessage(var_dump($db));
    return $db;
  }

  // 获取所有历史记录
  public function getHistories()
  {
    return $this->m_data;
  }

  public function getHistoryList()
  {
    $relist = array();
    foreach($this->m_data as $history)
    {
      global $appEngine;$appEngine->addMessage(var_dump($history));
    }
  }

  // 插入一条历史记录到数据库中
  public function createHistory($objHistory)
  {
    return $this->writeToFile($this->m_dbfile, $objHistory);
  }


  //////////////////////////////////////////////////////////////////////////////

  /**
   * Parses the database file and saves the data in a localy holded array, which
   * can be accessed by the public functions of this class.
   *
   * @param striing $dbfile The file to parse.
   * @return bool
   */
  private function parseUserFile($dbfile)
  {
    if (!file_exists($dbfile)) {
      // File does not exist.
      $this->m_errno = 1;
      return false;
    }

    if (!is_readable($dbfile)) {
      // No permission to read the file.
      $this->m_errno = 2;
      return false;
    }


    // Read data from db file
    $db = new SQLite3($dbfile);
    $queryd = $db->query('select * from History');
    global $appEngine;$appEngine->addMessage(var_dump($queryd));
    while($row = $queryd->fetchArray()){
      $h = new \svnadmin\core\entities\History();
      $h->id = $row['ID'];
      $h->username = $row['USERNAME'];
      $h->action = $row['ACTION'];
      $h->date = $row['DATE'];
      $h->description = $row['DESCRIPTION'];
      array_push($this->m_data, $h);
    }
    global $appEngine;$appEngine->addMessage(var_dump($this->m_data));

    $db->close();
    return true;
  }

  /**
   * Saves the local m_data, which holds the history information to the given file.
   *
   * @param $filename
   * @return unknown_type
   */
  public function writeToFile($filename = NULL, $objHostory)
  {
    if ($filename == NULL) {
      $filename = $this->m_dbfile;
    }
    if (!is_readable($filename)) {
      // No permission to read the file.
      $this->m_errno = 2;
      return false;
    }
    else if (!is_writeable($filename)){
      // No permission to write the file.
      $this->m_errno = 3;
      return false;
    }

    // Open file and write the array of histoies to it.
    $sql = "INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRPTION) VALUES (" .
      $objHostory->username . ", " . $objHostory->action . ", " . $objHostory->date . ", " . $objHostory->descrption . ");";
    $db = new SQLite3($filename);
    $query = $db->exec($sql);
    if (!$query){
      print_r("error message: ". $db->lastErrorMsg());
      return false;
    }
    return true;
  }
}
?>
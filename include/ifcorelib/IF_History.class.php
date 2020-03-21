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

  // holds the all history items number
  private $m_number = NULL;

  // Holds the History database file
  private $m_dbfile = NULL;

  // Holds the error number, if a error occured.
  private $m_errno = 0;

  //////////////////////////////////////////////////////////////////////////////
// Create table:
// CREATE TABLE History(ID INTEGER PRIMARY KEY AUTOINCREMENT, USERNAME CHAR(25) NOT NULL, ACTION CHAR(50) NOT NULL, DATE CHAR(50) NOT NULL, DESCRIPTION CHAR(250) NOT NULL);
// Insert data:
// INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRIPTION) VALUES (null, 'meizhaohui', 'Repo view', '2020-03-16_233845', '描述信息' );
// INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRIPTION) VALUES (null, 'tester', 'User view', '2020-03-16 233845', '描述信息' );
// INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRIPTION) VALUES (null, 'tester', 'User Add', '2020-03-17 104945', '增加用户' );
// INSERT INTO History (ID,USERNAME,ACTION,DATE,DESCRIPTION) VALUES (null, 'tester', 'User Delete', '2020-03-17 10:40:45', '删除用户' );
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
    return $db;
  }

  // 获取所有历史记录
  public function getHistoryList($q)
  {
    $ret = array();
    self::parseUserFile($this->m_dbfile, $q);
    $ret['items'] = $this->m_data;
    $ret['number'] = $this->m_number;
    return $ret;
  }

  // 插入一条历史记录到数据库中
  public function createHistory($user_action = null, $description = null)
  {
    $h = new \svnadmin\core\entities\History();
    $h->id = null;
    global $appEngine;
    $h->username = $appEngine->getSessionUsername();
//    $h->user_action = array('Repository', 'User', 'Group', 'AccessPath')[array_rand(array('Repository', 'User', 'Group', 'AccessPath'), 1)] . ' ' . array('Add', 'Delete')[array_rand(array('Add', 'Delete'))];
    $h->user_action = $user_action;
    ini_set('date.timezone', 'Asia/Shanghai');
    $objDate = new DateTime();
    $h->date = $objDate->format("Y-m-d H:i:s u");
//    $h->date = date("Y-m-d H:i:s u", time());
//    $h->description = array('Repository', 'User', 'Group', 'AccessPath')[array_rand(array('Repository', 'User', 'Group', 'AccessPath'), 1)] . ' ' . array('Add', 'Delete')[array_rand(array('Add', 'Delete'))];
    $h->description = $description;
    return $this->writeToFile($this->m_dbfile, $h);
  }


  //////////////////////////////////////////////////////////////////////////////

  /**
   * Parses the database file and saves the data in a localy holded array, which
   * can be accessed by the public functions of this class.
   *
   * @param string $dbfile The file to parse.
   * @param string $query_type query type, default is page.
   * @return bool
   */
  private function parseUserFile($dbfile, $query_type = null)
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

    // get the history items number
    $rows = $db->query('SELECT count(*) as count FROM History');
    $row = $rows->fetchArray();
    $this->m_number = $row['count'];

    $page_items_number = HISTORY_PAGE_ITEMS;
    // create the query sql string
    if ($query_type == NULL || $$query_type == "page") {
      $condition = "order by id desc limit " . $page_items_number;
    } else if ($query_type == "show_day") {
      $condition = "where date between date('now','localtime', 'start of day') and date('now', 'localtime', 'start of day', '1 day')";
    } else if ($query_type == "yesterday") {
      $condition = "where date between date('now','localtime', 'start of day', '-1 day') and date('now', 'localtime', 'start of day')";
    } else if ($query_type == "one_week") {
      $condition = "where date between date('now', 'localtime', 'start of day','-6 day','weekday 1') and date('now', 'localtime', 'start of day', '1 day')";
    } else if ($query_type == "one_month") {
      $condition = "where date between date('now', 'localtime', 'start of month') and date('now', 'localtime', 'start of day', '1 day')";
    } else if ($query_type == "show_all") {
      $condition = "";
    } else {
      $condition = " order by id desc limit " . $page_items_number * ($query_type - 1) . "," . $page_items_number;
    }


    // the table name is "History" in the sqlite database
    $q = $db->query('SELECT * FROM History ' . $condition);

    $this->m_data = array();
    while ($row = $q->fetchArray()) {
      $h = new \svnadmin\core\entities\History();
      $h->id = $row['ID'];
      $h->username = $row['USERNAME'];
      $h->user_action = $row['ACTION'];
      $h->date = $row['DATE'];
      $h->description = $row['DESCRIPTION'];
      array_push($this->m_data, $h);
    }
    usort($this->m_data, array('\svnadmin\core\entities\History', "compare"));
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
    if (!file_exists($filename)) {
      touch($filename);
      // Create table:
      // CREATE TABLE History(ID INTEGER PRIMARY KEY AUTOINCREMENT, USERNAME CHAR(25) NOT NULL, ACTION CHAR(50) NOT NULL, DATE CHAR(50) NOT NULL, DESCRIPTION CHAR(250) NOT NULL);
      $create_table = "CREATE TABLE History(ID INTEGER PRIMARY KEY AUTOINCREMENT, USERNAME CHAR(25) NOT NULL, ACTION CHAR(50) NOT NULL, DATE CHAR(50) NOT NULL, DESCRIPTION CHAR(250) NOT NULL);";
      $db = new SQLite3($filename);
      $db->exec($create_table);
    }
    if (!is_readable($filename)) {
      // No permission to read the file.
      $this->m_errno = 2;
      return false;
    } else if (!is_writeable($filename)) {
      // No permission to write the file.
      $this->m_errno = 3;
      return false;
    }
    // Open file and write the array of histoies to it.
    $sql = "INSERT INTO History (ID, USERNAME, ACTION, DATE, DESCRIPTION) VALUES (null, '" .
      $objHostory->username . "', '" . $objHostory->user_action . "', '" . $objHostory->date . "', '" . $objHostory->description . "');";
    $db = new SQLite3($filename);
    $query = $db->exec($sql);
    if (!$query) {
      print_r("error message: " . $db->lastErrorMsg());
      $db->close();
      return false;
    }
    $db->close();
    return true;
  }
}

?>
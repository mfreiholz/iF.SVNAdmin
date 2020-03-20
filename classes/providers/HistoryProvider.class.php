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

namespace svnadmin\providers {
  class HistoryProvider
  {
    private $m_database_file = NULL;
    private $m_init_done = false;
    private static $m_instance = NULL;

    public static function getInstance()
    {
      if (self::$m_instance == NULL) {
        self::$m_instance = new HistoryProvider;
      }
      return self::$m_instance;
    }

    public function init()
    {
      global $appEngine;
      if (!$this->m_database_file) {
        $this->m_init_done = true;
        // get the database file ./data/.history.db
        $this->m_database_file = new \IF_History($appEngine->getConfig()->getValue("History", "DatabaseFile"));
        return $this->m_database_file->init();
      }
      return false;
    }

    /**
     * get history list
     * @param string $q query type
     * @return mixed
     */
    public function getHistories($q)
    {
      $historyArray = $this->m_database_file->getHistoryList($q);
      return $historyArray;
    }

    /**
     * add history data to database
     * @param null $user_action
     * @param null $description
     * @return mixed
     */
    public function addHistory($user_action = null, $description = null)
    {
      if (!empty($user_action) && !empty($description)) {
        return $this->m_database_file->createHistory($user_action, $description);
      }
    }
  }
}
?>
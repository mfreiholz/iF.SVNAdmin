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
namespace svnadmin\providers
{
  class HistoryProvider
//  class HistoryProvider implements \svnadmin\core\interfaces\IHistoryProvider
  {
    private $m_database_file = NULL;
    private $m_init_done = false;
    private static $m_instance = NULL;

    public static function getInstance()
    {
      if( self::$m_instance == NULL )
      {
        self::$m_instance = new HistoryProvider;
      }
      return self::$m_instance;
    }

    //////////////////////////////////////////////////////////////////////////////
    // -- Base interface implementations ----------------------------------------
    //////////////////////////////////////////////////////////////////////////////

    public function init()
    {
      global $appEngine;
      if( !$this->m_database_file )
      {
        $this->m_init_done = true;
//        global $appEngine;$appEngine->addMessage(var_dump($appEngine->getConfig()));
        global $appEngine;$appEngine->addMessage(var_dump($appEngine->getConfig()->getValue("History", "DatabaseFile")));
        $this->m_database_file = new \IF_History($appEngine->getConfig()->getValue("History", "DatabaseFile"));
        return $this->m_database_file->init();
      }
      return false;
    }


    public function getHistories()
    {
      $historyArray = $this->m_database_file->getHistoryList();
      $retList = array();
      if( is_array( $historyArray ) )
      {
        for( $i=0; $i<count($historyArray); $i++ )
        {
          $historyObj = new \svnadmin\core\entities\History;
          $historyObj->id = $historyArray[$i];
          $historyObj->username = $historyArray[$i];
          $historyObj->action = $historyArray[$i];
          $historyObj->description = $historyArray[$i];
          array_push( $retList, $historyObj );
        }
      }

      return $retList;
    }

    public function addHistory( $objHisotry )
    {
      if( $objHisotry != NULL &&
        !empty($objHisotry->username) &&
        !empty($objHisotry->action) &&
        !empty($objHisotry->descrption))
      {
        return $this->m_database_file->createHistory($objHisotry);
      }
    }

  }
}
?>
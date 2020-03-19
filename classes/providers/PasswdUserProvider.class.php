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
  class PasswdUserProvider implements \svnadmin\core\interfaces\IUserViewProvider,
                                      \svnadmin\core\interfaces\IUserEditProvider
  {
    private $m_userfile = NULL;
    private $m_init_done = false;
    private static $m_instance = NULL;

    public static function getInstance()
    {
      if( self::$m_instance == NULL )
      {
        self::$m_instance = new PasswdUserProvider;
      }
      return self::$m_instance;
    }

    //////////////////////////////////////////////////////////////////////////////
    // -- Base interface implementations ----------------------------------------
    //////////////////////////////////////////////////////////////////////////////

    public function init()
    {
      global $appEngine;
      if( !$this->m_init_done )
      {
        $this->m_init_done = true;
        $this->m_userfile = new \IF_HtPasswd($appEngine->getConfig()->getValue("Users:passwd", "SVNUserFile"));
        return $this->m_userfile->init();
      }
      return false;
    }

	public function save()
    {
		if (!$this->m_userfile->writeToFile())
		{
			throw new Exception("Unable to save file.");
		}
		return true;
    }

    public function isUpdateable()
    {
      return false;
    }

    public function update()
    {
      return true;
    }

    //////////////////////////////////////////////////////////////////////////////
    // -- IUserViewProvider ------------------------------------------------------
    //////////////////////////////////////////////////////////////////////////////

    public function getUsers($withStarUser=true)
    {
      // Convert the list of user names into a list of User objects.
      $userNamesArray = $this->m_userfile->getUserList();
      $retList = array();
      if( is_array( $userNamesArray ) )
      {
        for( $i=0; $i<count($userNamesArray); $i++ )
        {
          $userObj = new \svnadmin\core\entities\User();
          $userObj->id = $userNamesArray[$i];
          $userObj->name = $userNamesArray[$i];
          array_push( $retList, $userObj );
        }
      }

      // Staticly get the '*' user.
      if ($withStarUser)
      {
        $oUAll = new \svnadmin\core\entities\User;
        $oUAll->id = '*';
        $oUAll->name = '*';
        array_push( $retList, $oUAll );
      }

      return $retList;
    }

    public function userExists( $objUser )
    {
      return $this->m_userfile->userExists( $objUser->name );
    }

    public function authenticate( $objUser, $password )
    {
      return $this->m_userfile->authenticate( $objUser->name, $password );
    }

    //////////////////////////////////////////////////////////////////////////////
    // -- IUserEditProvider ------------------------------------------------------
    //////////////////////////////////////////////////////////////////////////////

    public function addUser( $objUser )
    {
      if( $objUser != NULL && !empty($objUser->name) && !empty($objUser->password) )
      {
        return $this->m_userfile->createUser( $objUser->name, $objUser->password, true );
      }
    }

    public function deleteUser( $objUser )
    {
      return $this->m_userfile->deleteUser( $objUser->name );
    }

    public function changePassword($user, $newpass)
    {
      return $this->m_userfile->changePassword($user, $newpass);
    }
  }
}
?>
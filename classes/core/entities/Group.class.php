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
namespace svnadmin\core\entities
{
  class Group
  {
    public $id;
    public $name;

    // Object(Permission)
    public $perm;

    public function __construct($id = null, $name = null)
    {
      $this->id = $id;
      $this->name = $name;
    }

    public function ctr($id, $name)
    {
      $this->id = $id;
      $this->name = $name;
    }

    public function getName()
    {
      return $this->name;
    }

    public function getPermission()
    {
      return $this->perm;
    }

    public function getEncodedName()
    {
      return rawurlencode($this->name);
    }

    public static function compare($o1, $o2)
    {
      if ($o1->name == $o2->name) {
        return 0;
      }
      return ($o1->name > $o2->name) ? +1 : -1;
    }

    /*
     * get the users of the group
     */
    public function getUsersOfGroup()
    {
      $retArray = array();
      global $appEngine;
      $m_authfile = new \IF_SVNAuthFileC($appEngine->getConfig()->getValue("Subversion", "SVNAuthFile"));
      $userNamesArray = $m_authfile->usersOfGroup($this->name);

      if (is_array($userNamesArray)) {
        for ($i = 0; $i < count($userNamesArray); $i++) {
          $userObj = new \svnadmin\core\entities\User();
          $userObj->id = $userNamesArray[$i];
          $userObj->name = $userNamesArray[$i];
          array_push($retArray, $userObj);
        }
      }
      return $retArray;
    }


    public function getSubgroupOfGroup()
    {
      $retArray = array();
      global $appEngine;
      $m_authfile = new \IF_SVNAuthFileC($appEngine->getConfig()->getValue("Subversion", "SVNAuthFile"));
      $groupNamesArray = $m_authfile->groupsOfGroup($this->name);

      if (is_array($groupNamesArray)) {
        for ($i = 0; $i < count($groupNamesArray); $i++) {
          $groupObj = new \svnadmin\core\entities\Group();
          $groupObj->id = $groupNamesArray[$i];
          $groupObj->name = $groupNamesArray[$i];
          array_push($retArray, $groupObj);
        }
      }
      return $retArray;
    }
  }
}
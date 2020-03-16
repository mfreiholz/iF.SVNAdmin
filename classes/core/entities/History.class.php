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
  class History
  {
    public $id;
    public $username;
    public $action;
    public $date;
    public $description;

    public function __construct($id=null, $username=null, $action=null, $date=null, $description=null)
    {
      $this->id = $id;
      $this->username = $username;
      $this->action = $action;
      $this->date = $date;
      $this->description = $description;
    }

    public function ctr( $id, $username, $action, $date, $description)
    {
      $this->id = $id;
      $this->username = $username;
      $this->action = $action;
      $this->date = $date;
      $this->description = $description;
    }

    public function getUsername()
    {
      return $this->username;
    }

    public function getAction()
    {
      return $this->action;
    }

    public function getDate()
    {
      return $this->date;
    }

    public function getDescription()
    {
      return $this->description;
    }


    public static function compare( $o1, $o2 )
    {
      if( $o1->username == $o2->username )
      {
        return 0;
      }
      return ($o1->username > $o2->username) ? +1 : -1;
    }
  }
}
?>
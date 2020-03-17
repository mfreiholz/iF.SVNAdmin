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
    public $user_action;
    public $date;
    public $description;

    public function __construct($id=null, $username=null, $user_action=null, $date=null, $description=null)
    {
      $this->id = $id;
      $this->username = $username;
      $this->user_action = $user_action;
      $this->date = $date;
      $this->description = $description;
    }

    public function ctr( $id, $username, $user_action, $date, $description)
    {
      $this->id = $id;
      $this->username = $username;
      $this->user_action = $user_action;
      $this->date = $date;
      $this->description = $description;
    }

    public function getUsername()
    {
      return $this->username;
    }

    public function getAction()
    {
      return $this->user_action;
    }

    public function getDate()
    {
      return $this->date;
    }

    public function getDescription()
    {
      return $this->description;
    }

    // order by date time desc
    public static function compare( $o1, $o2 )
    {
      if( $o1->date == $o2->date )
      {
        return 0;
      }
      return ($o1->date > $o2->date) ? -1 : +1;
    }
  }
}
?>
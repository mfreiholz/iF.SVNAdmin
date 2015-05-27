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

    public function __construct($id=null, $name=null)
    {
      $this->id = $id;
      $this->name = $name;
    }

    public function ctr( $id, $name )
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
      return rawurlencode( $this->name );
    }

    public static function compare( $o1, $o2 )
    {
      if( $o1->name == $o2->name )
      {
        return 0;
      }
      return ($o1->name > $o2->name) ? +1 : -1;
    }
  }
}
?>
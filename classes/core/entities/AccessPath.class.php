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
  class AccessPath
  {
    public $path;
    public $perm;
    public $inherited;
    public $managers;

    public function __construct($path=null, $perm=null, $inherited=null, $managers=null)
    {
      $this->path = $path;
      $this->perm = $perm;
      $this->inherited = $inherited;
      $this->managers = $managers;
    }

    public function getPath()
    {
      return $this->path;
    }

    public function getPerm()
    {
      return $this->perm;
    }

    public function getInherited()
    {
      return $this->inherited;
    }

    public function getManagersAsString()
    {
    	if (!empty($this->managers) && is_array($this->managers))
    	{
    		return join(",", $this->managers);
    	}
    	else
    	{
    		return "-";
    	}
    }

    public function getEncodedPath()
    {
      return rawurlencode( $this->path );
    }

    public static function compare( $o1, $o2 )
    {
      if( $o1->path == $o2->path )
      {
        return 0;
      }
      return ($o1->path > $o2->path) ? +1 : -1;
    }
  }
}
?>
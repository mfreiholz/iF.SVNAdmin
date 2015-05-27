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
  class Permission
  {
    // Static defined available permissions.
    public static $PERM_NONE = "No-Access";
    public static $PERM_READ = "Read";
    public static $PERM_READWRITE = "Read-Write";
    
    // The permission of this object.
    public $perm;
    
    public function __construct()
    {
      $this->perm = NULL;
    }
    
    public function getPerm()
    {
      return $this->perm;
    }
    
    public function getVisibleName()
    {
    	switch ($this->perm)
    	{
    		case Permission::$PERM_READ:
    			return "Read-Only";
    		case Permission::$PERM_READWRITE:
    			return "Read-Write";
    		case Permission::$PERM_NONE:
    			return "No access";
    	}
    	return "Unknown";
    }
    
    public function getDescription()
    {
    }
  }
}
?>
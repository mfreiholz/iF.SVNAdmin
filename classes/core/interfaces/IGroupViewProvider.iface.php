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
namespace svnadmin\core\interfaces
{
  interface IGroupViewProvider extends IViewProvider
  {
    /**
     * Gets an array filled with group objects.
     * @return Group[]
     */
    public function getGroups();

    /**
     * Checks whether the given group exists.
     * @param $objGroup
     * @return bool
     */
    public function groupExists( $objGroup );

    /**
     * Gets the associated groups of the given user.
     * @param $objUser
     * @return array
     */
    public function getGroupsOfUser( $objUser );

    /**
     * Gets the associated users of the given group.
     * @param $objGroup
     * @return array
     */
    public function getUsersOfGroup( $objGroup );
    
    /**
     * Checks whether the user is in group.
     * @param User $objUser
     * @param Group $objGroup
     * @return bool
     */
    public function isUserInGroup( $objUser, $objGroup );
  }
}
?>
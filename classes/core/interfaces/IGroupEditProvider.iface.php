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
  interface IGroupEditProvider extends IEditProvider
  {
    /**
     * Adds the given group.
     * @param $objGroup
     * @param $reason
     * @return bool
     */
    public function addGroup( $objGroup, $reason);

    /**
     * Removes the given group.
     * @param $objGroup
     * @return bool
     */
    public function deleteGroup( $objGroup );

    /**
     * Assigns the user to group.
     * @param $objUser
     * @param $objGroup
     * @return bool
     */
    public function assignUserToGroup( $objUser, $objGroup, $reason);

    /**
     * Assigns the subgroup to group.
     * @param $objSubgroup
     * @param $objGroup
     * @param $reason
     * @return bool
     */
    public function assignSubgroupToGroup( $objSubgroup, $objGroup, $reason);

    /**
     * Removes the user from group.
     * @param $objUser
     * @param $objGroup
     * @param $reason
     * @return bool
     */
    public function removeUserFromGroup( $objUser, $objGroup, $reason);

    /**
     * Removes the group from group.
     * @param $objSubgroup
     * @param $objGroup
     * @param $reason
     * @return bool
     */
    public function removeSubgroupFromGroup( $objSubgroup, $objGroup, $reason);

    /**
     * Removes the user from all groups where he is associated.
     * @param User $objUser
     * @param return bool
     */
    public function removeUserFromAllGroups( $objUser );

    /**
     * Removes the group from all groups where he is associated.
     * @param Group $objSubgroup
     * @param return bool
     */
    public function removeSubgroupFromAllGroups( $objSubgroup );
  }
}
?>
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
include("include/config.inc.php");

if (!$appEngine->isGroupViewActive() && !$appEngine->isAccessPathViewActive())
  $appEngine->forwardInvalidModule(true);
  
$appEngine->checkUserAuthentication(true, ACL_MOD_GROUP, ACL_ACTION_VIEW);

$appTR->loadModule("groupview");

// Action handling.
if (check_request_var('unassign'))
{
  $appEngine->handleAction('unassign_usergroup');
}
if (check_request_var('unassign_permission'))
{
  $appEngine->handleAction('unassign_permission');
}
if (check_request_var('assign_usergroup'))
{
	$appEngine->handleAction("assign_usertogroup");
}

// Get required variables.
$groupname = get_request_var('groupname');

// Current selected group.
$oGroup = new \svnadmin\core\entities\Group;
$oGroup->id = $groupname;
$oGroup->name = $groupname;

// Users of group
$users=null;
$allusers=null;
if ($appEngine->isGroupViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_GROUP, ACL_ACTION_VIEW))
{
  $users = $appEngine->getGroupViewProvider()->getUsersOfGroup( $oGroup );
  usort( $users, array('\svnadmin\core\entities\User',"compare") );
  
  // All users except the already assigned users.
  if ($appEngine->isUserViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_GROUP, ACL_ACTION_ASSIGN))
  {
    $allusers = $appEngine->getUserViewProvider()->getUsers(false);
    usort($allusers, array('\svnadmin\core\entities\User',"compare"));
    $len = count($users);
    for ($i=0; $i<$len; $i++)
    {
    	if_array_remove_object_element($allusers, $users[$i], "name");
    }
    $allusers = array_values($allusers);
  }
}

// Access-Path permissions of the current group.
$paths=null;
if ($appEngine->isAccessPathViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW))
{
  $paths = $appEngine->getAccessPathViewProvider()->getPathsOfGroup( $oGroup );
  usort( $paths, array('\svnadmin\core\entities\AccessPath',"compare") );
}

SetValue("GroupName", $groupname);
SetValue("GroupNameEncoded", rawurlencode($groupname));
SetValue("UserList", $users);
SetValue("AllUserList", $allusers);
SetValue("AccessPathList", $paths);
ProcessTemplate("group/groupview.html.php");
?>

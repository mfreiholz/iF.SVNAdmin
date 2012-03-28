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
$appEngine->forwardInvalidModule(!$appEngine->isAccessPathViewActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW);
$appTR->loadModule("accesspathview");

// Action handling.
if (check_request_var("unassign"))
{
	$appEngine->handleAction("unassign_permission");
}
elseif (check_request_var("assign_permission"))
{
	$appEngine->handleAction("assign_usergrouptoaccesspath");
}

// Get required variables.
$accesspathEnc = get_request_var('accesspath');
$accesspath = rawurldecode( $accesspathEnc );

// View data.
$o = new \svnadmin\core\entities\AccessPath;
$o->path = $accesspath;

$users = $appEngine->getAccessPathViewProvider()->getUsersOfPath($o);
$groups = $appEngine->getAccessPathViewProvider()->getGroupsOfPath($o);

// Data to assign new user permissions.
// Data to assign new group permissions.
if ($appEngine->isAccessPathEditActive() && $appEngine->checkUserAccess(ACL_MOD_ACCESSPATH, ACL_ACTION_ASSIGN))
{
	if ($appEngine->isUserViewActive())
	{
		$allusers = $appEngine->getUserViewProvider()->getUsers();
		usort($allusers, array('\svnadmin\core\entities\User',"compare"));
		SetValue("UserListAll", $allusers);
	}

	if ($appEngine->isGroupViewActive())
	{
		$allgroups = $appEngine->getGroupViewProvider()->getGroups();
		usort($allgroups, array('\svnadmin\core\entities\Group',"compare"));
		SetValue("GroupListAll", $allgroups);
	}
}

SetValue("PermNone", \svnadmin\core\entities\Permission::$PERM_NONE);
SetValue("PermRead", \svnadmin\core\entities\Permission::$PERM_READ);
SetValue("PermReadWrite", \svnadmin\core\entities\Permission::$PERM_READWRITE);
SetValue("UserList", $users);
SetValue("GroupList", $groups);
SetValue("AccessPath", $accesspath);
SetValue("AccessPathEncoded", rawurlencode($accesspath));
ProcessTemplate("accesspath/accesspathview.html.php");
?>
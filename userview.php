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
$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_VIEW);
if( !$appEngine->isGroupViewActive() && !$appEngine->isAccessPathViewActive() )
{
  $appEngine->forwardInvalidModule( true );
}
$appTR->loadModule("userview");
$appTR->loadModule("roles");

// Action handling.
if (check_request_var('unassign'))
{
  $appEngine->handleAction('unassign_usergroup');
}
else if (check_request_var('unassign_permission'))
{
  $appEngine->handleAction('unassign_permission');
}
else if (check_request_var('assign_role'))
{
  $appEngine->handleAction('assign_userrole');
}
else if (check_request_var('unassign_role'))
{
  $appEngine->handleAction('unassign_userrole');
}
else if(check_request_var('unassign_projectmanager'))
{
  $appEngine->handleAction('unassign_projectmanager');
}
else if(check_request_var('assign_usergroup'))
{
	$appEngine->handleAction('assign_usertogroup');
}

// Get required variables.
$usernameEnc = get_request_var('username');
$username = rawurldecode( $usernameEnc );

// The current selected user.
$oUser = new \svnadmin\core\entities\User;
$oUser->id = $username;
$oUser->name = $username;

// Roles of the current user.
$allroles=null;
$rolesOfUser=null;
if ($appEngine->isAclManagerActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_ROLE, ACL_ACTION_VIEW))
{
  $rolesOfUser = $appEngine->getAclManager()->getRolesOfUser($oUser);

  // Get all roles to assign the user to a new role.
  if ($appEngine->checkUserAuthentication(false, ACL_MOD_ROLE, ACL_ACTION_ASSIGN))
  {
    // All existing roles.
    $allroles = $appEngine->getAclManager()->getRoles();
    usort($allroles, array('\svnadmin\core\entities\Role',"compare"));

    // Remove all roles from array, which are already assigned to the user.
    $rolesOfUserLen = count($rolesOfUser);
    for ($i=0; $i<$rolesOfUserLen; $i++)
    {
      if_array_remove_object_element($allroles, $rolesOfUser[$i], "name");
    }
    $allroles = array_values($allroles); // This step is important, to recreate the indexes.
  }
}

// Groups of the user.
$groups=null;
$allgroups=null;
if ($appEngine->isGroupViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_GROUP, ACL_ACTION_VIEW))
{
  $groups = $appEngine->getGroupViewProvider()->getGroupsOfUser( $oUser );
  usort( $groups, array('\svnadmin\core\entities\Group',"compare") );
  
  // Get all existing groups and remove the groups in which the user is already in.
  if ($appEngine->isGroupViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_GROUP, ACL_ACTION_ASSIGN))
  {
  	$allgroups = $appEngine->getGroupViewProvider()->getGroups();
  	usort($allgroups, array('\svnadmin\core\entities\Group',"compare"));
  	$len = count($groups);
  	for ($i=0; $i<$len; $i++)
  	{
  		if_array_remove_object_element($allgroups, $groups[$i], "name");
  	}
  	$allgroups = array_values($allgroups);
  }
}

// Access-Path permissions of the current group.
$paths=null;
if ($appEngine->isAccessPathViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW))
{
  $paths = $appEngine->getAccessPathViewProvider()->getPathsOfUser( $oUser );
  usort( $paths, array('\svnadmin\core\entities\AccessPath',"compare") );
}

// Check whether the user is an project administrator.
$restricted_paths=null;
$isprojectmanager=false;
if ($appEngine->isAuthenticationActive())
{
  $uname = $username;
  if ($appEngine->getAclManager()->isUserAccessPathManager($uname))
  {
    $restricted_paths = $appEngine->getAclManager()->getAccessPathsOfUser($uname);
    if (count($restricted_paths) > 0)
    {
      usort($restricted_paths, array('\svnadmin\core\entities\AccessPath',"compare"));
      $isprojectmanager=true;
    }
  }
}

SetValue("RoleList", $rolesOfUser);
SetValue("RoleListAll", $allroles);
SetValue("GroupList", $groups);
SetValue("GroupListAll", $allgroups);
SetValue("PathList", $paths);
SetValue("RestrictedPathList", $restricted_paths);
SetValue("ProjectManager", $isprojectmanager);
SetValue("Username", $username);
SetValue("UsernameEncoded", rawurlencode($username));
ProcessTemplate("user/userview.html.php");
?>
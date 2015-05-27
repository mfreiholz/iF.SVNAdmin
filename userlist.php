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
$appEngine->forwardInvalidModule(!$appEngine->isUserViewActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_VIEW);
$appTR->loadModule("userlist");
$appTR->loadModule("roles");

// Form request to delete users.
if (check_request_var("delete"))
{
  $appEngine->handleAction("delete_user");
}
elseif (check_request_var("assign_role"))
{
  $appEngine->handleAction("assign_userrole");
}

// Assign roles.
// Get all roles to assign the user to a new role.
$allroles=null;
if ($appEngine->isAclManagerActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_ROLE, ACL_ACTION_ASSIGN))
{
  // All existing roles.
  $allroles = $appEngine->getAclManager()->getRoles();
  usort($allroles, array('\svnadmin\core\entities\Role',"compare"));
}

// Get all users and sort them by name.
$users = $appEngine->getUserViewProvider()->getUsers();
usort( $users, array('\svnadmin\core\entities\User',"compare") );


SetValue("UserList", $users);
SetValue("RoleList", $allroles);
ProcessTemplate("user/userlist.html.php");
?>
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

if (!$appEngine->isUserViewActive() || !$appEngine->isGroupViewActive() || !$appEngine->isGroupEditActive())
{
  $appEngine->forwardInvalidModule(true);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_GROUP, ACL_ACTION_ASSIGN);

// Load language.
$appTR->loadModule("usergroupassign");

// Form request.
$assign = check_request_var('assign');
if($assign)
{
  $appEngine->handleAction('assign_usertogroup');
}

// User list.
$users=null;
if ($appEngine->isUserViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_USER, ACL_ACTION_VIEW))
{
  $users = $appEngine->getUserViewProvider()->getUsers();

  // Remove the * user from array.
  $o = new \svnadmin\core\entities\User;
  $o->id = '*';
  $o->name = '*';
  
  $users = remove_item_by_value($users, $o, true);
  usort($users, array('\svnadmin\core\entities\User',"compare"));
}

// Group list.
$groups=null;
if ($appEngine->isGroupViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_GROUP, ACL_ACTION_VIEW))
{
  $groups = $appEngine->getGroupViewProvider()->getGroups();
  usort( $groups, array('\svnadmin\core\entities\Group',"compare") );
}

SetValue("UserList", $users);
SetValue("GroupList", $groups);
ProcessTemplate("group/membership.html.php");
?>

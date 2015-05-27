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

//
// Authentication
//

if (!$appEngine->isProviderActive(PROVIDER_ACCESSPATH_VIEW))
{
	$appEngine->forwardError(ERROR_INVALID_MODULE);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_VIEW);
$appTR->loadModule("accesspathslist");

//
// Actions
//

// Form 'delete' request.
if (check_request_var("delete"))
{
	$appEngine->handleAction("delete_accesspath");
}
else if (check_request_var("assign_projectmanager"))
{
	$appEngine->handleAction("assign_projectmanager");
}

//
// View Data
//

$list = array();
$users = array();

try {
	// All AccessPaths.
	$list = $appEngine->getAccessPathViewProvider()->getPaths();
	usort($list, array('\svnadmin\core\entities\AccessPath', "compare"));


	// Filter access-paths for project-managers.
	if ($appEngine->isAuthenticationActive())
	{
		$currentUsername = $appEngine->getSessionUsername();
		if ($appEngine->getAclManager()->isUserAccessPathManager($currentUsername))
		{
			$list = $appEngine->getAclManager()->filterAccessPathsList($currentUsername, $list);
		}

		// Load list of users to create a combobox and assign them as project managers to paths.
		if ($appEngine->isUserViewActive() && $appEngine->checkUserAuthentication(false, ACL_MOD_PROJECTMANAGER, ACL_ACTION_ASSIGN))
		{
			$users = $appEngine->getUserViewProvider()->getUsers(false);
			usort($users, array('\svnadmin\core\entities\User', "compare"));
		}

		// Get the project managers of each path.
		$listCount = count($list);
		for ($i=0; $i<$listCount; $i++)
		{
			$managers = $appEngine->getAclManager()->getUsersOfAccessPath($list[$i]->path);
	  		$list[$i]->managers = $managers;
		}
	}

}
catch (Exception $ex) {
	$appEngine->addException($ex);
}

SetValue("UserList", $users);
SetValue("AccessPathList", $list);
ProcessTemplate("accesspath/accesspathlist.html.php");
?>
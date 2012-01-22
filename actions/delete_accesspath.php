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
if (!defined('ACTION_HANDLING')) { die("HaHa!"); }

//
// Authentication
//

if (!$appEngine->isAccessPathEditActive())
{
	$appEngine->forwardInvalidModule(true);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_DELETE);

//
// HTTP Request Vars
//

$selected = get_request_var('selected_accesspaths');

//
// Validation
//

if ($selected == NULL)
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one access-path.")));
}
else
{
	for ($i = 0; $i < count($selected); $i++)
	{
		$ap = new \svnadmin\core\entities\AccessPath();
		$ap->path = $selected[$i];

		// Is the user restricted to some paths? (project-manager)
		if ($appEngine->isAuthenticationActive())
		{
			$currentUsername = $appEngine->getSessionUsername();
			if ($appEngine->getAclManager()->isUserAccessPathManager($currentUsername))
			{
				if (!$appEngine->getAclManager()->isUserAdminOfPath($currentUsername, $ap->getPath()))
				{
					$appEngine->addException(new Exception(tr('No permission to handle Access-Path: %0', array($ap->getPath()))));
					continue;
				}
			}
		}

		// Remove all project-manager assignments to the access-path.
		if ($appEngine->isAclManagerActive())
		{
			$appEngine->getAclManager()->removeAssignmentsToPath($ap->getPath());
		}

		// Remove the access-path.

		if (!$appEngine->getAccessPathEditProvider()->deleteAccessPath($ap))
		{
			$appEngine->addException(new Exception(tr('Can not delete Access-Path: %0', array($ap->getPath()))));
		}
		$appEngine->addMessage(tr('Removed Access-Path: %0', array($ap->getPath())));
	}

	// Save changes.

	$b = $appEngine->getAccessPathEditProvider()->save();
	if (!$b)
	{
		$appEngine->addException(new Exception('Can not save changes to Access-Path-Edit-Provider'));
	}

	if ($appEngine->isAclManagerActive())
	{
		$b = $appEngine->getAclManager()->save();
		if (!$b)
		{
			$appEngine->addException(new Exception('Can not save changes to Acl-Manager'));
		}
	}
}
?>
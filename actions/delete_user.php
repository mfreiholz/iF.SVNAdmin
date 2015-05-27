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
if (!defined('ACTION_HANDLING'))
{
  die("HaHa!");
}

$appEngine->forwardInvalidModule(!$appEngine->isUserEditActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_DELETE);

$selusers = get_request_var('selected_users');

if ($selusers == NULL)
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one user.")));
}
else
{
	try {
	  $doneList = array();
	  $warnList = array();
	  $failedList = array();
	  $selusersCount = count($selusers);
	
	  for($i=0; $i<$selusersCount; $i++)
	  {
	    $u = new \svnadmin\core\entities\User;
	    $u->id = $selusers[$i];
	    $u->name = $selusers[$i];
	
	    // Skip * user.
	    if ($u->name == "*")
	    {
	      continue;
	    }
	
	    // Remove user from groups.
	    if ($appEngine->isGroupEditActive())
	    {
	      $appEngine->getGroupEditProvider()->removeUserFromAllGroups($u);
	    }
	
	    // Remove user from Access-Path's.
	    if ($appEngine->isAccessPathEditActive())
	    {
	      $appEngine->getAccessPathEditProvider()->removeUserFromAllAccessPaths($u);
	    }
	
	    // Remove project-manager associations.
	    if ($appEngine->isAclManagerActive())
	    {
	      if ($appEngine->getAclManager()->isUserAccessPathManager($u->name))
	      {
	        if (!$appEngine->getAclManager()->deleteAccessPathAdmin($u->name))
	        {
	          $appEngine->addException(new Exception(tr("Could not remove user's Access-Path-Manager associations:"+$u->name)));
	        }
	      }
	    }
	
	    // Remove roles of user.
	    if ($appEngine->isAclManagerActive())
	    {
	      if ($appEngine->getAclManager()->removeAllRolesFromUser($u->name))
	      {
	      }
	    }
	
	    // Delete the user.
	    $done = $appEngine->getUserEditProvider()->deleteUser($u);
	    if ($done)
	    {
	    	$appEngine->addMessage(tr("Removed user %0 successfully.", array($u->name)));
	    }
	    else
	    {
	    	$appEngine->addException(tr("Can not remove user %0."));
	    }
	  } // for(users)
	  
	  $appEngine->getUserEditProvider()->save();
	
	  if ($appEngine->isGroupEditActive())
	    $appEngine->getGroupEditProvider()->save();
	
	  if ($appEngine->isAccessPathEditActive())
	    $appEngine->getAccessPathEditProvider()->save();
	
	  if ($appEngine->isAclManagerActive())
	    $appEngine->getAclManager()->save();
	    
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
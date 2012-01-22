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
if( !defined('ACTION_HANDLING') ) {
  die("HaHa!");
}

// Check module.
$appEngine->forwardInvalidModule(!$appEngine->isAclManagerActive());

// Selected users on page.
$selusers = get_request_var("selected_users");

// Selected roles on page.
$selroles = get_request_var("selected_roles");

// Validate the selection.
if ($selusers == NULL || $selroles == NULL)
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one user and one role.")));
}
else
{
	try {
	  // Count of: All, Done, Failed
	  $cntAll = count($selroles) * count($selusers);
	
	  // Iterate all selected users and roles.
	  for ($i=0; $i<count($selroles); $i++)
	  {
	    $oR = new \svnadmin\core\entities\Role;
	    $oR->name = $selroles[$i];
	
	    for ($j=0; $j<count($selusers); $j++)
	    {
	      $oU = new \svnadmin\core\entities\User;
	      $oU->name = $selusers[$j];
	
	      if ($appEngine->getAclManager()->removeUserFromRole($oU, $oR))
	      {
	      	$appEngine->addMessage(tr("The user %0 has been removed from role %1", array($oU->name, $oR->name)));
	      }
	      else
	      {
	      	$appEngine->addException(tr("Can not remove user %0 from role %1", array($oU->name, $oR->name)));
	      }
	    } //for
	  } //for
	
	  if (!$appEngine->getAclManager()->save())
	    ;//throw new Exception("Could not save ACL.");
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
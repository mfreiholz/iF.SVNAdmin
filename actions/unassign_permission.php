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
if(!defined('ACTION_HANDLING'))
{
  die("HaHa!");
}

$appEngine->forwardInvalidModule(!$appEngine->isAccessPathEditActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN);

$selusers = get_request_var('selected_users');
$selgroups = get_request_var('selected_groups');
$selpaths = get_request_var('selected_accesspaths');

if ($selpaths == NULL || ($selgroups == NULL && $selusers == NULL))
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one access-path and one user or group.")));
}
else
{
	try {
	  $selpathsLen = count($selpaths);
	  
	  $selusersLen = 0;
	  if( $selusers != NULL ){
	    $selusersLen = count($selusers);
	  }
	  
	  $selgroupsLen = 0;
	  if( $selgroups != NULL ){
	    $selgroupsLen = count($selgroups);
	  }
	
	  // Count of: All, Done, Failed.
	  $cntAll = ($selpathsLen*$selgroupsLen) + ($selpathsLen*$selusersLen);
	  $doneList = array();
	  $noPermList = array();
	  $failedList = array();
	  
	  // Iterate all selected_accesspaths.
	  for( $i=0; $i<$selpathsLen; $i++ )
	  {
	    $oAP = new \svnadmin\core\entities\AccessPath;
	    $oAP->id = $selpaths[$i];
	    $oAP->path = $selpaths[$i];
	
	    // Is the user restricted to some paths? (project-manager)
	    if ($appEngine->isAuthenticationActive())
	    {
	      $currentUsername = $appEngine->getSessionUsername();
	      if ($appEngine->getAclManager()->isUserAccessPathManager($currentUsername))
	      {
	        if (!$appEngine->getAclManager()->isUserAdminOfPath($currentUsername, $selpaths[$i]))
	        {
	        	$appEngine->addException(tr("You don't have the permission to unassign the access path %0", array($oAP->path)));
	          continue;
	        }
	      }
	    }
	
	    // Iterate selected_users.
	    for( $iu=0; $iu<$selusersLen; $iu++ )
	    {
	      $oU = new \svnadmin\core\entities\User;
	      $oU->id = $selusers[$iu];
	      $oU->name = $selusers[$iu];
	      
	      $done = $appEngine->getAccessPathEditProvider()->removeUserFromAccessPath( $oU, $oAP );
	      if ($done)
	      	$appEngine->addMessage(tr("Removed user %0 from access path %1", array($oU->name, $oAP->path)));
	      else
	      	$appEngine->addException(tr("Can not remove user %0 from access path %1", array($oU->name, $oAP->path)));
	    }
	    
	    // Iterate selected_groups.
	    for( $ig=0; $ig<$selgroupsLen; $ig++ )
	    {
	      $oG = new \svnadmin\core\entities\Group;
	      $oG->id = $selgroups[$ig];
	      $oG->name = $selgroups[$ig];
	      
	      $done = $appEngine->getAccessPathEditProvider()->removeGroupFromAccessPath( $oG, $oAP );
	      if ($done)
	      	$appEngine->addMessage(tr("Removed group %0 from access path %1", array($oG->name, $oAP->path)));
	      else
	        $appEngine->addException(tr("Can not remove group %0 from access path %1", array($oG->name, $oAP->path)));
	    }
	  }
	  $appEngine->getAccessPathEditProvider()->save();
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
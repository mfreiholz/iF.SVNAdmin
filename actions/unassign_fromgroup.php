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

$appEngine->forwardInvalidModule( !$appEngine->isGroupEditActive() );

$selusers = get_request_var('selected_users', array());
$selgroups = get_request_var('selected_groups', array());
$selsubgroups = get_request_var('selected_subgroups', array());

// Validation.
if ((count($selusers) <= 0 && count($selsubgroups) <= 0) || count($selgroups) <= 0)
{
	$appEngine->addException(new ValidationException(tr("You have to select at least one user or one group.")));
}
else
{
	try {
	  // Count of: All, Done, Failed
	  $cntAll = count($selgroups) * count($selusers);
	
	  // Iterate all selected users and groups.
	  for( $i=0; $i<count($selgroups); $i++ )
	  {
	    $oG = new \svnadmin\core\entities\Group;
	    $oG->id = $selgroups[$i];
	    $oG->name = $selgroups[$i];

	    for( $k=0; $k<count($selsubgroups); $k++ )
	    {
	      $oS = new \svnadmin\core\entities\Group;
	      $oS->id = $selsubgroups[$k];
	      $oS->name = $selsubgroups[$k];

	      if( $appEngine->getGroupEditProvider()->removeSubgroupFromGroup( $oS, $oG ) )
	      {
	      	$appEngine->addMessage(tr("Removed group %0 from group %1", array($oS->name, $oG->name)));
	      }
	      else
	      {
	      	$appEngine->addException(tr("Could not remove group %0 from group %1", array($oS->name, $oG->name)));
	      }
	    } //for

	    for( $j=0; $j<count($selusers); $j++ )
	    {
	      $oU = new \svnadmin\core\entities\User;
	      $oU->id = $selusers[$j];
	      $oU->name = $selusers[$j];
	
	      if( $appEngine->getGroupEditProvider()->removeUserFromGroup( $oU, $oG ) )
	      {
	      	$appEngine->addMessage(tr("Removed user %0 from group %1", array($oU->name, $oG->name)));
	      }
	      else
	      {
	      	$appEngine->addException(tr("Could not remove user %0 from group %1", array($oU->name, $oG->name)));
	      }
	    } //for
	  } //for
	  $appEngine->getGroupEditProvider()->save();
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>

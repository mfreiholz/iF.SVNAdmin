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
if (!defined('ACTION_HANDLING')) {
  die("HaHa!");
}

$engine = \svnadmin\core\Engine::getInstance();

//
// Authentication
//

if (!$engine->isProviderActive(PROVIDER_ACCESSPATH_EDIT)) {
	$engine->forwardError(ERROR_INVALID_MODULE);
}

$engine->checkUserAuthentication(true, ACL_MOD_ACCESSPATH, ACL_ACTION_UNASSIGN);

//
// HTTP Request Vars
//

$selusers = get_request_var('selected_users');
$selgroups = get_request_var('selected_groups');
$selpaths = get_request_var('selected_accesspaths');

//
// Validation
//

if ($selpaths == NULL
	|| ($selgroups == NULL && $selusers == NULL)) {
	$engine->addException(new ValidationException(tr("You have to select at least one access-path and one user or group.")));
}
else {
	try {
		$selpathsLen = count($selpaths);
		$selusersLen = ($selusers != null) ? count($selusers) : 0;
		$selgroupsLen = ($selgroups != null) ? count($selgroups) : 0;
	  
		// iterate all selected_accesspaths
		for ($i = 0; $i < $selpathsLen; ++$i) {
			$oAP = new \svnadmin\core\entities\AccessPath($selpaths[$i]);
	
			// Is the user restricted to some paths? (project-manager)
			if ($engine->isAuthenticationActive()) {
				$currentUsername = $engine->getSessionUsername();
				if ($engine->getAclManager()->isUserAccessPathManager($currentUsername)) {
					if (!$engine->getAclManager()->isUserAdminOfPath($currentUsername, $selpaths[$i])) {
						$engine->addException(new Exception(tr("You don't have the permission to unassign the access path %0", array($oAP->path))));
						continue;
					}
				}
			}
	
			// iterate selected_users.
			for ($iu = 0; $iu < $selusersLen; ++$iu) {
				if (empty($selusers[$iu])) {
					continue;
				}
			
				$oU = new \svnadmin\core\entities\User($selusers[$iu], $selusers[$iu]);
	      
				// remove user from ap
				try {
					if ($engine->getAccessPathEditProvider()->removeUserFromAccessPath($oU, $oAP))
						$engine->addMessage(tr("Removed user %0 from access path %1", array($oU->name, $oAP->path)));
					else
						$engine->addException(new Exception(tr("Can not remove user %0 from access path %1", array($oU->name, $oAP->path))));
				}
				catch (Exception $e) {
					$engine->addException($e);
				}
			}
	    
			// iterate selected_groups.
			for ($ig = 0; $ig < $selgroupsLen; ++$ig) {
				if (empty($selgroups[$ig])) {
					continue;
				}
			
				$oG = new \svnadmin\core\entities\Group($selgroups[$ig], $selgroups[$ig]);
				
				// remove group from ap
				try {
					if ($engine->getAccessPathEditProvider()->removeGroupFromAccessPath($oG, $oAP))
						$engine->addMessage(tr("Removed group %0 from access path %1", array($oG->name, $oAP->path)));
					else
						$engine->addException(new Exception(tr("Can not remove group %0 from access path %1", array($oG->name, $oAP->path))));
				}
				catch (Exception $e) {
					$engine->addException($e);
				}
			}
		}
		$engine->getAccessPathEditProvider()->save();
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
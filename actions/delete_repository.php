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

//
// Authentication
//

$engine = \svnadmin\core\Engine::getInstance();

if (!$engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)) {
	$engine->forwardError(ERROR_INVALID_MODULE);
}

// Disabled by config?
if (!($engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDeleteEnabled', true))) {
	$engine->forwardError(ERROR_INVALID_MODULE);
}

//
// HTTP Request Vars
//

$varParentIdentifierEnc = get_request_var('pi');
$selrepos = get_request_var("selected_repos");
$remove_accesspaths = check_request_var('delete_ap');

$varParentIdentifier = rawurldecode($varParentIdentifierEnc);

//
// Validation
//

if ($selrepos == NULL || $varParentIdentifier === NULL) {
	$engine->addException(new ValidationException(tr("You have to select at least one repository.")));
}
else {
	try {
		// Iterate all selected items.
		$c = count($selrepos);
		for($i = 0; $i < $c; $i++) {
			$oR = new \svnadmin\core\entities\Repository($selrepos[$i], $varParentIdentifier);

			$b = $appEngine->getRepositoryEditProvider()->delete($oR);
			if (!$b) {
				throw new Exception(tr("Could not delete repository %0", array($oR->name)));
			}
        	$appEngine->addMessage(tr("The repository %0 has been deleted.", array($oR->name)));

        	// Remove Access-Paths of the repository.
        	if ($remove_accesspaths)
        	{
        		try {
					$apList = $appEngine->getAccessPathViewProvider()->getPathsOfRepository($oR);
					foreach ($apList as $ap)
					{
						$appEngine->getAccessPathEditProvider()->deleteAccessPath($ap);
						$appEngine->addMessage(tr('Removed Access-Path "%0"', array($ap->getPath())));
					}
					$appEngine->getAccessPathEditProvider()->save();
        		}
        		catch (Exception $ex2) {
        			$appEngine->addException($ex2);
        		}
        	} // if ($remove_accesspath)

		}
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
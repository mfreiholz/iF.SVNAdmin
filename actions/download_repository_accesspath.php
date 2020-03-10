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
if (!($engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDownloadAccessPathEnabled', true))) {
	$engine->forwardError(ERROR_INVALID_MODULE);
}

//
// HTTP Request Vars
//

$varParentIdentifierEnc = get_request_var('pi');
$varRepositoryNameEnc = get_request_var('r');
$varParentIdentifier = rawurldecode($varParentIdentifierEnc);
$varRepositoryName = rawurldecode($varRepositoryNameEnc);

if_log_array($varRepositoryName, '$varRepositoryName');
//
// Validation
//

if ($varRepositoryName == NULL || $varParentIdentifier === NULL) {
	$engine->addException(new ValidationException(tr("You have to select at least one repository.")));
}
else {
	try {
    $repositoryObject = new \svnadmin\core\entities\Repository($varRepositoryName, $varParentIdentifier);
    $apList = $appEngine->getAccessPathViewProvider()->getPathsOfRepository($repositoryObject);
    if_log_array($repositoryObject, '$repositoryObject');
    if_log_array($apList, '$apList');
//    global $appEngine;$appEngine->addMessage(var_dump($repositoryObject));
//    global $appEngine;$appEngine->addMessage(var_dump($apList));
    $engine->getRepositoryEditProvider()->downloadAccessPath($repositoryObject, $apList);
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
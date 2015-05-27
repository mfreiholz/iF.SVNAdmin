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

if (!$engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)
	|| !$engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDumpEnabled', true)) {
	$engine->forwardError(ERROR_INVALID_MODULE);
}

$engine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_DUMP);

//
// HTTP Request Vars
//

$varParentIdentifierEnc = get_request_var('pi');
$varRepositoryNameEnc = get_request_var('r');

$varParentIdentifier = rawurldecode($varParentIdentifierEnc);
$varRepositoryName = rawurldecode($varRepositoryNameEnc);

//
// Validation
//

if ($varParentIdentifier == NULL || $varRepositoryName == NULL) {
	$engine->addException(new ValidationException(tr('You have to select at least one repository.')));
}
else {
	try {
		$repositoryObject = new \svnadmin\core\entities\Repository($varRepositoryName, $varParentIdentifier);
		$engine->getRepositoryEditProvider()->dump($repositoryObject);
	}
	catch (Exception $e) {
		\svnadmin\core\Engine::getInstance()->addException($e);
	}
}
?>
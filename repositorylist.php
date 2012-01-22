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

if (!$appEngine->isRepositoryViewActive())
{
	$appEngine->forwardError(ERROR_INVALID_MODULE);
}

$appEngine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_VIEW);
$appTR->loadModule("repositorylist");

//
// Actions
//

if (check_request_var("delete"))
{
	$appEngine->handleAction("delete_repository");
}

//
// View data
//

$repos = array();
try {
	$repos = $appEngine->getRepositoryViewProvider()->getRepositories();
	usort($repos, array("\svnadmin\core\entities\Repository","compare"));
}
catch (Exception $ex) {
	$appEngine->addException($ex);
}

SetValue("ShowDeleteButton", ($appEngine->getConfig()->getValue('GUI', 'RepositoryDeleteEnabled', '1') == 1));
SetValue("RepositoryList", $repos);
ProcessTemplate("repository/repositorylist.html.php");
?>
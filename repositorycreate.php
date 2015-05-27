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

$engine = \svnadmin\core\Engine::getInstance();

if (!$engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)) {
	$engine->forwardInvalidModule(true);
}

$engine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_ADD);
$appTR->loadModule("repositorycreate");

//
// Actions
//

if (check_request_var('create'))
{
	$engine->handleAction('create_repository');
}

//
// View Data
//

SetValue('RepositoryParentList', $engine->getRepositoryViewProvider()->getRepositoryParents());
ProcessTemplate("repository/repositorycreate.html.php");
?>
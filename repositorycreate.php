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

// check the login user authentication
$engine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_ADD);
// load the translate file
$appTR->loadModule("repositorycreate");

//
// Actions
//

// if user click the create submit button
if (check_request_var('create'))
{
    // do action
    if_log_debug('deal create repository action');
	$engine->handleAction('create_repository');
}

//
// View Data
//

// Render the repository Parent list to html template.
SetValue('RepositoryParentList', $engine->getRepositoryViewProvider()->getRepositoryParents());
// Render template
ProcessTemplate("repository/repositorycreate.html.php");
?>
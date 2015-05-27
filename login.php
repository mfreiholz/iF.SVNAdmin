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

// Authentication disabled.
if (!$appEngine->isAuthenticationActive() )
{
	$appEngine->forward(PAGE_HOME, null, true);
}

// If the user is already logged in, we should redirect him to the index page.
if ($appEngine->checkUserAuthentication(false))
{
	$appEngine->forward(PAGE_HOME, null, true);
}

$appTR->loadModule("login");

//
// Actions
//
if (check_request_var("login"))
{
	$appEngine->handleAction("login_authentication");
}

//
// HTTP Request Vars
//

$logged_out = check_request_var("loggedout");

//
// View Data
//

SetValue("LoggedOut", $logged_out);
ProcessTemplate("login.html.php");
?>
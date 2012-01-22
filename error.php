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

$appEngine->checkUserAuthentication();
$appTR->loadModule("errors");

//
// HTTP Request Vars
//

$e = get_request_var('e');

//
// View Data
//

$page = "";
switch ($e)
{
	case ERROR_INVALID_MODULE:
		$page = "error/invalid-module.html.php";
		break;

	case ERROR_NO_ACCESS:
		$acl_module = get_request_var("m");
		$acl_action = get_request_var("a");
		$page = "error/no-access.html.php";
		SetValue("Module", $acl_module);
		SetValue("Action", $acl_action);
		break;

	default:
		$appEngine->forward(PAGE_HOME, true);
		break;
}

ProcessTemplate($page);
?>
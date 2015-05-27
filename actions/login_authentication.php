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
if (!defined('ACTION_HANDLING'))
{
  die("HaHa!");
}

//
// HTTP Request Vars
//

$loginname = get_request_var('loginname');
$loginpass = get_request_var('loginpass');

//
// Validation
//

if ($loginname == NULL || $loginpass == NULL)
{
	$appEngine->addException(new ValidationException(tr("You have to fill out all fields.")));
}
else
{
	try {
		if ($appEngine->getAuthenticator() != null)
		{
			$u = new \svnadmin\core\entities\User($loginname, $loginname);
			$authOK = $appEngine->getAuthenticator()->authenticate( $u, $loginpass );
			if ($authOK)
			{
				// Set session variable which indicates that the user is logged in.
				$_SESSION["svnadmin_username"] = $loginname;
				$appEngine->forward(PAGE_HOME, null, true);
			}
			else
			{
				$appEngine->addException(new ValidationException(tr("Wrong user/password combination.")));
			}
		}
		else
		{
			// Authentication is deactivated!
			// ...
		}
	}
	catch (Exception $ex) {
		$appEngine->addException($ex);
	}
}
?>
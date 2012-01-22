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
$appEngine->forwardInvalidModule( !$appEngine->isUserEditActive() );
$appEngine->checkUserAuthentication(true, ACL_MOD_USER, ACL_ACTION_ADD);
$appTR->loadModule("usercreate");

// Action handling.
// Form request to create the user
$create = check_request_var('create');
if( $create )
{
  $appEngine->handleAction('create_user');
}

ProcessTemplate("user/usercreate.html.php");
?>
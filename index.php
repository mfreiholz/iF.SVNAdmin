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
include_once("include/config.inc.php");

$appEngine->checkUserAuthentication();
$appTR->loadModule("index");
$appTR->loadModule("roles");

// PHP version.
$phpVersion=phpversion();

// Roles of current user.
$roles=null;
if ($appEngine->isAuthenticationActive())
{
  $u=new \svnadmin\core\entities\User();
  $u->name=$appEngine->getSessionUsername();
  
  $roles=$appEngine->getAclManager()->getRolesOfUser($u);
  sort($roles);
}

SetValue("PHPVersion", $phpVersion);
SetValue("Roles", $roles);
ProcessTemplate("index.html.php");
?>
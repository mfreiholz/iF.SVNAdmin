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

if (!$engine->isProviderActive(PROVIDER_REPOSITORY_VIEW)) {
	$engine->forwardError(ERROR_INVALID_MODULE);
}

$engine->checkUserAuthentication(true, ACL_MOD_REPO, ACL_ACTION_VIEW);
$appTR->loadModule("repositoryview");

//
// HTTP Request Vars
//

$varParentIdentifierEnc = get_request_var('pi');
$varRepoEnc = get_request_var('r');
$varPathEnc = get_request_var('p');

$varParentIdentifier = rawurldecode($varParentIdentifierEnc);
$varRepo = rawurldecode($varRepoEnc);
$varPath = rawurldecode($varPathEnc);

//
// View Data
//

$oR = new \svnadmin\core\entities\Repository($varRepo, $varParentIdentifier);

try {
	// Get the files of the selected repository path.
	$repoPathList = $engine->getRepositoryViewProvider()->listPath($oR, $varPath);

	// Web-Link - Directory Listing
	$apacheWebLink = $engine->getConfig()->getValue("GUI", "ApacheDirectoryListing");
	$customWebLink = $engine->getConfig()->getValue("GUI", "CustomDirectoryListing");
	$hasApacheWebLink = !empty($apacheWebLink) ? true : false;
	$hasCustomWebLink = !empty($customWebLink) ? true : false;

	// Is the current path the root directory of the repository?
	$isRepositoryRoot = false;
	if ($varPath == NULL || $varPath == "/")
	{
	  $isRepositoryRoot = true;
	}

	// Create the list of directory items.
	// $val->type => 0 is folder, 1 is file.
	$itemList = array();
	foreach ($repoPathList as &$val)
	{
		// Add weblink property.
		if ($hasApacheWebLink || $hasCustomWebLink)
		{
			$args = array($oR->getEncodedName(), $val->getEncodedRelativePath());

			if ($hasApacheWebLink)
			{
				$val->apacheWebLink = IF_StringUtils::arguments($apacheWebLink, $args);
			}

			if ($hasCustomWebLink)
			{
				$val->customWebLink = IF_StringUtils::arguments($customWebLink, $args);
			}
		}
		$itemList[] = $val;
	}

	// Create "up" link.
	// Load the user list template file and add the array of users.
	$backLinkPath = "/";
	if(empty($varPath))
	{
	  $varPath = "";
	}
	else
	{
	  $pos = strrpos($varPath, "/");
	  if ($pos !== false && $pos > 0)
	  {
	    $backLinkPath = substr($varPath, 0, $pos);
	  }
	}

	SetValue("ApacheWebLink", $hasApacheWebLink);
	SetValue("CustomWebLink", $hasCustomWebLink);
	SetValue("ItemList", $itemList);
	SetValue("Repository", $oR);
	SetValue("BackLinkPath", $backLinkPath);
	SetValue("BackLinkPathEncoded", rawurlencode($backLinkPath));
	SetValue("CurrentPath", $varPath);
	SetValue("RepositoryRoot", $isRepositoryRoot);
}
catch (Exception $ex) {
	$engine->addException($ex);
}
ProcessTemplate("repository/repositoryview.html.php");
?>
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
$appTR->loadModule("repositorylist");

//
// Actions
//
// make sure to do which action
if (check_request_var("delete")) {
  $engine->handleAction("delete_repository");
} else if (check_request_var('dump')) {
  $engine->handleAction('dump_repository');
  exit(0);
} else if (check_request_var('tree')) {
  $engine->handleAction('tree_repository');
  exit(0);
} else if (check_request_var('accesspath')) {
  $engine->handleAction('download_repository_accesspath');
  exit(0);
}

//
// View data
//

$repositoryParentList = array();
$repositoryList = array();
try {
  // Repository parent locations.
  if_log_debug('Repository parent locations.');
  // @see RepositoryViewProvider.class.php to get repository Parent folder
  $repositoryParentList = $engine->getRepositoryViewProvider()->getRepositoryParents();

  // Repositories of all locations.
  foreach ($repositoryParentList as $rp) {
    // getRepositoriesOfParent() will get the repository list data
    // @see classes/providers/RepositoryViewProvider.class.php
    $repositoryList[$rp->identifier] = $engine->getRepositoryViewProvider()->getRepositoriesOfParent($rp);
    // Sort the array in ascending order a->z
    usort($repositoryList[$rp->identifier], array('\svnadmin\core\entities\Repository', 'compare'));
  }


  // Show options column?
  // show the download repository dump file icon
  if (($engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)
    && $engine->hasPermission(ACL_MOD_REPO, ACL_ACTION_DUMP)
    && $engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDumpEnabled', false))
  ) {
    SetValue('ShowOptions', true);
    SetValue('ShowDumpOption', true);
  }

  // Show Download repository path list Excel file button
  if (($engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)
    && $engine->hasPermission(ACL_MOD_REPO, ACL_ACTION_DOWNLOAD_TREE)
    && $engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDownloadTreeEnabled', false))
  ) {
    SetValue('ShowOptions', true);
    SetValue('ShowDownloadTreeOption', true);
  }

  // Show Download repository access path list Excel file button
  if (($engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)
    && $engine->hasPermission(ACL_MOD_REPO, ACL_ACTION_DOWNLOAD_ACCESS_PATH)
    && $engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDownloadAccessPathEnabled', false))
  ) {
    SetValue('ShowOptions', true);
    SetValue('ShowDownloadAccessPathOption', true);
  }
} catch (Exception $ex) {
  $engine->addException($ex);
}

SetValue('RepositoryParentList', $repositoryParentList);
// save the $repositoryList to the list
SetValue('RepositoryList', $repositoryList);
SetValue('ShowDeleteButton', $engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDeleteEnabled', true));
ProcessTemplate('repository/repositorylist.html.php');
?>
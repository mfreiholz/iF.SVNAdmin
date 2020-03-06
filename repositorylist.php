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

if (check_request_var("delete")) {
	$engine->handleAction("delete_repository");
}
else if (check_request_var('dump')) {
	$engine->handleAction('dump_repository');
	exit(0);
}
else if (check_request_var('load')) {
	
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
    // 通过RepositoryViewProvider.class.php获取仓库父目录
	$repositoryParentList = $engine->getRepositoryViewProvider()->getRepositoryParents();
	
	// Repositories of all locations.
	foreach ($repositoryParentList as $rp) {
	    // 此处的getRepositoriesOfParent()函数会获取仓库列表数据
        // 详细参考classes/providers/RepositoryViewProvider.class.php文件
        $repositoryList[$rp->identifier] = $engine->getRepositoryViewProvider()->getRepositoriesOfParent($rp);
		// 对数组进行升序排序
		usort($repositoryList[$rp->identifier], array('\svnadmin\core\entities\Repository', 'compare'));
    }


    // Show options column?
	if (($engine->isProviderActive(PROVIDER_REPOSITORY_EDIT)
		&& $engine->hasPermission(ACL_MOD_REPO, ACL_ACTION_DUMP)
		&& $engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDumpEnabled', false))
		){
		SetValue('ShowOptions', true);
		SetValue('ShowDumpOption', true);
	}
}
catch (Exception $ex) {
	$engine->addException($ex);
}

SetValue('RepositoryParentList', $repositoryParentList);
// 设置值，将$repositoryList的值写入到列表中
SetValue('RepositoryList', $repositoryList);
SetValue('ShowDeleteButton', $engine->getConfig()->getValueAsBoolean('GUI', 'RepositoryDeleteEnabled', true));
ProcessTemplate('repository/repositorylist.html.php');
?>